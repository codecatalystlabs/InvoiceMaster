<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    <header class="app-header">
        <a class="app-brand" href="{{ route('login') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Code Catalyst Labs">
            <span class="app-brand-text">
                <strong>{{ config('app.name') }}</strong>
                <small>Code Catalyst Labs</small>
            </span>
        </a>
    </header>
    <div class="app-menubar no-print">
        <a href="{{ route('login') }}"><u>F</u>ile</a>
        <a href="{{ route('login') }}"><u>H</u>elp</a>
    </div>
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-card-top">
                <div>
                    <h1>@yield('title')</h1>
                    <p>@yield('subtitle')</p>
                </div>
                <div class="auth-logo-box">
                    <img src="{{ asset('images/logo.png') }}" alt="Code Catalyst Labs">
                    <div>
                        <span>Workspace</span>
                        <strong>Code Catalyst Labs</strong>
                    </div>
                </div>
            </div>
            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
            @yield('content')
        </div>
    </div>
    <footer class="app-statusbar no-print">
        <span class="sb-cell">Ready</span>
        <span class="sb-cell">Sign in to continue</span>
        <span class="sb-cell sb-end">{{ config('app.name') }}</span>
    </footer>
</div>
</body>
</html>
