@extends('layouts.stream')

@section('title', ($title ?? 'Échec').' — '.config('app.name'))

@section('nav_actions')
    <a class="btn btn-secondary" href="{{ route('home') }}"><i data-lucide="home" aria-hidden="true"></i><span class="nav-btn-label">Accueil</span></a>
    <a class="btn btn-primary" href="{{ route('donations.index') }}"><i data-lucide="heart" aria-hidden="true"></i><span class="nav-btn-label">Réessayer</span></a>
@endsection

@push('styles')
<style>
    .thanks {
        max-width: 560px;
        margin: 3rem auto;
        padding: 0 4vw;
        text-align: center;
    }
    .thanks h1 { font-size: 1.75rem; margin-bottom: 0.5rem; color: #fca5a5; }
    .thanks p { color: var(--muted); line-height: 1.6; }
    .ref { margin-top: 1rem; font-family: ui-monospace, monospace; font-size: 0.9rem; color: #e5e7eb; }
</style>
@endpush

@section('content')
    <div class="thanks">
        <h1>Paiement non abouti</h1>
        <p>La transaction n’a pas pu être finalisée. Tu peux réessayer depuis la page Soutenir.</p>
        @if ($donation)
            <p class="ref">Référence : {{ $donation->public_id }}</p>
        @endif
        <p style="margin-top:1.5rem;">
            <a class="btn btn-primary" href="{{ route('donations.index') }}"><i data-lucide="heart" aria-hidden="true"></i> Soutenir à nouveau</a>
        </p>
    </div>
@endsection
