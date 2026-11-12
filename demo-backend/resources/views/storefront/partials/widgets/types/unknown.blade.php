<div class="sf-card sf-card--flat">
    <p class="sf-section-header__eyebrow">Widget</p>
    <h2 class="sf-section-header__title" style="text-align:left;">{{ $widget['title'] ?? ($widget['type'] ?? 'Unknown') }}</h2>
    <p style="color:var(--sf-color-muted);margin:0;">Renderer not available for type <code>{{ $widget['type'] ?? 'unknown' }}</code>.</p>
</div>
