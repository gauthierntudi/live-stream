@php
    $appName = (string) config('app.name');
    $brandParts = preg_split('/\s+/', $appName, 2, PREG_SPLIT_NO_EMPTY);
    $brandFirst = $brandParts[0] ?? $appName;
    $brandAlternate = trim((string) ($brandParts[1] ?? 'Ize'));
    if ($brandAlternate === '' || strcasecmp($brandAlternate, 'Revayah') === 0) {
        $brandAlternate = 'Ize';
    }
    $brandTypewriterWords = [$brandAlternate, 'Revayah'];
    $brandTypewriterMeasure = 'Revayah';
@endphp
<span class="brand">
    <span class="brand-part brand-part--light">{{ $brandFirst }}</span>
    <span class="brand-typewriter-wrap">
        <span class="brand-part brand-part--accent brand-typewriter-measure" aria-hidden="true">{{ $brandTypewriterMeasure }}</span>
        <span
            id="brand-typewriter"
            class="brand-part brand-part--accent brand-part--typewriter"
            data-words='@json($brandTypewriterWords)'
            data-word-alt="{{ $brandAlternate }}"
        ><span class="brand-typewriter-text">{{ $brandTypewriterWords[0] }}</span><span class="brand-typewriter-caret" aria-hidden="true"></span></span>
    </span>
</span>
<span class="brand-tagline">Live Streaming</span>
