"""
torexguard — Python SDK for Torex Guard Runtime Security Platform
Install: pip install torexguard
Usage:
    from torexguard import Guard
    Guard.init(api_key="tg_xxxx", realtime=True)
"""

import hashlib
import json
import logging
import os
import platform
import re
import socket
import threading
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional, List, Dict, Any

import requests

__version__ = "1.0.0"
logger = logging.getLogger("torexguard")


# ──────────────────────────────────────────────────────────────
# Malware Signatures
# ──────────────────────────────────────────────────────────────

PHP_PATTERNS = [
    re.compile(r'eval\s*\(\s*base64_decode', re.IGNORECASE),
    re.compile(r'shell_exec\s*\(', re.IGNORECASE),
    re.compile(r'passthru\s*\(', re.IGNORECASE),
    re.compile(r'FilesMan|c99shell|r57shell', re.IGNORECASE),
]

PYTHON_PATTERNS = [
    re.compile(r'subprocess\.Popen', re.IGNORECASE),
    re.compile(r'os\.system\s*\(', re.IGNORECASE),
    re.compile(r'pickle\.loads\s*\(', re.IGNORECASE),
    re.compile(r'marshal\.loads\s*\(', re.IGNORECASE),
    re.compile(r'__import__\s*\(\s*[\'"]os', re.IGNORECASE),
]

CRYPTO_PATTERNS = [
    re.compile(r'xmrig|monero|cryptonight', re.IGNORECASE),
    re.compile(r'stratum\+tcp://', re.IGNORECASE),
]

DANGEROUS_EXTENSIONS = {'.php', '.phtml', '.py', '.sh', '.bash', '.rb'}

EXCLUDED_DIRS = {'__pycache__', '.git', 'node_modules', 'vendor', '.venv', 'venv', '.env'}


# ──────────────────────────────────────────────────────────────
# HTTP Client
# ──────────────────────────────────────────────────────────────

class GuardClient:
    def __init__(self, api_key: str, host: str):
        self.api_key = api_key
        self.host = host.rstrip('/')
        self.agent_token = ''
        self.agent_id = ''
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'User-Agent': f'TorexGuardSDK-Python/{__version__}',
        })

    def _headers(self) -> Dict[str, str]:
        return {'X-Agent-Token': self.agent_token}

    def register(self) -> Dict:
        payload = {
            'project_api_key': self.api_key,
            'hostname': socket.gethostname(),
            'os': platform.system(),
            'os_version': platform.release(),
            'arch': platform.machine(),
            'version': __version__,
            'capabilities': ['file_scan', 'process_monitor', 'file_integrity'],
        }
        resp = self.session.post(f'{self.host}/api/v1/agent/register', json=payload, timeout=30)
        resp.raise_for_status()
        data = resp.json()
        self.agent_token = data.get('agent_token', '')
        self.agent_id = data.get('agent_id', '')
        return data

    def heartbeat(self, metrics: Dict = None) -> None:
        try:
            payload = {'timestamp': datetime.now(timezone.utc).isoformat(), **(metrics or {})}
            self.session.post(f'{self.host}/api/v1/agent/heartbeat', json=payload,
                              headers=self._headers(), timeout=10)
        except Exception as e:
            logger.debug(f'Heartbeat failed: {e}')

    def report_threat(self, threat: Dict) -> None:
        try:
            self.session.post(f'{self.host}/api/v1/agent/threats', json=threat,
                              headers=self._headers(), timeout=15)
        except Exception as e:
            logger.warning(f'Failed to report threat: {e}')

    def report_event(self, event: Dict) -> None:
        try:
            self.session.post(f'{self.host}/api/v1/agent/events', json=event,
                              headers=self._headers(), timeout=10)
        except Exception as e:
            logger.debug(f'Failed to report event: {e}')

    def disconnect(self) -> None:
        try:
            self.session.post(f'{self.host}/api/v1/agent/disconnect',
                              headers=self._headers(), timeout=5)
        except Exception:
            pass


# ──────────────────────────────────────────────────────────────
# File Scanner
# ──────────────────────────────────────────────────────────────

class FileScanner:
    def __init__(self, client: GuardClient):
        self.client = client

    def scan(self, path: str) -> Dict:
        root = Path(path)
        files_scanned = 0
        threats_found = 0

        for file_path in root.rglob('*'):
            if not file_path.is_file():
                continue
            if any(ex in file_path.parts for ex in EXCLUDED_DIRS):
                continue
            if file_path.suffix.lower() not in DANGEROUS_EXTENSIONS:
                files_scanned += 1
                continue

            files_scanned += 1
            threat = self._scan_file(file_path)
            if threat:
                threats_found += 1
                self.client.report_threat(threat)
                logger.warning(f"[THREAT] {threat['severity'].upper()} — {file_path}")

        return {'files_scanned': files_scanned, 'threats_found': threats_found}

    def _scan_file(self, path: Path) -> Optional[Dict]:
        try:
            content = path.read_text(errors='ignore')
        except Exception:
            return None

        ext = path.suffix.lower()
        matched = []

        patterns = PHP_PATTERNS if ext in ('.php', '.phtml') else PYTHON_PATTERNS if ext == '.py' else []
        for pattern in patterns + CRYPTO_PATTERNS:
            if pattern.search(content):
                matched.append(pattern.pattern)

        if not matched:
            return None

        file_hash = hashlib.sha256(path.read_bytes()).hexdigest()
        confidence = min(100, len(matched) * 25)

        return {
            'type': 'web_shell' if ext in ('.php', '.phtml') else 'malware',
            'severity': 'critical' if len(matched) >= 3 else 'high',
            'title': f'Malicious {ext[1:].upper()} file: {path.name}',
            'file_path': str(path),
            'file_hash': file_hash,
            'file_size': path.stat().st_size,
            'confidence_score': confidence,
            'signatures_matched': matched,
            'detected_at': datetime.now(timezone.utc).isoformat(),
        }


