<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @include('partials.site-favicon')
    @include('partials.stream-font-faces')
    <style>
        :root {
            --bg: #0b0b0f;
            --bg-elevated: #14141f;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --accent: #e50914;
            --card: #1f2937;
            --border: rgba(255, 255, 255, 0.08);
            --success: #22c55e;
            --danger: #ef4444;
            --info: #38bdf8;
            --warning: #f59e0b;
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
        .admin-shell { min-height: 100vh; display: flex; flex-direction: column; }
        .admin-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 4vw;
            border-bottom: 1px solid var(--border);
            background: var(--bg-elevated);
        }
        .admin-top-brand {
            flex: 0 1 auto;
            min-width: 0;
        }
        .admin-top-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .admin-top-actions form {
            margin: 0;
            display: flex;
        }
        .admin-top-actions .btn svg {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
        }
        @media (max-width: 640px) {
            .admin-top {
                flex-wrap: nowrap;
                gap: 0.65rem;
                padding: 0.75rem 4vw;
            }
            .admin-top-brand .brand {
                font-size: clamp(1rem, 4vw, 1.25rem);
            }
            .admin-top-brand .brand-tagline {
                font-size: 0.5rem;
                letter-spacing: 0.12em;
            }
            .admin-top-actions {
                flex-wrap: nowrap;
                gap: 0.5rem;
                flex-shrink: 0;
            }
            .admin-top-actions .admin-top-btn-label {
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
            .admin-top-actions .btn {
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
            .admin-top-actions .btn svg {
                width: 1.35rem;
                height: 1.35rem;
            }
        }
        @media (max-width: 380px) {
            .admin-top-actions .btn {
                width: 2.6rem;
                height: 2.6rem;
            }
            .admin-top-actions .btn svg {
                width: 1.2rem;
                height: 1.2rem;
            }
        }
        .admin-tabs {
            display: flex;
            gap: 0.35rem;
            padding: 0 4vw;
            border-bottom: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.2);
        }
        .admin-tab {
            padding: 0.85rem 1.1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--muted);
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        .admin-tab:hover { color: var(--text); }
        .admin-tab.is-active {
            color: #fff;
            border-bottom-color: var(--accent);
        }
        .admin-main { flex: 1; padding: 1.5rem 4vw 3rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid var(--border);
        }
        .btn-gold {
            background: linear-gradient(135deg, #fbbf24, #d97706);
            color: #111827;
        }
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert--status {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.35);
            color: #bbf7d0;
        }
        .alert--error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fecaca;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(172px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            padding: 1.1rem 1.15rem;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stat-card:hover {
            border-color: rgba(255, 255, 255, 0.14);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }
        .stat-card__icon {
            flex-shrink: 0;
            width: 2.65rem;
            height: 2.65rem;
            border-radius: 10px;
            display: grid;
            place-items: center;
        }
        .stat-card__icon svg {
            width: 1.3rem;
            height: 1.3rem;
            stroke-width: 2;
        }
        .stat-card__body {
            min-width: 0;
            flex: 1;
        }
        .stat-card__label {
            font-size: 0.7rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
            line-height: 1.3;
        }
        .stat-card__value {
            font-size: 1.45rem;
            font-weight: 700;
            margin-top: 0.35rem;
            line-height: 1.15;
            color: #fff;
        }
        .stat-card__value--sm {
            font-size: 0.95rem;
            font-weight: 600;
        }
        .stat-card__value--mono {
            font-size: 0.78rem;
            font-weight: 500;
            font-family: ui-monospace, monospace;
            word-break: break-all;
        }
        .stat-card--default .stat-card__icon {
            background: rgba(156, 163, 175, 0.14);
            color: #e5e7eb;
        }
        .stat-card--success .stat-card__icon {
            background: rgba(34, 197, 94, 0.16);
            color: #86efac;
        }
        .stat-card--warning .stat-card__icon {
            background: rgba(245, 158, 11, 0.16);
            color: #fcd34d;
        }
        .stat-card--danger .stat-card__icon {
            background: rgba(239, 68, 68, 0.16);
            color: #fca5a5;
        }
        .stat-card--info .stat-card__icon {
            background: rgba(56, 189, 248, 0.16);
            color: #7dd3fc;
        }
        .stat-card--gold .stat-card__icon {
            background: rgba(251, 191, 36, 0.16);
            color: #fcd34d;
        }
        .stat-card--accent .stat-card__icon {
            background: rgba(229, 9, 20, 0.18);
            color: #fca5a5;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem 1rem;
            margin-bottom: 1rem;
            align-items: flex-end;
            padding: 1rem 1.15rem;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.01) 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        .filters-bar label {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.7rem;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .filters-bar__field {
            position: relative;
        }
        .filters-bar__field--grow {
            flex: 1 1 14rem;
            min-width: 12rem;
        }
        .filters-bar input,
        .filters-bar select {
            width: 100%;
            padding: 0.55rem 0.75rem;
            padding-left: 2.15rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(11, 11, 15, 0.65);
            color: var(--text);
            font-family: inherit;
            font-size: 0.875rem;
            min-width: 10rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .filters-bar select {
            padding-left: 0.75rem;
            cursor: pointer;
        }
        .filters-bar input:focus,
        .filters-bar select:focus {
            outline: none;
            border-color: rgba(229, 9, 20, 0.45);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.12);
        }
        .filters-bar__icon {
            position: absolute;
            left: 0.65rem;
            bottom: 0.62rem;
            color: var(--muted);
            pointer-events: none;
            display: flex;
        }
        .filters-bar__icon svg {
            width: 1rem;
            height: 1rem;
        }
        .filters-bar__actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
        .table-card {
            background: linear-gradient(180deg, rgba(31, 41, 55, 0.55) 0%, rgba(20, 20, 31, 0.9) 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.22);
        }
        .table-card__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.9rem 1.15rem;
            border-bottom: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.22);
        }
        .table-card__title {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .table-card__meta {
            font-size: 0.8rem;
            color: var(--muted);
        }
        .table-wrap {
            overflow-x: auto;
            background: rgba(11, 11, 15, 0.45);
        }
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }
        .data-table thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .data-table th {
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #d1d5db;
            font-weight: 600;
            background: rgba(0, 0, 0, 0.35);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }
        .data-table td {
            padding: 0.9rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }
        .data-table tbody tr {
            transition: background 0.12s ease;
        }
        .data-table tbody tr:nth-child(even) td {
            background: rgba(255, 255, 255, 0.02);
        }
        .data-table tbody tr:hover td {
            background: rgba(229, 9, 20, 0.06);
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        .data-table__donor strong {
            display: block;
            font-weight: 600;
        }
        .data-table__donor span {
            color: var(--muted);
            font-size: 0.8rem;
        }
        .data-table__amount {
            font-weight: 600;
            white-space: nowrap;
        }
        .data-table__method {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: rgba(255, 255, 255, 0.06);
            color: #e5e7eb;
        }
        .payment-method-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
            border-radius: 5px;
            overflow: hidden;
        }
        .payment-method-icon img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            object-fit: contain;
            border-radius: 5px;
        }
        .payment-method-icon--md img {
            max-height: 1.75rem;
            max-width: 3.5rem;
        }
        .payment-method-icon--sm img {
            max-height: 1.35rem;
            max-width: 2.75rem;
        }
        .payment-method-icon--lg img {
            max-height: 2.25rem;
            max-width: 4.5rem;
        }
        .payment-method-icon--fallback {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--muted);
            padding: 0.2rem 0.45rem;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.06);
        }
        .data-table td.data-table__method-cell {
            text-align: center;
        }
        .data-table__empty td {
            text-align: center;
            color: var(--muted);
            padding: 2.5rem 1rem;
            background: transparent !important;
        }
        .btn-table {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 6px;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge--success { background: rgba(34,197,94,0.2); color: #86efac; }
        .badge--danger { background: rgba(239,68,68,0.2); color: #fca5a5; }
        .badge--info { background: rgba(56,189,248,0.2); color: #7dd3fc; }
        .badge--muted { background: rgba(156,163,175,0.2); color: #d1d5db; }
        .table-card__footer {
            padding: 0.85rem 1rem;
            border-top: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.2);
        }
        .table-card__footer--pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
        }
        .pagination-summary {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .pagination-summary strong {
            color: var(--text);
            font-weight: 600;
        }
        .pagination-nav {
            flex: 1 1 auto;
            display: flex;
            justify-content: flex-end;
            min-width: min(100%, 16rem);
        }
        .pagination-nav .pagination {
            justify-content: flex-end;
        }
        .pagination__ellipsis {
            padding: 0.45rem 0.35rem;
            color: var(--muted);
            font-size: 0.85rem;
            border: none;
            user-select: none;
        }
        .pagination__disabled {
            opacity: 0.35;
            cursor: default;
            pointer-events: none;
        }
        .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: center;
            margin: 0;
        }
        .pagination a,
        .pagination span {
            padding: 0.45rem 0.7rem;
            border-radius: 8px;
            font-size: 0.85rem;
            border: 1px solid transparent;
        }
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--border);
        }
        .pagination .current {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .pagination span:not(.current) {
            color: var(--muted);
        }
        .mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
        .copy-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.35rem;
        }
        .copy-row code {
            flex: 1;
            padding: 0.5rem 0.65rem;
            background: var(--bg);
            border-radius: 6px;
            font-size: 0.8rem;
            word-break: break-all;
        }
        .detail-grid {
            display: grid;
            gap: 1rem;
        }
        @media (min-width: 720px) {
            .detail-grid { grid-template-columns: 1fr 1fr; }
        }
        .detail-grid dt {
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 0.15rem;
        }
        .detail-grid dd {
            margin: 0 0 0.75rem;
        }
        pre.payload {
            background: var(--bg);
            padding: 1rem;
            border-radius: 8px;
            overflow: auto;
            font-size: 0.75rem;
            max-height: 24rem;
        }
        .donation-modal {
            width: min(100vw - 2rem, 36rem);
            max-height: min(90vh, 720px);
            padding: 0;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            background: linear-gradient(165deg, #1a1f2e 0%, #0f1117 55%, #14141f 100%);
            color: var(--text);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.55);
            overflow: hidden;
        }
        .donation-modal::backdrop {
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(6px);
        }
        .donation-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.15rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
            background: #f80404;
        }
        .donation-modal__title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
        }
        .donation-modal__close {
            display: grid;
            place-items: center;
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            cursor: pointer;
            flex-shrink: 0;
        }
        .donation-modal__close:hover {
            background: rgba(255, 255, 255, 0.22);
        }
        .donation-modal__close svg {
            width: 1.1rem;
            height: 1.1rem;
        }
        .donation-modal__body {
            padding: 1.15rem;
            overflow-y: auto;
            max-height: calc(min(90vh, 720px) - 4rem);
        }
        .donation-modal.is-loading .donation-modal__body {
            opacity: 0.65;
        }
        .donation-modal__loading,
        .donation-modal__error {
            text-align: center;
            color: var(--muted);
            padding: 2rem 1rem;
            margin: 0;
        }
        .donation-modal__error { color: #fca5a5; }
        .donation-modal__hero {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.28);
            border: 1px solid var(--border);
        }
        .donation-modal__amount {
            margin: 0.5rem 0 0.2rem;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .donation-modal__amount span {
            font-size: 1rem;
            font-weight: 600;
            color: var(--muted);
        }
        .donation-modal__meta {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .donation-modal__grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            margin: 0;
        }
        @media (max-width: 520px) {
            .donation-modal__grid { grid-template-columns: 1fr; }
        }
        .donation-modal__item {
            margin: 0;
            padding: 0.75rem 0.85rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .donation-modal__item--wide {
            grid-column: 1 / -1;
        }
        .donation-modal__item dt {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .donation-modal__item dt svg {
            width: 0.9rem;
            height: 0.9rem;
            opacity: 0.85;
        }
        .donation-modal__item dd {
            margin: 0;
            font-size: 0.9rem;
            word-break: break-word;
        }
        .donation-modal__item dd a {
            color: #93c5fd;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .donation-modal__payload {
            margin-top: 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .donation-modal__payload summary {
            padding: 0.75rem 1rem;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            list-style: none;
        }
        .donation-modal__payload summary::-webkit-details-marker { display: none; }
        .donation-modal__payload .payload {
            margin: 0;
            border-radius: 0;
            max-height: 14rem;
        }
    </style>
    @include('partials.stream-brand-styles')
    @stack('styles')
</head>
<body>
    <div class="admin-shell">
        <header class="admin-top">
            <a href="{{ route('admin.donations.index') }}" class="brand-wrap admin-top-brand">
                @include('partials.stream-brand')
            </a>
            <div class="admin-top-actions">
                <a
                    class="btn btn-secondary"
                    href="{{ route('home') }}"
                    target="_blank"
                    rel="noopener"
                    aria-label="Voir le site"
                >
                    <i data-lucide="globe" aria-hidden="true"></i>
                    <span class="admin-top-btn-label">Voir le site</span>
                </a>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary" aria-label="Déconnexion">
                        <i data-lucide="log-out" aria-hidden="true"></i>
                        <span class="admin-top-btn-label">Déconnexion</span>
                    </button>
                </form>
            </div>
        </header>
        @auth
            <nav class="admin-tabs" aria-label="Sections du dashboard">
                <a
                    class="admin-tab {{ request()->routeIs('admin.donations.*') ? 'is-active' : '' }}"
                    href="{{ route('admin.donations.index') }}"
                >Paiements</a>
                <a
                    class="admin-tab {{ request()->routeIs('admin.live.*') ? 'is-active' : '' }}"
                    href="{{ route('admin.live.index') }}"
                >Live</a>
            </nav>
        @endauth
        <main class="admin-main">
            @if (session('status'))
                <div class="alert alert--status" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--error" role="alert">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    @vite(['resources/js/stream-icons.js', 'resources/js/brand-typewriter.js'])
    @stack('scripts')
</body>
</html>
