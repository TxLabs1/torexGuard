<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root { --tg-bg:#060b14; --tg-card:#0d1526; --tg-border:rgba(0,255,136,0.08); --tg-accent:#00ff88; --tg-text:#e8f0fe; --tg-muted:#4a6080; }
        body { background: var(--tg-bg); color: var(--tg-text); font-family: 'Inter', sans-serif; min-height:100vh; margin:0; }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
