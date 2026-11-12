{{-- Widget form: styles & CDN stylesheet --}}

@section('styles')
@parent
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<style>
  /* ── Type selector cards ─────────────────────────────── */
  .type-card { cursor: pointer; border-color: transparent !important; }
  .type-card:hover  { border-color: var(--bs-primary) !important; box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb),.15); }
  .type-card--active { box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb),.3); }
  /* ── Section label separators ────────────────────────── */
  .section-label { padding-top: 1rem; margin-top: .5rem; border-top: 1px solid var(--bs-border-color); }
  .section-label:first-child { border-top: none; padding-top: 0; margin-top: 0; }
  /* ── Slide drag handle ───────────────────────────────── */
  .slide-drag-handle { cursor: grab; }
  .slide-drag-handle:active { cursor: grabbing; }
  .slide-sortable-ghost { opacity: .35; background: var(--bs-primary-bg-subtle, #e7f3ff) !important; }
  /* ── Code textareas ──────────────────────────────────── */
  .font-monospace { font-size: .82rem; line-height: 1.55; }
  /* ── Misc ────────────────────────────────────────────── */
  .badge.bg-purple-transparent { background: rgba(111,66,193,.12); }
  .text-purple { color: #6f42c1 !important; }
  .border-purple { border-color: #6f42c1 !important; }
  .btn-xs { padding: .2rem .45rem; font-size: .75rem; line-height: 1.3; }
</style>
@endsection
