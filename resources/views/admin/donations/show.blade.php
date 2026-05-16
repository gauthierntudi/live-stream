@extends('layouts.admin')

@section('title', 'Paiement '.$donation->public_id)

@section('content')
    <p style="margin:0 0 1rem;">
        <a class="btn btn-secondary" href="{{ route('admin.donations.index') }}">&larr; Retour aux paiements</a>
    </p>

    <div class="table-card" style="padding:1.15rem;">
        @include('admin.donations.partials.modal-content', ['donation' => $donation])
    </div>
@endsection
