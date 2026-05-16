{{-- Fragment HTML remplacé après actions AJAX (garde les IDs utilisés par les scripts). --}}
<div class="live-admin-dashboard">
    <section class="live-admin-card live-admin-card--accent">
        <div class="live-admin-card__head">
            <div class="live-admin-card__title-row">
                <span class="live-admin-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                </span>
                <div>
                    <h2 class="live-admin-card__title">Fournisseur streaming</h2>
                    <p class="live-admin-card__desc">
                        Moteur pour la page <code class="live-admin-code">/live</code>, cette console et les identifiants RTMPS.
                        @if ($streamingProviderForced)
                            <span class="live-admin-pill live-admin-pill--warn">Surcharge admin</span>
                            remplace <code class="live-admin-code">STREAMING_PROVIDER</code>
                            (fichier <code class="live-admin-code">.env</code> : <code class="live-admin-code">{{ $envStreamingDefault }}</code>).
                        @else
                            Valeur lue dans <code class="live-admin-code">.env</code> :
                            <code class="live-admin-code">STREAMING_PROVIDER={{ $envStreamingDefault }}</code>.
                            Tu peux forcer un fournisseur ci-dessous (en base).
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="live-admin-card__body live-admin-provider-row">
            <form
                class="live-admin-inline-form"
                method="post"
                action="{{ route('admin.live.provider.update') }}"
                data-admin-live-form
                data-admin-live-full-reload
            >
                @csrf
                <label class="live-admin-field">
                    <span class="live-admin-field__label">Fournisseur actif</span>
                    <select name="streaming_provider" class="live-admin-select">
                        <option value="cloudflare" @selected($resolvedStreamingProvider === 'cloudflare')>Cloudflare Stream</option>
                        <option value="aws_ivs" @selected($resolvedStreamingProvider === 'aws_ivs')>AWS IVS</option>
                        <option value="youtube" @selected($resolvedStreamingProvider === 'youtube')>YouTube (embed)</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary live-admin-btn-submit">Enregistrer</button>
            </form>
            @if ($streamingProviderForced)
                <form
                    method="post"
                    action="{{ route('admin.live.provider.reset') }}"
                    data-admin-live-form
                    data-admin-live-full-reload
                    data-confirm="Revenir à la valeur STREAMING_PROVIDER du fichier .env ?"
                >
                    @csrf
                    <button type="submit" class="btn btn-secondary live-admin-btn-submit">Réinitialiser (.env)</button>
                </form>
            @endif
        </div>
    </section>

    <div class="live-admin-summary">
        <span class="live-admin-pill live-admin-pill--accent">{{ $streamProvider }}</span>
        <span class="live-admin-pill live-admin-pill--muted"><code>{{ $streamProviderKey }}</code></span>
    </div>

    @if ($streamProviderKey === 'youtube')
        <section class="live-admin-card">
            <div class="live-admin-card__head">
                <h2 class="live-admin-card__title">YouTube — vidéo sur /live</h2>
                <p class="live-admin-card__desc">
                    Collez l’identifiant vidéo (11 caractères) ou une URL
                    (<code class="live-admin-code">youtube.com/watch?v=…</code>,
                    <code class="live-admin-code">youtu.be/…</code>, embed, shorts, live).
                    La valeur enregistrée ici est <strong>prioritaire</strong> sur
                    <code class="live-admin-code">YOUTUBE_LIVE_VIDEO_ID</code> dans le .env.
                </p>
                @if ($youtubeFallbackEnvConfigured)
                    <p class="live-admin-card__desc" style="margin-top: 0.5rem;">
                        @if ($youtubeLiveVideoRaw !== '')
                            <span class="live-admin-pill live-admin-pill--muted">Secours .env disponible</span>
                            Si vous effacez le champ ci-dessous, ce sera la valeur du .env qui s’appliquera.
                        @else
                            <span class="live-admin-pill live-admin-pill--muted">Repli .env actif</span>
                            Aucune valeur en base : le site utilise <code class="live-admin-code">YOUTUBE_LIVE_VIDEO_ID</code> du .env.
                        @endif
                    </p>
                @endif
            </div>
            <div class="live-admin-card__body">
                <form
                    class="live-admin-inline-form"
                    method="post"
                    action="{{ route('admin.live.youtube.update') }}"
                    data-admin-live-form
                >
                    @csrf
                    <label class="live-admin-field" style="flex: 1; min-width: 240px;">
                        <span class="live-admin-field__label">ID ou URL YouTube</span>
                        <input
                            type="text"
                            name="youtube_live_video_id"
                            class="live-admin-select"
                            value="{{ old('youtube_live_video_id', $youtubeLiveVideoRaw) }}"
                            placeholder="ex. dQw4w9WgXcQ ou https://www.youtube.com/watch?v=…"
                            autocomplete="off"
                        />
                    </label>
                    <button type="submit" class="btn btn-primary live-admin-btn-submit">Enregistrer la vidéo</button>
                </form>
                <p class="live-admin-card__desc" style="margin: 0.85rem 0 0; font-size: 0.82rem;">
                    Laissez vide et enregistrez pour effacer la valeur en base et revenir au .env uniquement.
                </p>
            </div>
        </section>
    @endif

    <section class="live-admin-toolbar">
        <div class="live-admin-toolbar__cluster">
            @if ($streamProviderKey === 'youtube')
                <form method="post" action="{{ route('admin.live.publish') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-primary btn--compact live-admin-btn-submit"
                        title="Ouvre la lecture sur la page publique /live pour les visiteurs (écran d’attente puis flux)."
                    >
                        <span class="live-admin-btn-icon" aria-hidden="true">◎</span>
                        Démarrer le live (public)
                    </button>
                </form>
                <form method="post" action="{{ route('admin.live.unpublish') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn--compact live-admin-btn-submit">Masquer du site</button>
                </form>
            @elseif ($configured && $liveInputUid)
                <form method="post" action="{{ route('admin.live.start') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn--compact live-admin-btn-submit">
                        <span class="live-admin-btn-icon" aria-hidden="true">▶</span>
                        Activer l’entrée OBS
                    </button>
                </form>
                <form method="post" action="{{ route('admin.live.publish') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-primary btn--compact live-admin-btn-submit"
                        title="Ouvre la lecture sur la page publique /live pour les visiteurs (écran d’attente puis flux)."
                    >
                        <span class="live-admin-btn-icon" aria-hidden="true">◎</span>
                        Démarrer le live (public)
                    </button>
                </form>
                <form method="post" action="{{ route('admin.live.unpublish') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn--compact live-admin-btn-submit">Masquer du site</button>
                </form>
                <form method="post" action="{{ route('admin.live.stop') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn--compact live-admin-btn-submit">Arrêter le stream</button>
                </form>
            @endif
            @if ($streamProviderKey !== 'youtube')
                <form method="post" action="{{ route('admin.live.store') }}" data-admin-live-form class="live-admin-toolbar__form">
                    @csrf
                    <button type="submit" class="btn btn-gold btn--compact live-admin-btn-submit">
                        {{ $liveInputUid ? 'Recréer le canal' : 'Créer le canal' }}
                    </button>
                </form>
            @endif
        </div>
        <a class="btn btn-secondary btn--compact live-admin-toolbar__link" href="{{ $publicLiveUrl }}" target="_blank" rel="noopener">
            Ouvrir /live ↗
        </a>
    </section>

    @if (! $configured && $streamProviderKey !== 'youtube')
        <div class="live-admin-alert live-admin-alert--error">
            @if ($streamProviderKey === 'aws_ivs')
                AWS IVS n’est pas configuré. Renseignez
                <code>AWS_ACCESS_KEY_ID</code>, <code>AWS_SECRET_ACCESS_KEY</code> et
                <code>AWS_IVS_REGION</code> dans le fichier <code>.env</code>.
            @else
                Cloudflare Stream n’est pas configuré. Renseignez
                <code>CLOUDFLARE_ACCOUNT_ID</code> et <code>CLOUDFLARE_STREAM_API_TOKEN</code> dans le fichier <code>.env</code>.
            @endif
        </div>
    @elseif (! $configured && $streamProviderKey === 'youtube')
        <div class="live-admin-alert live-admin-alert--error">
            YouTube : renseignez <strong>« ID ou URL YouTube »</strong> ci-dessus (ou <code>YOUTUBE_LIVE_VIDEO_ID</code> dans le <code>.env</code>).
            Les boutons <strong>Démarrer le live (public)</strong> et <strong>Masquer du site</strong> restent disponibles ; la prévisualisation affiche le lecteur dès qu’un ID valide est en place.
        </div>
    @endif

    @if ($configured)
        <section class="stats live-admin-stats" aria-label="État du live">
            <x-admin-stat-card
                icon="tv-minimal-play"
                label="{{ $streamProviderKey === 'aws_ivs' ? 'Canal (ARN)' : ($streamProviderKey === 'youtube' ? 'Vidéo YouTube' : 'Entrée live') }}"
                :value="$liveInputUid ?: '—'"
                tone="accent"
                value-size="mono"
            />
            <x-admin-stat-card
                icon="power"
                label="État"
                :value="$ingest['enabled'] === true ? 'En ligne' : ($ingest['enabled'] === false ? 'Hors ligne' : '—')"
                :tone="$ingest['enabled'] === true ? 'success' : ($ingest['enabled'] === false ? 'danger' : 'default')"
                value-size="sm"
            />
            <x-admin-stat-card
                icon="activity"
                label="Connexion"
                :value="$ingest['status'] ?? '—'"
                tone="info"
                value-size="sm"
            />
            <x-admin-stat-card
                icon="globe"
                label="Page publique /live"
                :value="$livePublicVisible ? 'Lecteur ouvert' : 'Écran d’attente'"
                :tone="$livePublicVisible ? 'success' : 'default'"
                value-size="sm"
            />
        </section>
    @endif

    @if ($previewPlayer)
        <section
            class="live-admin-card live-admin-card--preview"
            data-live-preview-status-url="{{ route('admin.live.preview-status') }}"
        >
            <div class="live-admin-card__head live-admin-preview-head">
                <div class="live-admin-preview-head__text">
                    <h2 class="live-admin-card__title">Prévisualisation</h2>
                    <p class="live-admin-card__desc">
                        Flux technique réservé à l’admin. La page publique suit le bouton « Démarrer le live (public) ».
                    </p>
                    <p class="live-admin-preview-hint">
                        @if ($streamProviderKey === 'youtube' && ! ($previewPlayer['hasPlayerConfig'] ?? false))
                            Enregistrez un ID ou une URL YouTube dans le formulaire ci-dessus : l’embed apparaîtra ici (et sur <code class="live-admin-code">/live</code> après publication publique).
                        @elseif ($previewPlayer['showWaiting'])
                            Actualisation automatique environ toutes les 4&nbsp;s tant qu’aucun signal vidéo n’est détecté.
                        @else
                            Le flux est détecté ; la zone ci-dessous se met à jour quand vous actualisez ou quand le flux s’arrête.
                        @endif
                    </p>
                </div>
                <button
                    type="button"
                    class="btn btn-secondary btn--compact live-admin-preview-refresh"
                    data-live-preview-refresh
                >
                    Actualiser
                </button>
            </div>
            <div class="live-admin-card__body live-admin-card__body--flush">
                <div class="admin-live-preview-shell">
                    <div
                        class="admin-live-preview-player player"
                        @if ($previewPlayer['showWaiting'])
                            data-live-poll
                            data-live-status-url="{{ route('admin.live.preview-status') }}"
                            data-live-poll-interval="4000"
                        @endif
                    >
                        @include('live.partials.player-inner', $previewPlayer)
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($configured)
        <section class="live-admin-card">
            @if ($streamProviderKey === 'youtube')
                <div class="live-admin-card__head">
                    <h2 class="live-admin-card__title">YouTube — diffusion</h2>
                    <p class="live-admin-card__desc">
                        Ce mode n’expose pas de RTMPS dans l’application : vous encodez vers YouTube (Studio ou OBS relié à YouTube).
                        Le site intègre seulement la lecture via <code class="live-admin-code">youtube.com/embed</code>.
                    </p>
                </div>
                <div class="live-admin-card__body live-admin-ingest">
                    <p class="live-admin-card__desc" style="margin: 0 0 0.75rem;">
                        Ouvrez <a href="https://studio.youtube.com/" target="_blank" rel="noopener noreferrer">YouTube Studio ↗</a>
                        pour créer un live et récupérer l’URL ou l’ID diffusé, puis mettez à jour le champ YouTube ci-dessus ou <code>YOUTUBE_LIVE_VIDEO_ID</code>.
                    </p>
                </div>
            @else
                <div class="live-admin-card__head">
                    <h2 class="live-admin-card__title">OBS — RTMPS</h2>
                    <p class="live-admin-card__desc">
                        Active l’entrée OBS, configure ces champs dans OBS, vérifie la prévisualisation puis ouvre au public.
                    </p>
                </div>
                <div class="live-admin-card__body live-admin-ingest">
                    <div class="live-admin-ingest__field">
                        <span class="live-admin-ingest__label">URL serveur</span>
                        <div class="live-admin-copy-row copy-row">
                            <code id="rtmps-url" class="live-admin-code-block">{{ $ingest['rtmps_url'] ?? '—' }}</code>
                            @if ($ingest['rtmps_url'])
                                <button type="button" class="btn btn-secondary btn--compact" data-copy="#rtmps-url">Copier</button>
                            @endif
                        </div>
                    </div>
                    <div class="live-admin-ingest__field">
                        <span class="live-admin-ingest__label">Clé de stream</span>
                        <div class="live-admin-copy-row copy-row">
                            <code id="stream-key" class="live-admin-code-block">{{ $ingest['stream_key'] ?? '—' }}</code>
                            @if ($ingest['stream_key'])
                                <button type="button" class="btn btn-secondary btn--compact" data-copy="#stream-key">Copier</button>
                            @endif
                        </div>
                    </div>
                    @if ($ingest['srt_url'])
                        <div class="live-admin-ingest__field">
                            <span class="live-admin-ingest__label">SRT (optionnel)</span>
                            <div class="live-admin-copy-row copy-row">
                                <code id="srt-url" class="live-admin-code-block">{{ $ingest['srt_url'] }}</code>
                                <button type="button" class="btn btn-secondary btn--compact" data-copy="#srt-url">Copier</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        @if ($ingest['playback'])
            <section class="live-admin-card live-admin-card--muted">
                <h2 class="live-admin-card__title">URL de lecture (référence)</h2>
                <p class="mono live-admin-playback-url">{{ $ingest['playback'] }}</p>
            </section>
        @endif
    @endif
</div>
