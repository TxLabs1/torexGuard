<#
.SYNOPSIS
    Torex Guard agent installer for Windows.

.EXAMPLE
    iex (iwr "https://<dashboard-host>/install.ps1" -UseBasicParsing).Content
    Install-TorexAgent -ApiKey "tg_..." -Host "https://<dashboard-host>"
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $ApiKey,
    [string] $Host    = "https://guard.torexlabs.cloud",
    [string] $Version = "",
    [string] $Prefix  = "$env:ProgramFiles\TorexGuard",
    [string] $Repo    = "torexlabs/torex-guard"
)

$ErrorActionPreference = "Stop"

function Log([string]$msg) { Write-Host "[torex-guard] $msg" }

if ([string]::IsNullOrWhiteSpace($Version)) {
    Log "looking up latest release"
    $latest = (Invoke-RestMethod "https://api.github.com/repos/$Repo/releases/latest").tag_name
    $Version = $latest.TrimStart("v")
}

$arch = if ([Environment]::Is64BitOperatingSystem) { "amd64" } else { throw "32-bit Windows is not supported" }
$archive = "torex-agent_${Version}_windows_${arch}.zip"
$url     = "https://github.com/$Repo/releases/download/v${Version}/$archive"
$sumsUrl = "https://github.com/$Repo/releases/download/v${Version}/SHA256SUMS"

$tmp = New-Item -ItemType Directory -Path "$env:TEMP\torex-install-$(Get-Random)" -Force
try {
    Log "downloading $url"
    Invoke-WebRequest -Uri $url       -OutFile "$tmp\$archive" -UseBasicParsing
    Invoke-WebRequest -Uri $sumsUrl  -OutFile "$tmp\SHA256SUMS" -UseBasicParsing

    Log "verifying SHA256"
    $hash = (Get-FileHash "$tmp\$archive" -Algorithm SHA256).Hash.ToLower()
    $line = Select-String -Path "$tmp\SHA256SUMS" -Pattern "  $archive$" | Select-Object -First 1
    if (-not $line) { throw "no checksum entry for $archive" }
    $expected = ($line.Line -split '\s+')[0].ToLower()
    if ($hash -ne $expected) { throw "checksum mismatch: $hash != $expected" }

    Log "extracting to $Prefix"
    New-Item -ItemType Directory -Path $Prefix -Force | Out-Null
    Expand-Archive -Path "$tmp\$archive" -DestinationPath $Prefix -Force

    $cfgDir = "$env:ProgramData\TorexGuard"
    $cfg = "$cfgDir\config.yaml"
    New-Item -ItemType Directory -Path $cfgDir -Force | Out-Null
    if (-not (Test-Path $cfg)) {
        Log "writing $cfg"
        @"
api_key: $ApiKey
host: $Host
scan_interval: 300
heartbeat_interval: 60
monitored_paths:
  - C:\inetpub\wwwroot
  - C:\xampp\htdocs
excluded_paths:
  - vendor
  - node_modules
  - .git
"@ | Out-File -FilePath $cfg -Encoding UTF8
    }

    $bin = Join-Path $Prefix "torex-agent.exe"
    $serviceName = "TorexGuardAgent"
    if (Get-Service $serviceName -ErrorAction SilentlyContinue) {
        Stop-Service $serviceName -Force -ErrorAction SilentlyContinue
        & sc.exe delete $serviceName | Out-Null
        Start-Sleep -Seconds 1
    }
    & sc.exe create $serviceName binPath= "`"$bin`" --config `"$cfg`" --log-format json" start= auto displayname= "Torex Guard Agent" | Out-Null
    Start-Service $serviceName
    Log "agent installed and started as Windows service '$serviceName'"
}
finally {
    Remove-Item -Recurse -Force $tmp
}
