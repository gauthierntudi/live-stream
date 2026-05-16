<style>
    .brand-wrap {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        line-height: 1.05;
        text-decoration: none;
        min-width: 0;
        flex: 0 1 auto;
        width: fit-content;
        max-width: 100%;
        align-items: flex-start;
        text-align: left;
        overflow: visible;
    }
    .brand {
        display: inline-flex;
        flex-wrap: nowrap;
        align-items: baseline;
        gap: 0.25em;
        font-family: var(--font-stream-brand, "Maswen", sans-serif);
        letter-spacing: 0.04em;
        font-size: clamp(1.15rem, 4.5vw, 2rem);
        max-width: 100%;
        min-width: 0;
        white-space: nowrap;
        overflow: visible;
    }
    .brand-part--light {
        color: #ffffff;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .brand-part--accent {
        color: var(--accent);
    }
    .brand-typewriter-wrap {
        position: relative;
        display: inline-block;
        vertical-align: baseline;
        overflow: visible;
        padding-top: 0.1em;
        padding-right: 0.14em;
        margin-top: -0.1em;
    }
    .brand-typewriter-measure {
        position: absolute;
        visibility: hidden;
        height: 0;
        overflow: hidden;
        white-space: nowrap;
        pointer-events: none;
        user-select: none;
    }
    .brand-part--typewriter {
        display: inline-flex;
        align-items: flex-end;
        white-space: nowrap;
        vertical-align: baseline;
        overflow: visible;
    }
    .brand-typewriter-caret {
        display: inline-block;
        flex-shrink: 0;
        width: 0.07em;
        min-width: 1.5px;
        height: 0.72em;
        margin-left: 0.05em;
        margin-bottom: 0.1em;
        background: #ffffff;
        vertical-align: bottom;
        transform: skewX(-14deg);
        transform-origin: bottom center;
        animation: brand-typewriter-caret 0.72s step-end infinite;
    }
    @keyframes brand-typewriter-caret {
        0%, 49% { opacity: 1; }
        50%, 100% { opacity: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .brand-typewriter-caret { display: none; }
    }
    .brand-tagline {
        font-family: var(--font-stream-body, "Montserrat", system-ui, sans-serif);
        font-size: clamp(0.55rem, 2.2vw, 0.65rem);
        font-weight: 600;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: var(--muted, #9ca3af);
    }
</style>
