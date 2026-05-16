@extends('layouts.stream')

@section('title', 'Merci — '.config('app.name'))

@section('nav_actions')
    <a class="btn btn-secondary" href="{{ route('home') }}"><i data-lucide="home" aria-hidden="true"></i><span class="nav-btn-label">Accueil</span></a>
    <a class="btn btn-primary" href="{{ route('live.show') }}"><i data-lucide="tv-minimal-play" aria-hidden="true"></i><span class="nav-btn-label">Live</span></a>
@endsection

@push('styles')
<style>
    .thanks {
        max-width: 560px;
        margin: 3rem auto;
        padding: 0 4vw;
        text-align: center;
    }
    .thanks h1 { font-size: 1.75rem; margin-bottom: 0.5rem; }
    .thanks p { color: var(--muted); line-height: 1.6; }
    .ref { margin-top: 1rem; font-family: ui-monospace, monospace; font-size: 0.9rem; color: #e5e7eb; }
</style>
@endpush

@section('content')
    <div class="thanks">
        <h1>Merci</h1>
        <p>
            Si tu viens de payer, la confirmation peut prendre quelques instants
            La confirmation peut prendre quelques instants. Tu peux revenir au live quand tu veux.
        </p>
        @if ($donation)
            @php
                $pmRow = collect(config('payment_methods.methods'))->firstWhere('id', $donation->payment_method);
                $pmLabel = is_array($pmRow) && isset($pmRow['label']) ? $pmRow['label'] : $donation->payment_method;
            @endphp
            <p class="ref">Référence : {{ $donation->public_id }} — statut : {{ $donation->status }}@if ($donation->payment_method) — paiement : {{ $pmLabel }}@endif</p>
        @endif
        <p style="margin-top:1.5rem;">
            <a class="btn btn-primary" href="{{ route('live.show') }}"><i data-lucide="circle-play" aria-hidden="true"></i> Retour au live</a>
        </p>
    </div>
@endsection
