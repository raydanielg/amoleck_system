<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ \App\Support\Settings::get('clinic_name', 'Amoleck') }}</title>
    <link href="https://cdn.hugeicons.com/font/hgi-stroke-rounded.css" rel="stylesheet">
    <style>
        * { margin: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #fafafa; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #18181b; padding: 1.5rem;
        }
        .err-card { max-width: 26rem; width: 100%; text-align: center; }
        .err-badge {
            display: inline-grid; place-items: center; width: 4.5rem; height: 4.5rem;
            border-radius: 1.25rem; background: #fff; border: 1px solid #e4e4e7;
            box-shadow: 0 8px 24px -12px rgb(0 0 0 / .15); margin-bottom: 1.5rem;
        }
        .err-badge i { font-size: 1.9rem; color: #16834A; }
        .err-code { font-size: 4.5rem; font-weight: 800; letter-spacing: -0.04em; line-height: 1; color: #18181b; }
        .err-code span { color: #16834A; }
        .err-title { font-size: 1.125rem; font-weight: 600; margin-top: .5rem; }
        .err-msg { color: #71717a; font-size: .875rem; line-height: 1.6; margin-top: .5rem; }
        .err-actions { display: flex; gap: .75rem; justify-content: center; margin-top: 1.75rem; }
        .btn {
            display: inline-flex; align-items: center; gap: .4rem; padding: .6rem 1.25rem;
            border-radius: .5rem; font-size: .875rem; font-weight: 500; text-decoration: none;
            border: 1px solid transparent; transition: all .15s;
        }
        .btn-dark { background: #16834A; color: #fff; } .btn-dark:hover { background: #11663B; }
        .btn-light { background: #fff; color: #18181b; border-color: #e4e4e7; } .btn-light:hover { border-color: #a1a1aa; }
        .err-foot { margin-top: 2.5rem; font-size: .75rem; color: #a1a1aa; }
    </style>
</head>
<body>
    <div class="err-card">
        <div class="err-badge"><i class="hgi-stroke @yield('icon')"></i></div>
        <div class="err-code">{!! $__env->yieldContent('code') !!}</div>
        <div class="err-title">@yield('title')</div>
        <div class="err-msg">@yield('message')</div>
        <div class="err-actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-light"><i class="hgi-stroke hgi-arrow-left-01"></i>Go back</a>
            <a href="{{ url('/') }}" class="btn btn-dark"><i class="hgi-stroke hgi-home-01"></i>Home</a>
        </div>
        <div class="err-foot">{{ \App\Support\Settings::get('clinic_name', 'Amoleck Group Company LTD') }}</div>
    </div>
</body>
</html>
