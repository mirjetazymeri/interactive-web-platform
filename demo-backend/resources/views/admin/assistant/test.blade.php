@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.test')])

<p class="text-muted">{{ __('assistant.test_intro') }}</p>
@unless($enabled)<div class="alert alert-warning">{{ __('assistant.disabled_note') }}</div>@endunless

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <form id="assistantTestForm">
        <div class="mb-3">
          <label class="form-label" for="test-locale">{{ __('assistant.language') }}</label>
          <select id="test-locale" class="form-select">
            @foreach($locales as $locale)
              <option value="{{ $locale }}">{{ config("locales.$locale.name") ?? strtoupper($locale) }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="test-message">{{ __('assistant.test_question') }}</label>
          <textarea id="test-message" class="form-control" rows="3" maxlength="{{ config('assistant.max_length', 300) }}" required></textarea>
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="test-continue" checked>
          <label class="form-check-label" for="test-continue">{{ __('assistant.continue_conversation') }}</label>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-primary" type="submit">{{ __('assistant.ask') }}</button>
          <button class="btn btn-light" type="button" id="test-clear">{{ __('assistant.clear') }}</button>
        </div>
      </form>
      <div id="test-history" class="mt-3 small"></div>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card"><div class="card-body" id="test-result" aria-live="polite">
      <p class="text-muted mb-0">{{ __('assistant.result') }}…</p>
    </div></div>
  </div>
</div>
@endsection

@section('scripts')
@parent
@php
  $labels = \Illuminate\Support\Arr::only(trans('assistant'), ['normalized', 'tokens', 'synonyms_used', 'detected_intent', 'matched_keywords', 'score',
    'match_status', 'reason', 'response', 'navigation', 'cards', 'followup_suggestions', 'candidates', 'detected_entity',
    'status_matched', 'status_ambiguous', 'status_fallback', 'none', 'quantity', 'project_topic', 'detected_language', 'request_type', 'subjects']);
@endphp
<script>
(function () {
  // Everything from the response is inserted as text, never as HTML.
  var t = @json($labels);
  var form = document.getElementById('assistantTestForm');
  var result = document.getElementById('test-result');
  var history = document.getElementById('test-history');
  var context = null;

  function el(tag, text, className) {
    var node = document.createElement(tag);
    if (text !== undefined && text !== null) node.textContent = String(text);
    if (className) node.className = className;
    return node;
  }
  function row(label, value) {
    var wrap = el('div', null, 'mb-2');
    wrap.appendChild(el('div', label, 'text-muted small'));
    if (value instanceof Node) wrap.appendChild(value); else wrap.appendChild(el('div', value === '' || value === null || value === undefined ? t.none : value));
    return wrap;
  }
  function list(items, render) {
    if (!items || !items.length) return el('div', t.none);
    var ul = el('ul', null, 'mb-0 ps-3');
    items.forEach(function (item) { ul.appendChild(el('li', render(item))); });
    return ul;
  }

  function show(data) {
    result.replaceChildren();
    var badge = { matched: 'bg-success', ambiguous: 'bg-warning text-dark', fallback: 'bg-secondary' }[data.status] || 'bg-light';
    var status = el('span', t['status_' + data.status] || data.status, 'badge ' + badge);
    result.appendChild(row(t.match_status, status));
    var top = data.debug.candidates[0];
    result.appendChild(row(t.detected_intent, data.intent ? '#' + data.intent.id + ' — ' + data.intent.question : null));
    // The score of what decided the answer: the matched question, or the named service/project.
    var score = data.debug.reason === 'entity' && data.debug.entity ? data.debug.entity.score + ' (' + data.debug.entity.name + ')'
      : data.intent && top && top.id === data.intent.id ? top.score + (top.exact ? ' (exact)' : '') : (top ? top.score : null);
    result.appendChild(row(t.score, score));
    result.appendChild(row(t.reason, data.debug.reason));
    result.appendChild(row(t.normalized, data.debug.normalized));
    result.appendChild(row(t.tokens, data.debug.tokens.join(', ')));
    result.appendChild(row(t.synonyms_used, list(data.debug.synonyms, function (g) { return g.join(' / '); })));
    result.appendChild(row(t.matched_keywords, top && top.keywords.length ? top.keywords.join(', ') : null));
    result.appendChild(row(t.detected_entity, data.debug.entity ? data.debug.entity.name + ' (' + data.debug.entity.score + ')' : null));
    result.appendChild(row(t.quantity, data.debug.quantity === null || data.debug.quantity === undefined ? null : String(data.debug.quantity)));
    result.appendChild(row(t.project_topic, data.debug.topic && data.debug.topic.terms.length ? data.debug.topic.terms.join(', ') + ' → ' + data.debug.topic.keys.length : null));
    var lang = data.debug.language || {};
    result.appendChild(row(t.detected_language, (lang.used || '') + (lang.language ? '' : ' (' + JSON.stringify(lang.scores || {}) + ')')));
    result.appendChild(row(t.request_type, data.debug.request && data.debug.request.act ? data.debug.request.act : null));
    result.appendChild(row(t.subjects, list(data.debug.subjects, function (s) { return s.name + ' — ' + s.type + ', ' + s.score + ', coverage ' + s.coverage + ', ' + s.via + (s.strong ? '' : ' (weak)'); })));
    var answer = el('div', data.answer, 'border rounded p-2 bg-light');
    answer.style.whiteSpace = 'pre-line';
    result.appendChild(row(t.response, answer));
    result.appendChild(row(t.navigation, list(data.actions, function (a) { return a.label + ' → ' + a.href; })));
    result.appendChild(row(t.cards, list(data.cards, function (c) { return c.type + ': ' + (c.title || c.email || c.phone || ''); })));
    result.appendChild(row(t.followup_suggestions, list(data.suggestions, function (s) { return s.question; })));
    result.appendChild(row(t.candidates, list(data.debug.candidates, function (c) {
      return c.score + ' — ' + c.question + '  [phrase ' + c.signals.phrase + ', keywords ' + c.signals.keywords + ', coverage ' + c.signals.coverage + (c.signals.context ? ', context +' + c.signals.context : '') + ']';
    })));
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var message = document.getElementById('test-message').value.trim();
    if (!message) return;
    var keep = document.getElementById('test-continue').checked;
    fetch(@js(route('admin.assistant.test.run')), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) },
      body: JSON.stringify({ message: message, locale: document.getElementById('test-locale').value, context: keep ? context : null })
    })
      .then(function (r) { return r.ok ? r.json() : r.json().then(function (body) { return Promise.reject(body); }); })
      .then(function (data) {
        context = data.context;
        history.prepend(el('div', '› ' + message, 'text-muted'));
        show(data);
      })
      .catch(function (error) {
        result.replaceChildren(el('div', (error && error.message) || 'Error', 'alert alert-danger mb-0'));
      });
  });
  document.getElementById('test-clear').addEventListener('click', function () {
    context = null;
    history.replaceChildren();
    result.replaceChildren();
  });
})();
</script>
@endsection
