<?php

namespace App\Http\Controllers;

use App\Support\LivePlayerPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function __construct(
        private LivePlayerPresenter $presenter,
    ) {}

    public function show(): View
    {
        $data = $this->presenter->viewData(requirePublicVisibility: true);
        $initialHash = '';
        if ($data['hasPlayerConfig'] ?? false) {
            $inner = view('live.partials.player-inner', $data)->render();
            $initialHash = hash('xxh128', $inner);
        }

        return view('live.show', array_merge($data, [
            'initialPlayerInnerHash' => $initialHash,
        ]));
    }

    public function status(): JsonResponse
    {
        return response()->json($this->presenter->statusJsonPayload(requirePublicVisibility: true));
    }
}
