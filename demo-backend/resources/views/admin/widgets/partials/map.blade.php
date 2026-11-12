{{-- Map type config block --}}
@php
  $mapMode = ($cfg['mode'] ?? 'coordinates') === 'embed' ? 'embed' : 'coordinates';
  $mapLocationMode = ($cfg['location_mode'] ?? 'single') === 'multi' ? 'multi' : 'single';
  $mapMarkers = is_array($cfg['markers'] ?? null) ? $cfg['markers'] : [];
@endphp
<div id="cfg-map" class="cfg d-none">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="ri-map-pin-2-line text-danger fs-16"></i>
    <span class="fw-semibold fs-13 text-uppercase text-muted">Map Options</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">Mode</label>
      <select name="config[mode]" id="mapMode" class="form-select">
        <option value="coordinates" @selected($mapMode === 'coordinates')>Coordinates</option>
        <option value="embed" @selected($mapMode === 'embed')>Embed URL</option>
      </select>
      <div class="form-text">Use coordinates by default. Embed URL is fallback.</div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Provider</label>
      <select name="config[provider]" id="mapProvider" class="form-select">
        <option value="osm" @selected(($cfg['provider'] ?? 'osm') === 'osm')>OpenStreetMap</option>
        <option value="google" @selected(($cfg['provider'] ?? '') === 'google')>Google Maps</option>
        <option value="mapbox" @selected(($cfg['provider'] ?? '') === 'mapbox')>Mapbox</option>
      </select>
      <div class="form-text">Admin preview always uses OpenStreetMap.</div>
    </div>

    <div class="col-md-3" id="mapZoomWrap">
      <label class="form-label">Zoom</label>
      <input type="number" name="config[zoom]" id="mapZoom" min="1" max="20" class="form-control"
             value="{{ $cfg['zoom'] ?? 13 }}">
    </div>

    <div class="col-md-3" id="mapLocationModeWrap">
      <label class="form-label">Location Type</label>
      <select name="config[location_mode]" id="mapLocationMode" class="form-select">
        <option value="single" @selected($mapLocationMode === 'single')>Single Office</option>
        <option value="multi" @selected($mapLocationMode === 'multi')>Multiple Offices</option>
      </select>
      <div class="form-text">Use one marker or add multiple office locations.</div>
    </div>
  </div>

  <div id="mapCoordinatesFields" class="row g-3 mb-4 {{ $mapMode === 'coordinates' ? '' : 'd-none' }}">
    <div id="mapSingleFields" class="row g-3 {{ $mapLocationMode === 'single' ? '' : 'd-none' }}">
      <div class="col-md-3">
        <label class="form-label">Latitude</label>
        <input type="number" step="any" name="config[lat]" id="mapLat" class="form-control"
               value="{{ $cfg['lat'] ?? '' }}" placeholder="41.3275">
      </div>
      <div class="col-md-3">
        <label class="form-label">Longitude</label>
        <input type="number" step="any" name="config[lng]" id="mapLng" class="form-control"
               value="{{ $cfg['lng'] ?? '' }}" placeholder="19.8187">
      </div>
      <div class="col-md-3">
        <label class="form-label">Marker Title</label>
        <input type="text" name="config[marker_title]" id="mapMarkerTitle" class="form-control"
               value="{{ $cfg['marker_title'] ?? '' }}" placeholder="Main Office">
      </div>
      <div class="col-md-3">
        <label class="form-label">Marker Text</label>
        <input type="text" name="config[marker_text]" id="mapMarkerText" class="form-control"
               value="{{ $cfg['marker_text'] ?? '' }}" placeholder="Open Mon-Fri">
      </div>
      <div class="col-12">
        <div class="form-text">Single office marker supports translatable title/text from Section 5.</div>
      </div>
    </div>

    <div id="mapMultiFields" class="{{ $mapLocationMode === 'multi' ? '' : 'd-none' }}">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold">Office Locations</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addMapMarkerBtn">
          <i class="ri-add-line me-1"></i>Add Office
        </button>
      </div>

      <div id="mapMarkersList" class="d-flex flex-column gap-2">
        @foreach($mapMarkers as $k => $marker)
          <div class="card map-marker-item" data-marker-index="{{ $k }}">
            <div class="card-body py-2 px-3">
              <div class="row g-2 align-items-end">
                <div class="col-md-2">
                  <label class="form-label mb-1">Latitude</label>
                  <input type="number" step="any" class="form-control form-control-sm map-marker-lat"
                         name="config[markers][{{ $k }}][lat]" value="{{ $marker['lat'] ?? '' }}" placeholder="41.3275">
                </div>
                <div class="col-md-2">
                  <label class="form-label mb-1">Longitude</label>
                  <input type="number" step="any" class="form-control form-control-sm map-marker-lng"
                         name="config[markers][{{ $k }}][lng]" value="{{ $marker['lng'] ?? '' }}" placeholder="19.8187">
                </div>
                <div class="col-md-3">
                  <label class="form-label mb-1">Office Name</label>
                  <input type="text" class="form-control form-control-sm map-marker-title"
                         name="config[markers][{{ $k }}][title]" value="{{ $marker['title'] ?? '' }}" placeholder="Office title">
                </div>
                <div class="col-md-4">
                  <label class="form-label mb-1">Details</label>
                  <input type="text" class="form-control form-control-sm map-marker-text"
                         name="config[markers][{{ $k }}][text]" value="{{ $marker['text'] ?? '' }}" placeholder="Address or notes">
                </div>
                <div class="col-md-1 text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger js-remove-map-marker" title="Remove">
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </div>

                <div class="col-12">
                  <iframe class="map-marker-preview w-100 rounded border"
                          title="Office map preview"
                          style="height:220px; border:0;"
                          src="about:blank"
                          loading="lazy"></iframe>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div id="mapMarkersEmpty" class="text-muted small mt-2 {{ count($mapMarkers) > 0 ? 'd-none' : '' }}">
        Add at least one office location for multi-office mode.
      </div>
    </div>
  </div>

  <div id="mapEmbedFields" class="row g-3 mb-4 {{ $mapMode === 'embed' ? '' : 'd-none' }}">
    <div class="col-12">
      <label class="form-label">Embed URL</label>
      <input type="url" name="config[embed_url]" id="mapEmbedUrl" class="form-control"
             value="{{ $cfg['embed_url'] ?? '' }}" placeholder="https://www.openstreetmap.org/export/embed.html?...">
      <div class="form-text">Paste iframe src URL from your map provider.</div>
    </div>
  </div>

  <div id="mapGlobalPreviewWrap" class="border rounded overflow-hidden bg-light">
    <div class="p-2 border-bottom bg-light-subtle d-flex align-items-center justify-content-between">
      <span class="fw-semibold fs-13">Map Preview (OpenStreetMap)</span>
      <button type="button" id="refreshMapPreview" class="btn btn-sm btn-outline-secondary">
        <i class="ri-refresh-line me-1"></i> Refresh
      </button>
    </div>
    <iframe id="mapPreviewFrame" title="Map preview" class="w-100" style="height:320px; border:0;"
            src="about:blank" loading="lazy"></iframe>
  </div>
</div>
