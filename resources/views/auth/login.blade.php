{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Torex Guard</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --tg-bg:       #060b14;
            --tg-card:     #0d1526;
            --tg-border:   rgba(0,255,136,0.08);
            --tg-accent:   #00ff88;
            --tg-critical: #ff3366;
            --tg-text:     #e8f0fe;
            --tg-muted:    #4a6080;
            --tg-secondary:#8099c0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--tg-bg);
            color: var(--tg-text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                repeating-linear-gradient(0deg, transparent, transparent 49px, rgba(0,255,136,0.025) 50px),
                repeating-linear-gradient(90deg, transparent, transparent 49px, rgba(0,255,136,0.025) 50px);
            animation: grid-scroll 20s linear infinite;
            pointer-events: none;
        }

        @keyframes grid-scroll {
            from { background-position: 0 0; }
            to   { background-position: 50px 50px; }
        }

        /* Glow orbs */
        body::after {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(0,255,136,0.06) 0%, transparent 70%);
            top: -200px; left: -200px;
            pointer-events: none;
        }

        .auth-wrap {
            width: 100%;
            max-width: 440px;
            padding: 24px 20px;
            position: relative;
            z-index: 1;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--tg-accent), #00aaff);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #060b14;
            margin-bottom: 14px;
            box-shadow: 0 0 30px rgba(0,255,136,0.3);
        }

        .auth-logo-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--tg-text);
            letter-spacing: 0.5px;
        }

        .auth-logo-name span { color: var(--tg-accent); }

        .auth-logo-sub {
            font-size: 12px;
            color: var(--tg-secondary);
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        .auth-card {
            background: var(--tg-card);
            border: 1px solid var(--tg-border);
            border-radius: 18px;
            padding: 32px;
        }

        .auth-card-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .auth-card-sub {
            font-size: 13px;
            color: var(--tg-secondary);
            margin-bottom: 28px;
        }

        .auth-form-group { margin-bottom: 18px; }

        .auth-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--tg-secondary);
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .auth-input-wrap {
            position: relative;
        }

        .auth-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--tg-muted);
            font-size: 13px;
        }

        .auth-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--tg-border);
            border-radius: 11px;
            padding: 11px 14px 11px 40px;
            color: var(--tg-text);
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .auth-input:focus {
            border-color: var(--tg-accent);
            background: rgba(0,255,136,0.03);
            box-shadow: 0 0 0 3px rgba(0,255,136,0.1);
        }

        .auth-input::placeholder { color: var(--tg-muted); }

        .auth-input.is-invalid {
            border-color: var(--tg-critical);
            background: rgba(255,51,102,0.04);
        }

        .auth-error {
            font-size: 12px;
            color: var(--tg-critical);
            margin-top: 6px;
        }

        .auth-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            justify-content: space-between;
        }

        .auth-check-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--tg-secondary);
            cursor: pointer;
        }

        .auth-check-label input {
            accent-color: var(--tg-accent);
            width: 15px; height: 15px;
        }

        .auth-forgot {
            font-size: 13px;
            color: var(--tg-accent);
            text-decoration: none;
        }

        .auth-forgot:hover { text-decoration: underline; }

        .auth-btn {
            width: 100%;
            background: var(--tg-accent);
            color: #060b14;
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .auth-btn:hover {
            background: #00e07a;
            box-shadow: 0 0 24px rgba(0,255,136,0.4);
            transform: translateY(-1px);
        }

        .auth-btn:active { transform: translateY(0); }

        .auth-divider {
            text-align: center;
            margin: 22px 0;
            position: relative;
            color: var(--tg-muted);
            font-size: 12px;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: var(--tg-border);
        }
        .auth-divider::before { left: 0; }
        .auth-divider::after  { right: 0; }

        .auth-switch {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: var(--tg-secondary);
        }

        .auth-switch a {
            color: var(--tg-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-switch a:hover { text-decoration: underline; }

        .auth-alert-error {
            background: rgba(255,51,102,0.08);
            border: 1px solid rgba(255,51,102,0.2);
            border-radius: 10px;
            padding: 12px 15px;
            color: var(--tg-critical);
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* Scan line effect */
        .auth-card::before {
            content: '';
            position: absolute;
            top: -1px; left: 0;
            width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--tg-accent), transparent);
            border-radius: 18px 18px 0 0;
        }

        .auth-card { position: relative; }

        /* Security badges */
        .auth-badges {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 22px;
        }

        .auth-badge-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--tg-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .auth-badge-item i { color: var(--tg-accent); font-size: 10px; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="fas fa-shield-halved"></i></div>
        <div class="auth-logo-name">Torex <span>Guard</span></div>
        <div class="auth-logo-sub">Runtime Security Platform</div>
    </div>

    <div class="auth-card">
        <div class="auth-card-title">Sign In</div>
        <div class="auth-card-sub">Access your security command center</div>

        @if($errors->any())
        <div class="auth-alert-error">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ $errors->first() }}
        </div>
        @endif

        @if(session('status'))
        <div style="background:rgba(0,255,136,0.08);border:1px solid rgba(0,255,136,0.2);border-radius:10px;padding:12px 15px;color:var(--tg-accent);font-size:13px;margin-bottom:20px">
            <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="auth-form-group">
                <label class="auth-label">Email Address</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-envelope auth-input-icon"></i>
                    <input type="email" name="email" class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="you@company.com" value="{{ old('email') }}" autocomplete="email" required>
                </div>
                @error('email') <div class="auth-error"><i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}</div> @enderror
            </div>

            <div class="auth-form-group">
                <label class="auth-label">Password</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-lock auth-input-icon"></i>
                    <input type="password" name="password" class="auth-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           placeholder="••••••••••••" autocomplete="current-password" required>
                </div>
                @error('password') <div class="auth-error"><i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}</div> @enderror
            </div>

            <div class="auth-check">
                <label class="auth-check-label">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Remember this device
                </label>
                <a href="{{ route('password.request') }}" class="auth-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="auth-btn">
                <i class="fas fa-shield-halved"></i>
                Authenticate
            </button>
        </form>

        <div class="auth-switch">
            Don't have an account? <a href="{{ route('register') }}">Create one</a>
        </div>
    </div>

    <div class="auth-badges">
        <div class="auth-badge-item"><i class="fas fa-lock"></i> TLS Encrypted</div>
        <div class="auth-badge-item"><i class="fas fa-shield-check"></i> Zero Trust</div>
        <div class="auth-badge-item"><i class="fas fa-eye-slash"></i> Private</div>
    </div>
</div>
</body>
</html>
