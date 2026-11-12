<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo-icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo-icon.png') }}">

        <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-page bg-white">
        @include('partials.toasts')

        <div class="login-box">
            <div class="login-logo">
                <a href="/">
                    <img src="{{ asset('amoleck-logo.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="app-logo">
                </a>
            </div>

            <div class="card">
                <div class="card-body login-card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
