<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ ($company ?? $invoice->company ?? null)?->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php $company = $company ?? $invoice->company ?? $client->company ?? null; @endphp
<div class="container py-4" style="max-width:720px;">
    <div class="text-center mb-4">
        <img src="{{ ($company ?? null)?->logoUrl() ?? asset('images/logo.png') }}" alt="" style="max-height:56px;">
        <div class="fw-semibold mt-2">{{ $company->name ?? config('app.name') }}</div>
    </div>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    @yield('content')
</div>
</body>
</html>
