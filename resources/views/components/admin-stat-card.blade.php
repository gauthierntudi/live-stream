@props([
    'icon',
    'label',
    'value',
    'tone' => 'default',
    'valueSize' => 'lg',
])

<div {{ $attributes->class(['stat-card', 'stat-card--'.$tone]) }}>
    <div class="stat-card__icon" aria-hidden="true">
        <i data-lucide="{{ $icon }}"></i>
    </div>
    <div class="stat-card__body">
        <div class="stat-card__label">{{ $label }}</div>
        <div @class([
            'stat-card__value',
            'stat-card__value--sm' => $valueSize === 'sm',
            'stat-card__value--mono' => $valueSize === 'mono',
        ])>{{ $value }}</div>
    </div>
</div>
