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
        return view('live.show', $this->presenter->viewData(requirePublicVisibility: true));
    }

    public function status(): JsonResponse
    {
        $data = $this->presenter->viewData(requirePublicVisibility: true);

        return response()->json([
            'live' => $data['streamLive'],
            'hasPlayerConfig' => $data['hasPlayerConfig'],
            'playbackMode' => $data['playbackMode'],
            'html' => ($data['hasPlayerConfig'] ?? false)
                ? view('live.partials.player-inner', $data)->render()
                : null,
        ]);
    }
}
