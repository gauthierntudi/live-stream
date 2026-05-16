<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.site-favicon')
    @include('partials.stream-font-faces')
    <style>
        :root {
            --bg: #0b0b0f;
            --bg-elevated: #14141f;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --accent: #e50914;
            --accent-2: #f59e0b;
            --card: #1f2937;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-stream-body, "Montserrat", system-ui, sans-serif);
            background: var(--bg);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }
        .nav {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem 0.75rem;
            padding: 0.75rem 4vw;
            background: linear-gradient(180deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
        }
        .nav-links {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem 0.75rem;
            flex: 0 0 auto;
            margin-left: auto;
            flex-shrink: 0;
        }
        .nav .btn {
            flex-shrink: 0;
        }
        .nav .btn:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.55);
            outline-offset: 2px;
        }
        @media (min-width: 641px) {
            .nav .btn {
                padding: 0.7rem 1.35rem;
                min-height: 2.9rem;
                font-size: 1.05rem;
            }
        }
        @media (max-width: 640px) {
            .nav .nav-btn-label {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border-width: 0;
            }
            .nav .btn {
                position: relative;
                width: 2.875rem;
                height: 2.875rem;
                min-height: 0;
                padding: 0;
                border-radius: 50%;
                gap: 0;
                font-size: 0;
                line-height: 0;
            }
            .nav .btn svg {
                width: 1.35rem;
                height: 1.35rem;
            }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 1rem 2rem;
            min-height: 3.375rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.125rem;
            line-height: 1.2;
            border: none;
            cursor: pointer;
            transition: transform 0.12s ease, filter 0.12s ease;
        }
        .btn--block {
            width: 100%;
        }
        .btn svg {
            width: 1.2em;
            height: 1.2em;
            flex-shrink: 0;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-secondary {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.18);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.18); }
        .btn-gold { background: linear-gradient(135deg, #fbbf24, #d97706); color: #111827; }
        .btn-gold:hover { filter: brightness(1.05); }
        main { padding-bottom: 3rem; }
        .shell { padding: 0 4vw; }
        @media (max-width: 380px) {
            .btn {
                padding: 0.85rem 1.35rem;
                font-size: 1.05rem;
                min-height: 3rem;
            }
            .nav .btn {
                width: 2.6rem;
                height: 2.6rem;
            }
            .nav .btn svg {
                width: 1.2rem;
                height: 1.2rem;
            }
        }
        .alert {
            margin: 1rem 4vw;
            padding: 0.85rem 1rem;
            border-radius: 6px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fde68a;
            font-size: 0.9rem;
        }
    </style>
    @include('partials.stream-brand-styles')
    @stack('styles')
</head>
<body>
    @stack('page_loader')
    @stack('body_start')
    <header class="nav">
        <a href="{{ route('home') }}" class="brand-wrap">
            @include('partials.stream-brand')
        </a>
        <nav class="nav-links">
            @yield('nav_actions')
        </nav>
    </header>
    @if (session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    <main>
        @yield('content')
    </main>
    @vite(['resources/js/stream-icons.js', 'resources/js/brand-typewriter.js'])
    @stack('scripts')
</body>
</html>
