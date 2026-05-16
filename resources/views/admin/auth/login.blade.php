<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Dashboard</title>
    @include('partials.site-favicon')
    @include('partials.stream-font-faces')
    <style>
        :root {
            --muted: #9ca3af;
            --accent: #e50914;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            font-family: var(--font-stream-body, "Montserrat", system-ui, sans-serif);
            background: #0b0b0f;
            color: #f3f4f6;
            padding: 1.5rem;
        }
        .login-brand {
            text-decoration: none;
        }
        .login-card {
            width: min(100%, 380px);
            background: #1f2937;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 2rem;
        }
        .login-card h2 { margin: 0 0 0.35rem; font-size: 1.1rem; font-weight: 600; }
        .login-card p { margin: 0 0 1.5rem; color: var(--muted); font-size: 0.9rem; }
        label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.35rem; }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.75rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.12);
            background: #0b0b0f;
            color: #fff;
            font-family: inherit;
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .error { color: #fca5a5; font-size: 0.85rem; margin-bottom: 1rem; }
        .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.85rem; color: var(--muted); }
    </style>
    @include('partials.stream-brand-styles')
</head>
<body>
    <a href="{{ route('home') }}" class="brand-wrap login-brand">
        @include('partials.stream-brand')
    </a>
    <div class="login-card">
        <h2>Dashboard</h2>
        <p>Connectez-vous pour gérer les paiements et le live.</p>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('admin.login') }}">
            @csrf
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            <label class="remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Se souvenir de moi
            </label>
            <button type="submit" class="btn">Se connecter</button>
        </form>
    </div>
    @vite(['resources/js/brand-typewriter.js'])
</body>
</html>
