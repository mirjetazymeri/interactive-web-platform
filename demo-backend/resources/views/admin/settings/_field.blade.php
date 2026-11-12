@php
    $segments  = explode('.', $key);
    $inputName = array_shift($segments) . implode('', array_map(fn ($p) => "[{$p}]", $segments));
    $type      = $field['type'];
    $fieldId   = 'field-' . \Illuminate\Support\Str::slug($key);
    $value     = old($key, $settings[$key] ?? $field['default'] ?? null);
    $hasError  = $errors->has($key);
    $errorId   = $fieldId . '-error';
    $helpId    = $fieldId . '-help';
    $describedBy = trim(($hasError ? $errorId : '') . ' ' . (!empty($field['description']) ? $helpId : ''));
    $isWide    = in_array($type, ['textarea', 'file'], true);
    $label     = $field['label'] ?? $key;
@endphp

@if ($type === 'boolean')
    {{-- Unchecked switches still submit "0" through the hidden input. --}}
    <div class="ax-field">
        <div class="ax-switch-row">
            <div class="min-w-0">
                <label class="ax-switch-row__label" for="{{ $fieldId }}">{{ $label }}</label>
                @if (!empty($field['description']))
                    <p class="ax-switch-row__help" id="{{ $helpId }}">{{ $field['description'] }}</p>
                @endif
                @if ($hasError)
                    <div class="invalid-feedback d-block" id="{{ $errorId }}">{{ $errors->first($key) }}</div>
                @endif
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="{{ $inputName }}" value="0">
                <input type="checkbox" id="{{ $fieldId }}" name="{{ $inputName }}" value="1" class="form-check-input" role="switch"
                       @checked($value) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            </div>
        </div>
    </div>
@else
    <div class="ax-field {{ $isWide ? 'ax-field--full' : '' }}">
        <label class="form-label" for="{{ $fieldId }}">
            {{ $label }}
            @if (!empty($field['required']))<span class="ax-req" aria-hidden="true">*</span>@endif
        </label>

        @if ($type === 'text' || $type === 'number' || $type === 'url')
            <input type="{{ $type === 'number' ? 'number' : 'text' }}" id="{{ $fieldId }}" name="{{ $inputName }}" value="{{ $value }}"
                   class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                   @if ($type === 'number' && isset($field['min'])) min="{{ $field['min'] }}" @endif
                   @if ($type === 'number' && isset($field['max'])) max="{{ $field['max'] }}" @endif
                   @if ($type === 'number') step="any" @endif
                   @if (!empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                   @if (!empty($field['required'])) aria-required="true" @endif
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>

        @elseif ($type === 'email')
            <div class="input-group">
                <span class="input-group-text"><i class="ri-mail-line" aria-hidden="true"></i></span>
                <input type="email" id="{{ $fieldId }}" name="{{ $inputName }}" value="{{ $value }}"
                       class="form-control {{ $hasError ? 'is-invalid' : '' }}" autocomplete="off"
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            </div>

        @elseif ($type === 'textarea')
            <textarea id="{{ $fieldId }}" name="{{ $inputName }}" rows="3"
                      class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                      @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $value }}</textarea>

        @elseif ($type === 'color')
            <div class="ax-color">
                <input type="color" id="{{ $fieldId }}" name="{{ $inputName }}" value="{{ $value ?: '#000000' }}"
                       class="form-control form-control-color {{ $hasError ? 'is-invalid' : '' }}" data-ax-color
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <code data-ax-color-value>{{ $value ?: '#000000' }}</code>
            </div>

        @elseif ($type === 'select')
            <select id="{{ $fieldId }}" name="{{ $inputName }}" class="form-select {{ $hasError ? 'is-invalid' : '' }}"
                    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                @foreach ($field['options'] ?? [] as $option)
                    <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
                @endforeach
            </select>

        @elseif ($type === 'password')
            {{-- The saved secret is never rendered; leaving the field empty keeps it. --}}
            <div class="input-group">
                <input type="password" id="{{ $fieldId }}" name="{{ $inputName }}" value="" autocomplete="new-password"
                       class="form-control {{ $hasError ? 'is-invalid' : '' }}"
                       placeholder="{{ filled($settings[$key] ?? null) ? 'Saved — leave empty to keep' : 'Enter value' }}"
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <button type="button" class="btn btn-outline-secondary" data-ax-reveal aria-label="Show value" aria-controls="{{ $fieldId }}">
                    <i class="ri-eye-line" aria-hidden="true"></i>
                </button>
            </div>

        @elseif ($type === 'file')
            @php
                $isSquare = str_contains($key, 'favicon');
                $currentUrl = $value ? asset('storage/' . $value) : null;
            @endphp
            <div class="ax-upload" data-ax-upload>
                <div class="ax-upload__preview {{ $isSquare ? 'ax-upload__preview--square' : '' }}" data-ax-logo data-ax-upload-preview>
                    @if ($currentUrl)
                        <img src="{{ $currentUrl }}" alt="Current {{ strtolower($label) }}">
                    @else
                        <i class="ri-image-line ax-upload__empty" aria-hidden="true"></i>
                    @endif
                </div>
                <div class="ax-upload__body">
                    <div class="ax-upload__actions">
                        <input type="file" id="{{ $fieldId }}" name="{{ $inputName }}" class="ax-file-input"
                               accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.ico,image/*" data-ax-upload-input
                               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                        <label for="{{ $fieldId }}" class="btn btn-outline-secondary btn-sm mb-0">
                            <i class="ri-upload-2-line me-1" aria-hidden="true"></i>{{ $currentUrl ? 'Replace' : 'Upload' }}
                        </label>
                        <button type="button" class="btn btn-ghost btn-sm" data-ax-upload-reset hidden>Undo</button>
                        <span class="ax-upload__file" data-ax-upload-name>{{ $currentUrl ? 'Current file' : 'No file uploaded' }}</span>
                    </div>
                    <div class="ax-upload__meta">JPG, PNG, GIF, WebP, SVG or ICO · max 2 MB{{ $isSquare ? ' · square, at least 32×32 px' : ' · transparent PNG or SVG recommended' }}</div>
                </div>
            </div>
        @endif

        @if ($hasError)
            <div class="invalid-feedback d-block" id="{{ $errorId }}">{{ $errors->first($key) }}</div>
        @endif
        @if (!empty($field['description']))
            <div class="form-text" id="{{ $helpId }}">{{ $field['description'] }}</div>
        @endif
    </div>
@endif
