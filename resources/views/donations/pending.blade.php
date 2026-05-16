@extends('layouts.stream')

@section('title', ($title ?? 'En attente').' — '.config('app.name'))

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
    .thanks h1 { font-size: 1.75rem; margin-bottom: 0.5rem; color: #fcd34d; }
    .thanks p { color: var(--muted); line-height: 1.6; }
    .ref { margin-top: 1rem; font-family: ui-monospace, monospace; font-size: 0.9rem; color: #e5e7eb; }
    .instr { margin-top: 1rem; padding: 1rem; border-radius: 12px; background: rgba(245, 158, 11, 0.12); color: #fde68a; text-align: left; }
</style>
@endpush

@section('content')
    <div class="thanks">
        <h1>Paiement en attente</h1>
        <p>Valide la demande sur ton téléphone si tu utilises Mobile Money, ou suis la page sécurisée pour finaliser un paiement par carte.</p>
        @if (! empty($pendingInstructions))
            <div class="instr">{{ $pendingInstructions }}</div>
        @endif
        @if ($donation)
            <p class="ref">Référence : {{ $donation->public_id }} — statut : {{ $donation->status }}</p>
        @endif
        <p style="margin-top:1.5rem;">
            <a class="btn btn-primary" href="{{ route('live.show') }}"><i data-lucide="circle-play" aria-hidden="true"></i> Retour au live</a>
        </p>
    </div>
@endsection
