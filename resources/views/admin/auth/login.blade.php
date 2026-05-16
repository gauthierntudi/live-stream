<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Connexion — Dashboard</title>
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
            --border: rgba(255, 255, 255, 0.08);
            --border-focus: rgba(229, 9, 20, 0.55);
            --glass: rgba(20, 20, 31, 0.72);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: var(--font-stream-body, "Montserrat", system-ui, sans-serif);
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        .login-scene {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: max(1.5rem, env(safe-area-inset-top)) max(1.25rem, env(safe-area-inset-right))
                max(1.5rem, env(safe-area-inset-bottom)) max(1.25rem, env(safe-area-inset-left));
            overflow: hidden;
        }
        .login-scene__bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(ellipse 90% 70% at 15% -10%, rgba(229, 9, 20, 0.22) 0%, transparent 55%),
                radial-gradient(ellipse 70% 50% at 100% 100%, rgba(245, 158, 11, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(255, 255, 255, 0.03) 0%, transparent 70%),
                var(--bg);
        }
        .login-scene__grid {
            position: absolute;
            inset: 0;
            opacity: 0.35;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, #000 20%, transparent 75%);
        }
        .login-scene__inner {
            position: relative;
            z-index: 1;
            width: min(100%, 26rem);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 1.75rem;
            animation: login-enter 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes login-enter {
            from {
                opacity: 0;
                transform: translateY(1.25rem);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-back {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            align-self: flex-start;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .login-back:hover { color: var(--text); }
        .login-back svg { width: 1rem; height: 1rem; flex-shrink: 0; }
        .login-brand {
            text-decoration: none;
            align-self: center;
        }
        .login-brand .brand { justify-content: center; }
        .login-brand .brand-wrap { align-items: center; text-align: center; }
        .login-card {
            position: relative;
            padding: 2rem 1.75rem 1.75rem;
            border-radius: 1.25rem;
            background: var(--glass);
            border: 1px solid var(--border);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.03) inset,
                0 1.5rem 3.5rem -1rem rgba(0, 0, 0, 0.55),
                0 0 4rem -1rem rgba(229, 9, 20, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1.5rem;
            right: 1.5rem;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(229, 9, 20, 0.65), transparent);
            border-radius: 1px;
        }
        .login-card__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 1rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #fca5a5;
            background: rgba(229, 9, 20, 0.12);
            border: 1px solid rgba(229, 9, 20, 0.28);
            border-radius: 999px;
        }
        .login-card__badge svg { width: 0.85rem; height: 0.85rem; }
        .login-card h1 {
            margin: 0 0 0.4rem;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .login-card__lead {
            margin: 0 0 1.5rem;
            font-size: 0.9rem;
            line-height: 1.55;
            color: var(--muted);
        }
        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
            padding: 0.75rem 0.9rem;
            font-size: 0.85rem;
            line-height: 1.45;
            color: #fecaca;
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: 0.65rem;
        }
        .login-alert svg {
            width: 1.1rem;
            height: 1.1rem;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
        .login-field {
            margin-bottom: 1.1rem;
        }
        .login-field label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .login-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .login-input-wrap__icon {
            position: absolute;
            left: 0.85rem;
            display: flex;
            color: var(--muted);
            pointer-events: none;
        }
        .login-input-wrap__icon svg { width: 1.1rem; height: 1.1rem; }
        .login-input-wrap input {
            width: 100%;
            padding: 0.8rem 0.9rem 0.8rem 2.65rem;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text);
            background: rgba(11, 11, 15, 0.65);
            border: 1px solid var(--border);
            border-radius: 0.65rem;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .login-input-wrap input::placeholder { color: #6b7280; }
        .login-input-wrap input:hover {
            border-color: rgba(255, 255, 255, 0.14);
        }
        .login-input-wrap input:focus {
            border-color: var(--border-focus);
            background: rgba(11, 11, 15, 0.9);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.18);
        }
        .login-remember {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 1.35rem;
            font-size: 0.875rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }
        .login-remember input {
            width: 1rem;
            height: 1rem;
            margin: 0;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .login-submit {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: inherit;
            color: #fff;
            border: none;
            border-radius: 0.65rem;
            cursor: pointer;
            background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
            box-shadow: 0 4px 1.25rem rgba(229, 9, 20, 0.35);
            transition: transform 0.15s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        .login-submit:hover {
            filter: brightness(1.06);
            box-shadow: 0 6px 1.5rem rgba(229, 9, 20, 0.45);
        }
        .login-submit:active { transform: scale(0.98); }
        .login-submit:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.55);
            outline-offset: 2px;
        }
        .login-submit svg { width: 1.15rem; height: 1.15rem; }
        .login-foot {
            margin: 1.25rem 0 0;
            padding-top: 1.15rem;
            border-top: 1px solid var(--border);
            font-size: 0.78rem;
            line-height: 1.5;
            text-align: center;
            color: #6b7280;
        }
        @media (prefers-reduced-motion: reduce) {
            .login-scene__inner { animation: none; }
            .login-submit { transition: none; }
        }
    </style>
    @include('partials.stream-brand-styles')
</head>
<body>
    <div class="login-scene">
        <div class="login-scene__bg" aria-hidden="true">
            <div class="login-scene__grid"></div>
        </div>

        <div class="login-scene__inner">
            <a href="{{ route('home') }}" class="login-back">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Retour au site
            </a>

            <a href="{{ route('home') }}" class="brand-wrap login-brand">
                @include('partials.stream-brand')
            </a>

            <div class="login-card">
                <span class="login-card__badge">
                    <i data-lucide="shield-check" aria-hidden="true"></i>
                    Espace sécurisé
                </span>
                <h1>Connexion</h1>
                <p class="login-card__lead">Gérez les paiements et la diffusion live depuis le tableau de bord.</p>

                @if ($errors->any())
                    <div class="login-alert" role="alert">
                        <i data-lucide="circle-x" aria-hidden="true"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="post" action="{{ route('admin.login') }}" novalidate>
                    @csrf
                    <div class="login-field">
                        <label for="email">E-mail</label>
                        <div class="login-input-wrap">
                            <span class="login-input-wrap__icon" aria-hidden="true">
                                <i data-lucide="mail"></i>
                            </span>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="vous@exemple.com"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="password">Mot de passe</label>
                        <div class="login-input-wrap">
                            <span class="login-input-wrap__icon" aria-hidden="true">
                                <i data-lucide="lock"></i>
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                    </div>
                    <label class="login-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Se souvenir de moi
                    </label>
                    <button type="submit" class="login-submit">
                        <i data-lucide="sparkles" aria-hidden="true"></i>
                        Se connecter
                    </button>
                </form>

                <p class="login-foot">Accès réservé aux administrateurs autorisés.</p>
            </div>
        </div>
    </div>

    @vite(['resources/js/brand-typewriter.js', 'resources/js/stream-icons.js'])
</body>
</html>
