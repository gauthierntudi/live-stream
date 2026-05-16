<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\StreamingServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\StreamingServiceManager;
use App\Services\YoutubeEmbedStreamService;
use App\Support\LivePlayerPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class LiveController extends Controller
{
    public function __construct(
        protected StreamingServiceInterface $stream,
        protected StreamingServiceManager $streamingManager,
        protected LivePlayerPresenter $presenter,
    ) {}

    public function index(): View
    {
        return view('admin.live.index', $this->livePageVariables());
    }

    public function previewStatus(): JsonResponse
    {
        return response()->json($this->presenter->statusJsonPayload(requirePublicVisibility: false));
    }

    public function publish(Request $request): RedirectResponse|JsonResponse
    {
        if ($this->stream->resolveLiveInputUid() === null) {
            $msg = $this->stream->providerKey() === 'youtube'
                ? 'En mode YouTube, renseignez d’abord l’identifiant ou l’URL de la vidéo ci-dessous (admin ou .env), puis ouvrez au public.'
                : 'Créez d’abord une entrée live (bouton « Créer le canal »), puis ouvrez au public.';

            return $this->jsonLiveError($request, $msg);
        }

        Setting::setLivePublicVisible(true);

        return $this->jsonLiveOk(
            $request,
            'Le flux est visible sur la page publique /live (dès que la source envoie du signal).',
        );
    }

    public function unpublish(Request $request): RedirectResponse|JsonResponse
    {
        Setting::setLivePublicVisible(false);

        return $this->jsonLiveOk(
            $request,
            'La page publique n’affiche plus le lecteur (les visiteurs voient l’écran d’attente).',
        );
    }

    public function updateProvider(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'streaming_provider' => ['required', 'string', Rule::in(StreamingServiceManager::PROVIDERS)],
        ]);

        Setting::set(
            Setting::STREAMING_PROVIDER,
            $validated['streaming_provider'],
        );

        return $this->jsonLiveOk(
            $request,
            'Fournisseur streaming enregistré. Il est utilisé pour tout le site à la place de STREAMING_PROVIDER dans le .env.',
        );
    }

    public function resetProviderToEnv(Request $request): RedirectResponse|JsonResponse
    {
        Setting::query()->whereKey(Setting::STREAMING_PROVIDER)->delete();

        return $this->jsonLiveOk(
            $request,
            'Le site utilise à nouveau la valeur STREAMING_PROVIDER du fichier .env.',
        );
    }

    public function updateYoutubeLiveVideo(Request $request): RedirectResponse|JsonResponse
    {
        if ($this->streamingManager->resolvedProvider() !== 'youtube') {
            return $this->jsonLiveError(
                $request,
                'Le fournisseur actif n’est pas YouTube. Sélectionnez « YouTube (embed) » puis enregistrez.',
            );
        }

        $validated = $request->validate([
            'youtube_live_video_id' => ['nullable', 'string', 'max:2048'],
        ]);

        $raw = trim((string) ($validated['youtube_live_video_id'] ?? ''));

        if ($raw === '') {
            Setting::query()->whereKey(Setting::YOUTUBE_LIVE_VIDEO_ID)->delete();

            return $this->jsonLiveOk(
                $request,
                'Valeur YouTube effacée en base. Le site utilisera uniquement YOUTUBE_LIVE_VIDEO_ID du .env si elle est définie.',
            );
        }

        if (YoutubeEmbedStreamService::normalizeYoutubeVideoId($raw) === null) {
            return $this->jsonLiveError(
                $request,
                'Identifiant ou URL YouTube non reconnu. Utilisez un ID vidéo ou une URL watch, youtu.be, embed, shorts ou live.',
            );
        }

        Setting::set(Setting::YOUTUBE_LIVE_VIDEO_ID, $raw);

        return $this->jsonLiveOk(
            $request,
            'Vidéo YouTube enregistrée (prioritaire sur le fichier .env).',
        );
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->stream->createLiveInput($request->string('name')->toString() ?: 'Live Stream');
        } catch (Throwable $e) {
            return $this->jsonLiveError($request, $e->getMessage());
        }

        Setting::setLivePublicVisible(false);

        return $this->jsonLiveOk(
            $request,
            'Entrée live créée. Utilisez les identifiants RTMPS dans OBS pour diffuser.',
        );
    }

    public function start(Request $request): RedirectResponse|JsonResponse
    {
        try {
            if ($this->stream->resolveLiveInputUid() === null) {
                $this->stream->createLiveInput('Live Stream');
                Setting::setLivePublicVisible(false);
            } else {
                $this->stream->setLiveInputEnabled(true);
            }
        } catch (Throwable $e) {
            return $this->jsonLiveError($request, $e->getMessage());
        }

        return $this->jsonLiveOk(
            $request,
            'Entrée OBS activée. Utilisez la prévisualisation ci-dessous, puis ouvrez au public lorsque vous êtes prêt.',
        );
    }

    public function stop(Request $request): RedirectResponse|JsonResponse
    {
        try {
            if ($this->stream->resolveLiveInputUid() === null) {
                throw new RuntimeException('Aucune entrée live configurée.');
            }
            $this->stream->setLiveInputEnabled(false);
        } catch (Throwable $e) {
            return $this->jsonLiveError($request, $e->getMessage());
        }

        Setting::setLivePublicVisible(false);

        return $this->jsonLiveOk(
            $request,
            'Entrée live désactivée et flux retiré du site public.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function livePageVariables(): array
    {
        $configured = $this->stream->isConfigured();
        $ingest = $configured ? $this->stream->getIngestInfo() : null;
        $liveInput = $configured ? $this->stream->getLiveInput() : null;
        $previewForYoutube = $this->stream->providerKey() === 'youtube';
        $previewPlayer = ($configured || $previewForYoutube)
            ? $this->presenter->viewData(requirePublicVisibility: false)
            : null;

        $youtubeLiveVideoRaw = Setting::get(Setting::YOUTUBE_LIVE_VIDEO_ID) ?? '';
        $youtubeFallbackEnvRaw = config('streaming.youtube.video_id');
        $youtubeFallbackEnvConfigured = is_string($youtubeFallbackEnvRaw)
            && YoutubeEmbedStreamService::normalizeYoutubeVideoId(trim($youtubeFallbackEnvRaw)) !== null;

        return [
            'configured' => $configured,
            'ingest' => $ingest,
            'liveInput' => $liveInput,
            'liveInputUid' => $this->stream->resolveLiveInputUid(),
            'streamProvider' => $this->stream->providerLabel(),
            'streamProviderKey' => $this->stream->providerKey(),
            'resolvedStreamingProvider' => $this->streamingManager->resolvedProvider(),
            'streamingProviderForced' => $this->streamingManager->isProviderForcedFromDatabase(),
            'envStreamingDefault' => (string) config('streaming.default', 'aws_ivs'),
            'publicLiveUrl' => route('live.show'),
            'previewPlayer' => $previewPlayer,
            'livePublicVisible' => Setting::isLivePublicVisible(),
            'youtubeLiveVideoRaw' => $youtubeLiveVideoRaw,
            'youtubeFallbackEnvConfigured' => $youtubeFallbackEnvConfigured,
        ];
    }

    private function liveDashboardHtml(): string
    {
        return view('admin.live.partials.dashboard-inner', $this->livePageVariables())->render();
    }

    private function jsonLiveOk(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'fragments' => [
                    'dashboard' => $this->liveDashboardHtml(),
                ],
            ]);
        }

        return back()->with('status', $message);
    }

    private function jsonLiveError(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 422);
        }

        return back()->with('error', $message);
    }
}
