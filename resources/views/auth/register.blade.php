<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Torex Guard</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --tg-bg:#060b14;--tg-card:#0d1526;--tg-border:rgba(0,255,136,0.08);
            --tg-accent:#00ff88;--tg-critical:#ff3366;--tg-text:#e8f0fe;
            --tg-muted:#4a6080;--tg-secondary:#8099c0;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{background:var(--tg-bg);color:var(--tg-text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow-x:hidden;}
        body::before{content:'';position:fixed;inset:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 49px,rgba(0,255,136,0.02) 50px),repeating-linear-gradient(90deg,transparent,transparent 49px,rgba(0,255,136,0.02) 50px);animation:grid-scroll 20s linear infinite;pointer-events:none;}
        @keyframes grid-scroll{from{background-position:0 0;}to{background-position:50px 50px;}}
        .auth-wrap{width:100%;max-width:520px;padding:24px 20px;position:relative;z-index:1;}
        .auth-logo{text-align:center;margin-bottom:28px;}
        .auth-logo-icon{width:52px;height:52px;background:linear-gradient(135deg,var(--tg-accent),#00aaff);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;font-size:24px;color:#060b14;margin-bottom:12px;box-shadow:0 0 28px rgba(0,255,136,0.3);}
        .auth-logo-name{font-family:'Rajdhani',sans-serif;font-size:26px;font-weight:700;}
        .auth-logo-name span{color:var(--tg-accent);}
        .auth-logo-sub{font-size:12px;color:var(--tg-secondary);margin-top:4px;}
        .auth-card{background:var(--tg-card);border:1px solid var(--tg-border);border-radius:18px;padding:32px;position:relative;}
        .auth-card::before{content:'';position:absolute;top:-1px;left:0;width:100%;height:2px;background:linear-gradient(90deg,transparent,var(--tg-accent),transparent);border-radius:18px 18px 0 0;}
        .auth-card-title{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;margin-bottom:4px;}
        .auth-card-sub{font-size:13px;color:var(--tg-secondary);margin-bottom:24px;}
        .auth-label{display:block;font-size:11px;font-weight:600;letter-spacing:0.5px;color:var(--tg-secondary);margin-bottom:6px;text-transform:uppercase;}
        .auth-input-wrap{position:relative;}
        .auth-input-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--tg-muted);font-size:13px;}
        .auth-input{width:100%;background:rgba(255,255,255,0.03);border:1px solid var(--tg-border);border-radius:11px;padding:11px 14px 11px 38px;color:var(--tg-text);font-size:13.5px;outline:none;transition:all 0.2s;font-family:'Inter',sans-serif;}
        .auth-input:focus{border-color:var(--tg-accent);background:rgba(0,255,136,0.03);box-shadow:0 0 0 3px rgba(0,255,136,0.1);}
        .auth-input::placeholder{color:var(--tg-muted);}
        .auth-input.is-invalid{border-color:var(--tg-critical);}
        .auth-error{font-size:12px;color:var(--tg-critical);margin-top:5px;}
        .auth-btn{width:100%;background:var(--tg-accent);color:#060b14;border:none;border-radius:12px;padding:13px;font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;letter-spacing:0.8px;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;}
        .auth-btn:hover{background:#00e07a;box-shadow:0 0 24px rgba(0,255,136,0.4);transform:translateY(-1px);}
        .auth-switch{text-align:center;margin-top:20px;font-size:13px;color:var(--tg-secondary);}
        .auth-switch a{color:var(--tg-accent);text-decoration:none;font-weight:600;}
        .auth-switch a:hover{text-decoration:underline;}
        .form-group{margin-bottom:16px;}
        .plan-card{border:1px solid var(--tg-border);border-radius:10px;padding:14px;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:10px;}
        .plan-card:hover,.plan-card.selected{border-color:var(--tg-accent);background:var(--tg-accent-dim,rgba(0,255,136,0.05));}
        .plan-card input{accent-color:var(--tg-accent);}
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
        <div class="auth-card-title">Create Account</div>
        <div class="auth-card-sub">Start protecting your infrastructure in minutes</div>

        @if($errors->any())
        <div style="background:rgba(255,51,102,0.08);border:1px solid rgba(255,51,102,0.2);border-radius:10px;padding:12px 15px;color:var(--tg-critical);font-size:13px;margin-bottom:18px">
            <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="auth-label">Full Name</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-user auth-input-icon"></i>
                        <input type="text" name="name" class="auth-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               placeholder="Elijah Emmanuel" value="{{ old('name') }}" required>
                    </div>
                    @error('name')<div class="auth-error">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="auth-label">Organization Name</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-building auth-input-icon"></i>
                        <input type="text" name="organization_name" class="auth-input {{ $errors->has('organization_name') ? 'is-invalid' : '' }}"
                               placeholder="Torex Labs" value="{{ old('organization_name') }}" required>
                    </div>
                    @error('organization_name')<div class="auth-error">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="auth-label">Email Address</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope auth-input-icon"></i>
                        <input type="email" name="email" class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               placeholder="you@company.com" value="{{ old('email') }}" required>
                    </div>
                    @error('email')<div class="auth-error">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="auth-label">Password</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock auth-input-icon"></i>
                        <input type="password" name="password" class="auth-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="Min. 8 characters" required>
                    </div>
                    @error('password')<div class="auth-error">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="auth-label">Confirm Password</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock auth-input-icon"></i>
                        <input type="password" name="password_confirmation" class="auth-input" placeholder="Repeat password" required>
                    </div>
                </div>

                <div class="col-12">
                    <div style="background:rgba(0,255,136,0.04);border:1px solid rgba(0,255,136,0.12);border-radius:10px;padding:14px;font-size:12px;color:var(--tg-secondary);line-height:1.6">
                        <i class="fas fa-shield-check text-accent me-2" style="color:var(--tg-accent)"></i>
                        By creating an account you agree to our Terms of Service and Privacy Policy.
                        Your free plan includes 1 server, 3 projects, and real-time monitoring.
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="auth-btn">
                        <i class="fas fa-rocket"></i> Create Account
                    </button>
                </div>
            </div>
        </form>

        <div class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
    </div>
</div>
</body>
</html>
