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
    <body class="layout-fixed sidebar-expand-lg sidebar-mini bg-body-tertiary">
        @include('partials.toasts')

        <div class="app-wrapper">
            @include('layouts.navigation')
            @include('layouts.sidebar')

            <main class="app-main">
                @isset($header)
                    <div class="app-content-header">
                        <div class="container-fluid">
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">{{ $header }}</h4>
                                @isset($headerActions)
                                    <div>{{ $headerActions }}</div>
                                @endisset
                            </div>
                        </div>
                    </div>
                @endisset

                <div class="app-content">
                    <div class="container-fluid">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="app-footer text-center text-muted small py-2">
                {{ \App\Support\Settings::get('clinic_name', config('app.name')) }}
            </footer>
        </div>
        @stack('scripts')
    </body>
</html>
