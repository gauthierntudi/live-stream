@if ($showPlayer && $isHls)
    {{-- IVS : Video.js ; Cloudflare / YouTube : iframe embed. --}}
    <div class="live-player-netflix" role="region" aria-label="Lecture du direct">
        <video
            class="video-js vjs-big-play-centered"
            data-src="{{ $playbackUrl }}"
            playsinline
            muted
        ></video>
    </div>
@elseif ($showPlayer)
    <iframe
        src="{{ $iframeSrc }}"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
        loading="lazy"
        referrerpolicy="strict-origin-when-cross-origin"
        title="Lecteur vidéo du direct"
    ></iframe>
@elseif ($hasPlayerConfig ?? false)
    <div class="player__overlay player__overlay--waiting" role="status" aria-live="polite" data-live-waiting>
        <div class="player__pulse" aria-hidden="true"></div>
        @if ($waitingNotYetPublic ?? false)
            <h1 class="player__title">Le direct n’est pas encore ouvert au public</h1>
            <p class="player__subtitle">
                La source technique peut être active ; la page publique affichera la vidéo lorsque l’équipe aura ouvert la diffusion depuis l’administration.
            </p>
            <p class="player__hint">Restez sur cette page : elle se mettra à jour automatiquement.</p>
        @elseif ($youtubeAwaitingEmbed ?? false)
            <h1 class="player__title">Le direct arrive bientôt</h1>
            <p class="player__subtitle">
                La diffusion utilise YouTube. Dès que l’identifiant ou l’URL de la vidéo est enregistré dans l’administration, le lecteur apparaîtra ici automatiquement.
            </p>
            <p class="player__hint">Merci de patienter quelques instants.</p>
        @else
            <h1 class="player__title">Le direct arrive bientôt</h1>
            <p class="player__subtitle">
                L’équipe prépare la diffusion. Cette page se mettra à jour automatiquement dès que le live sera en ligne.
            </p>
            <p class="player__hint">Merci de patienter quelques instants.</p>
        @endif
    </div>
@else
    <div class="player__overlay" role="status">
        <h1 class="player__title">Live non disponible</h1>
        <p class="player__subtitle">
            La diffusion n’est pas encore configurée. Revenez plus tard ou contactez l’équipe.
        </p>
    </div>
@endif
