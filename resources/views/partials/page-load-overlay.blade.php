<style>
    #page-load-overlay {
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        background: var(--bg, #0b0b0f);
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    #page-load-overlay.page-load-overlay--hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    #page-load-overlay .page-load-overlay__spinner {
        width: 2.75rem;
        height: 2.75rem;
        border: 3px solid rgba(229, 9, 20, 0.22);
        border-top-color: var(--accent, #e50914);
        border-radius: 50%;
        animation: page-load-spin 0.7s linear infinite;
    }
    #page-load-overlay .sr-only {
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
    @keyframes page-load-spin {
        to {
            transform: rotate(360deg);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        #page-load-overlay .page-load-overlay__spinner {
            animation: none;
            border-top-color: var(--accent, #e50914);
            opacity: 0.85;
        }
        #page-load-overlay {
            transition: opacity 0.15s ease, visibility 0.15s ease;
        }
    }
</style>
<div id="page-load-overlay" class="page-load-overlay" role="status" aria-live="polite" aria-busy="true" aria-label="Chargement">
    <span class="page-load-overlay__spinner" aria-hidden="true"></span>
    <span class="sr-only">Chargement en cours…</span>
</div>
