<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#0a2540">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo-icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo-icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <link rel="stylesheet" href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-page">
        @include('partials.toasts')

        <div class="auth-shell">
            <aside class="auth-brand d-none d-lg-flex">
                <div>
                    <span class="auth-brand-logo">
                        <img src="{{ asset('amoleck-logo.png') }}" alt="{{ config('app.name', 'Laravel') }}">
                    </span>
                </div>

                <div class="auth-brand-inner">
                    <h2 class="auth-brand-title">{{ __('Care, managed beautifully.') }}</h2>
                    <p class="auth-brand-desc">
                        {{ __('One secure workspace for patients, appointments, billing, HR, inventory and reporting — built for daily clinic operations.') }}
                    </p>

                    <ul class="list-unstyled mb-0">
                        <li class="auth-feature">
                            <i class="hgi-stroke hgi-hospital-01"></i>
                            <div>
                                <strong>{{ __('Complete clinic operations') }}</strong>
                                <span>{{ __('Reception, treatment, invoicing and follow-ups in one flow.') }}</span>
                            </div>
                        </li>
                        <li class="auth-feature">
                            <i class="hgi-stroke hgi-shield-user"></i>
                            <div>
                                <strong>{{ __('Role-based access control') }}</strong>
                                <span>{{ __('Every staff member sees only what their role allows.') }}</span>
                            </div>
                        </li>
                        <li class="auth-feature">
                            <i class="hgi-stroke hgi-analytics-02"></i>
                            <div>
                                <strong>{{ __('Real-time insight') }}</strong>
                                <span>{{ __('Financial, clinical and workforce reporting on demand.') }}</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="auth-brand-foot">
                    <i class="hgi-stroke hgi-security-check"></i>
                    <span>{{ __('Secure Healthcare Management Platform') }}</span>
                </div>
            </aside>

            <main class="auth-main">
                <div class="auth-card">
                    <a href="/" class="auth-card-logo d-lg-none">
                        <img src="{{ asset('amoleck-logo.png') }}" alt="{{ config('app.name', 'Laravel') }}">
                    </a>

                    {{ $slot }}

                    <div class="auth-secure">
                        <i class="hgi-stroke hgi-security-check"></i>
                        <span>{{ __('Secure Healthcare Management Platform') }}</span>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