# ──────────────────────────────────────────────────────────────
# Heartbeat Thread
# ──────────────────────────────────────────────────────────────

class HeartbeatThread(threading.Thread):
    def __init__(self, client: GuardClient, interval: int = 60):
        super().__init__(daemon=True)
        self.client = client
        self.interval = interval
        self._stop = threading.Event()

    def run(self):
        while not self._stop.wait(self.interval):
            self.client.heartbeat()

    def stop(self):
        self._stop.set()


# ──────────────────────────────────────────────────────────────
# Main Guard Interface
# ──────────────────────────────────────────────────────────────

class Guard:
    _instance: Optional['Guard'] = None

    def __init__(self, api_key: str, host: str, realtime: bool, scan_paths: List[str]):
        self.client = GuardClient(api_key, host)
        self.scanner = FileScanner(self.client)
        self.realtime = realtime
        self.scan_paths = scan_paths
        self._heartbeat: Optional[HeartbeatThread] = None

    @classmethod
    def init(
        cls,
        api_key: str,
        host: str = 'https://guard.torexlabs.cloud',
        realtime: bool = True,
        scan_paths: Optional[List[str]] = None,
        log_level: str = 'WARNING',
    ) -> 'Guard':
        logging.basicConfig(level=getattr(logging, log_level))

        instance = cls(
            api_key=api_key,
            host=host,
            realtime=realtime,
            scan_paths=scan_paths or [os.getcwd()],
        )

        try:
            result = instance.client.register()
            logger.info(f'[TorexGuard] Connected — Agent: {result.get("agent_id")}')
        except Exception as e:
            logger.error(f'[TorexGuard] Connection failed: {e}')
            return instance

        if realtime:
            instance._heartbeat = HeartbeatThread(instance.client)
            instance._heartbeat.start()
            logger.info('[TorexGuard] Realtime monitoring active')

        cls._instance = instance
        return instance

    def scan(self, path: str = None) -> Dict:
        """Run an on-demand file scan."""
        target = path or (self.scan_paths[0] if self.scan_paths else os.getcwd())
        logger.info(f'[TorexGuard] Scanning: {target}')
        result = self.scanner.scan(target)
        logger.info(f'[TorexGuard] Scan complete — Files: {result["files_scanned"]}, Threats: {result["threats_found"]}')
        return result

    def report(self, threat_type: str, severity: str, title: str, **kwargs) -> None:
        """Manually report a custom threat."""
        self.client.report_threat({
            'type': threat_type,
            'severity': severity,
            'title': title,
            'detected_at': datetime.now(timezone.utc).isoformat(),
            **kwargs,
        })

    def event(self, event_type: str, severity: str = 'info', payload: Dict = None) -> None:
        """Report a security event."""
        self.client.report_event({
            'type': event_type,
            'severity': severity,
            'payload': payload or {},
            'occurred_at': datetime.now(timezone.utc).isoformat(),
        })

    def shutdown(self) -> None:
        if self._heartbeat:
            self._heartbeat.stop()
        self.client.disconnect()
        logger.info('[TorexGuard] Shutdown complete')

    @classmethod
    def get(cls) -> Optional['Guard']:
        return cls._instance


# ──────────────────────────────────────────────────────────────
# Django / FastAPI integration helpers
# ──────────────────────────────────────────────────────────────

class DjangoMiddleware:
    """Django middleware for Torex Guard request monitoring."""

    SQL_PATTERNS = [
        re.compile(r'UNION\s+SELECT', re.IGNORECASE),
        re.compile(r'DROP\s+TABLE', re.IGNORECASE),
        re.compile(r"'\s+OR\s+'", re.IGNORECASE),
    ]

    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        self._check_request(request)
        return self.get_response(request)

    def _check_request(self, request):
        guard = Guard.get()
        if not guard:
            return
        for key, value in request.POST.items():
            for pattern in self.SQL_PATTERNS:
                if pattern.search(str(value)):
                    guard.event('sql_injection_attempt', 'high', {
                        'input_key': key,
                        'source_ip': request.META.get('REMOTE_ADDR'),
                        'path': request.path,
                    })


def fastapi_middleware(app):
    """FastAPI/Starlette middleware factory."""
    from starlette.middleware.base import BaseHTTPMiddleware
    from starlette.requests import Request

    class TorexGuardMiddleware(BaseHTTPMiddleware):
        async def dispatch(self, request: Request, call_next):
            return await call_next(request)

    app.add_middleware(TorexGuardMiddleware)
    return app


__all__ = ['Guard', 'DjangoMiddleware', 'fastapi_middleware', '__version__']
