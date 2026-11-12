{{-- Widget form: map type - coordinates/embed toggle + OSM preview --}}

@section('scripts')
@parent
<script>
(function () {
  'use strict';

  var modeEl       = document.getElementById('mapMode');
  var locationMode = document.getElementById('mapLocationMode');
  var providerEl   = document.getElementById('mapProvider');
  var latEl        = document.getElementById('mapLat');
  var lngEl        = document.getElementById('mapLng');
  var zoomEl       = document.getElementById('mapZoom');
  var singleWrap   = document.getElementById('mapSingleFields');
  var multiWrap    = document.getElementById('mapMultiFields');
  var markersList  = document.getElementById('mapMarkersList');
  var markersEmpty = document.getElementById('mapMarkersEmpty');
  var addMarkerBtn = document.getElementById('addMapMarkerBtn');
  var embedUrlEl   = document.getElementById('mapEmbedUrl');
  var coordsWrap   = document.getElementById('mapCoordinatesFields');
  var embedWrap    = document.getElementById('mapEmbedFields');
  var previewFrame = document.getElementById('mapPreviewFrame');
  var refreshBtn   = document.getElementById('refreshMapPreview');

  var globalPreviewWrap = document.getElementById('mapGlobalPreviewWrap');
  if (!modeEl || !previewFrame) return;

  function toNumber(v) {
    var n = parseFloat(v);
    return Number.isFinite(n) ? n : null;
  }

  function toZoom(v) {
    var z = parseInt(v, 10);
    if (!Number.isFinite(z)) return 13;
    return Math.max(1, Math.min(20, z));
  }

  function bboxFromCenter(lat, lng, zoom) {
    var delta = Math.max(0.0015, 360 / Math.pow(2, zoom + 2));
    var left = (lng - delta).toFixed(6);
    var right = (lng + delta).toFixed(6);
    var top = (lat + delta).toFixed(6);
    var bottom = (lat - delta).toFixed(6);
    return left + '%2C' + bottom + '%2C' + right + '%2C' + top;
  }

  function buildOsmEmbedUrl(lat, lng, zoom) {
    var marker = encodeURIComponent(lat.toFixed(6) + ',' + lng.toFixed(6));
    var bbox = bboxFromCenter(lat, lng, zoom);
    return 'https://www.openstreetmap.org/export/embed.html?layer=mapnik&marker=' + marker + '&bbox=' + bbox;
  }

  function syncMode() {
    var isEmbed = modeEl.value === 'embed';
    var isMulti = locationMode && locationMode.value === 'multi';

    if (coordsWrap) coordsWrap.classList.toggle('d-none', isEmbed);
    if (embedWrap) embedWrap.classList.toggle('d-none', !isEmbed);
    if (globalPreviewWrap) globalPreviewWrap.classList.toggle('d-none', !isEmbed && isMulti);
    if (singleWrap) singleWrap.classList.toggle('d-none', isEmbed || isMulti);
    if (multiWrap) multiWrap.classList.toggle('d-none', isEmbed || !isMulti);
  }

  function syncMarkersEmpty() {
    if (!markersList || !markersEmpty) return;
    var count = markersList.querySelectorAll('.map-marker-item').length;
    markersEmpty.classList.toggle('d-none', count > 0);
  }

  function markerRow(index) {
    return (
      '<div class="card map-marker-item" data-marker-index="' + index + '">' +
        '<div class="card-body py-2 px-3">' +
          '<div class="row g-2 align-items-end">' +
            '<div class="col-md-2">' +
              '<label class="form-label mb-1">Latitude</label>' +
              '<input type="number" step="any" class="form-control form-control-sm map-marker-lat" name="config[markers][' + index + '][lat]" placeholder="41.3275">' +
            '</div>' +
            '<div class="col-md-2">' +
              '<label class="form-label mb-1">Longitude</label>' +
              '<input type="number" step="any" class="form-control form-control-sm map-marker-lng" name="config[markers][' + index + '][lng]" placeholder="19.8187">' +
            '</div>' +
            '<div class="col-md-3">' +
              '<label class="form-label mb-1">Office Name</label>' +
              '<input type="text" class="form-control form-control-sm map-marker-title" name="config[markers][' + index + '][title]" placeholder="Office title">' +
            '</div>' +
            '<div class="col-md-4">' +
              '<label class="form-label mb-1">Details</label>' +
              '<input type="text" class="form-control form-control-sm map-marker-text" name="config[markers][' + index + '][text]" placeholder="Address or notes">' +
            '</div>' +
            '<div class="col-md-1 text-end">' +
              '<button type="button" class="btn btn-sm btn-outline-danger js-remove-map-marker" title="Remove">' +
                '<i class="ri-delete-bin-line"></i>' +
              '</button>' +
            '</div>' +
            '<div class="col-12">' +
              '<iframe class="map-marker-preview w-100 rounded border" title="Office map preview" style="height:220px; border:0;" src="about:blank" loading="lazy"></iframe>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function nextMarkerIndex() {
    if (!markersList) return 0;
    var maxIndex = -1;
    markersList.querySelectorAll('.map-marker-item').forEach(function (row) {
      var idx = parseInt(row.getAttribute('data-marker-index') || '-1', 10);
      if (Number.isFinite(idx) && idx > maxIndex) {
        maxIndex = idx;
      }
    });
    return maxIndex + 1;
  }

  function firstValidMultiMarker() {
    if (!markersList) return null;
    var rows = markersList.querySelectorAll('.map-marker-item');
    for (var i = 0; i < rows.length; i++) {
      var latInput = rows[i].querySelector('.map-marker-lat');
      var lngInput = rows[i].querySelector('.map-marker-lng');
      var lat = toNumber(latInput ? latInput.value : null);
      var lng = toNumber(lngInput ? lngInput.value : null);
      if (lat !== null && lng !== null) {
        return { lat: lat, lng: lng };
      }
    }
    return null;
  }

  function refreshMarkerPreviews() {
    if (!markersList) return;

    var zoom = toZoom(zoomEl ? zoomEl.value : 13);
    markersList.querySelectorAll('.map-marker-item').forEach(function (row) {
      var frame = row.querySelector('.map-marker-preview');
      if (!frame) return;

      var latInput = row.querySelector('.map-marker-lat');
      var lngInput = row.querySelector('.map-marker-lng');
      var lat = toNumber(latInput ? latInput.value : null);
      var lng = toNumber(lngInput ? lngInput.value : null);

      if (lat === null || lng === null) {
        frame.src = 'about:blank';
        return;
      }

      frame.src = buildOsmEmbedUrl(lat, lng, zoom);
    });
  }

  function refreshPreview() {
    syncMode();
    syncMarkersEmpty();
    refreshMarkerPreviews();

    if (modeEl.value === 'embed') {
      var embed = (embedUrlEl && embedUrlEl.value ? embedUrlEl.value.trim() : '');
      previewFrame.src = embed !== '' ? embed : 'about:blank';
      return;
    }

    var isMulti = locationMode && locationMode.value === 'multi';
    var lat = null;
    var lng = null;
    if (isMulti) {
      var marker = firstValidMultiMarker();
      if (marker) {
        lat = marker.lat;
        lng = marker.lng;
      }
    } else {
      lat = toNumber(latEl ? latEl.value : null);
      lng = toNumber(lngEl ? lngEl.value : null);
    }

    var zoom = toZoom(zoomEl ? zoomEl.value : 13);

    if (lat === null || lng === null) {
      previewFrame.src = 'about:blank';
      return;
    }

    previewFrame.src = buildOsmEmbedUrl(lat, lng, zoom);
  }

  [modeEl, locationMode, providerEl, latEl, lngEl, zoomEl, embedUrlEl].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', refreshPreview);
    el.addEventListener('change', refreshPreview);
  });

  if (addMarkerBtn && markersList) {
    addMarkerBtn.addEventListener('click', function () {
      var index = nextMarkerIndex();
      markersList.insertAdjacentHTML('beforeend', markerRow(index));
      syncMarkersEmpty();
      refreshPreview();
    });
  }

  if (markersList) {
    markersList.addEventListener('click', function (e) {
      var removeBtn = e.target.closest('.js-remove-map-marker');
      if (!removeBtn) return;
      var row = removeBtn.closest('.map-marker-item');
      if (row) row.remove();
      syncMarkersEmpty();
      refreshPreview();
    });

    markersList.addEventListener('input', function () {
      refreshPreview();
    });
  }

  if (refreshBtn) refreshBtn.addEventListener('click', refreshPreview);

  refreshPreview();
})();
</script>
@endsection
