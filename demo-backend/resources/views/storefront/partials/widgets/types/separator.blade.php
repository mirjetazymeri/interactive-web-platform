@php
    $style = $config['style'] ?? 'solid';
    $thickness = (int) ($config['thickness'] ?? 1);
    $color = $config['color'] ?? '#cbd5e1';
    $width = $config['width'] ?? '100%';
    $label = $config['label'] ?? '';
    $alignment = $config['alignment'] ?? 'center';
@endphp

<div class="sf-separator" style="margin-block: {{ (int) ($config['spacing_top'] ?? 32) }}px {{ (int) ($config['spacing_bottom'] ?? 32) }}px; text-align: {{ $alignment }};">
    @if($label !== '')
        <span class="sf-separator__label">{{ $label }}</span>
    @endif
    <hr style="border: 0; border-top: {{ $thickness }}px {{ $style }} {{ $color }}; width: {{ $width }};">
</div>
