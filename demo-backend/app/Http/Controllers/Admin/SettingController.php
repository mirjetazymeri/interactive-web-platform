<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SettingService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class SettingController extends Controller
{
    public function index(SettingService $settings)
    {
        return view('admin.settings.index', [
            'settings' => $settings->all(),
            'schema'   => config('settings'),
        ]);
    }

    public function layoutPreview(Request $request, SettingService $settings)
    {
        $storedSettings = $settings->all();
        $layoutInput = $request->input('layout', []);

        $previewSettings = $storedSettings;

        foreach ((array) $layoutInput as $key => $value) {
            $previewSettings["layout.{$key}"] = $value;
        }

        return view('admin.settings.layout-preview', [
            'settings' => $previewSettings,
        ]);
    }

    public function update(Request $request, SettingService $settings)
    {
        $channelId = auth()->user()->current_channel_id;

        abort_unless(
            auth()->user()->channels()->where('channel_id', $channelId)->exists(),
            403
        );

        $schema     = config('settings');
        // Fields with a 'panel' are saved by their dedicated page (SEO, Tracking). This form
        // never renders them, so treating them as submitted would reset their switches.
        $flatSchema = array_filter($this->flattenSchema($schema), fn (array $field) => empty($field['panel']));

        // -------------------------
        // VALIDATION
        // -------------------------
        $rules = [];

        foreach ($flatSchema as $key => $field) {
            $rules[$key] = $this->buildValidation($field);
        }

        $validated = $request->validate($rules);

        // -------------------------
        // KEEP EXISTING VALUES
        // -------------------------
        $current = $settings->all($channelId);
        $data    = $current;

        // Detect submitted fields — use bracket-name format to match actual request keys
        // We build a dot-keyed map from the raw request for boolean detection
        $flatInput = Arr::dot($request->except('_token', '_method'));

        // -------------------------
        // PROCESS FIELDS
        // -------------------------
        foreach ($flatSchema as $key => $field) {

            $inputName = $this->keyToInputName($key);
            $type      = $field['type'] ?? null;

            // -------------------------------------------------------
            // FIX Bug 5: FILE — only update if a new file was uploaded.
            // If no file submitted, keep the existing stored path as-is.
            // -------------------------------------------------------
            if ($type === 'file') {
                // hasFile() and file() need dot-notation (e.g. "general.logo"),
                // NOT bracket-notation (e.g. "general[logo]") — use $key directly.
                if ($request->hasFile($key)) {
                    $old = $settings->get($key, null, $channelId);

                    if ($old && Storage::disk('public')->exists($old)) {
                        Storage::disk('public')->delete($old);
                    }

                    $data[$key] = $request->file($key)->store('settings', 'public');
                }
                // No new file uploaded → keep existing value in $data unchanged
                continue;
            }

            // -------------------------------------------------------
            // FIX Bug 1: BOOLEAN — unchecked checkboxes are never
            // present in the request, so absence means false, not skip.
            // -------------------------------------------------------
            if ($type === 'boolean') {
                // The hidden input sends "0" when unchecked, "1" when checked.
                // array_key_exists on dot-flattened input handles both cases.
                $data[$key] = array_key_exists($key, $flatInput)
                    ? (bool) $flatInput[$key]
                    : false;
                continue;
            }

            // Saved secrets are never sent back to the browser, so an empty field keeps
            // the stored value; a new value is stored encrypted (SettingService decrypts it).
            if ($type === 'password') {
                if (filled($flatInput[$key] ?? null)) {
                    $data[$key] = Crypt::encryptString((string) ($validated[$key] ?? $flatInput[$key]));
                }
                continue;
            }

            // All other field types — only update if present in the request
            if (array_key_exists($key, $flatInput)) {
                $data[$key] = $validated[$key] ?? $flatInput[$key];
            }
        }

        $settings->setMany($data, $channelId);

        return back()->with('success', 'Settings updated');
    }

    /**
     * Send a test email to the authenticated user.
     */
    public function testEmail(Request $request)
    {
        $channelId = auth()->user()->current_channel_id;

        abort_unless(
            auth()->user()->channels()->where('channel_id', $channelId)->exists(),
            403
        );

        $email = auth()->user()->email;
        $template = $request->input('template', 'welcome');

        try {
            // Dispatch test mails using MailService
            $mailService = app(\App\Services\Mail\MailService::class);

            match ($template) {
                'welcome' => \Illuminate\Support\Facades\Mail::to($email)
                    ->send(new \App\Mail\WelcomeMail(auth()->user())),
                'verify-email' => \Illuminate\Support\Facades\Mail::to($email)
                    ->send(new \App\Mail\VerifyEmailMail(
                        auth()->user(),
                        route('verification.verify', [auth()->id(), 'hash' => 'TEST-HASH'])
                    )),
                'login-alert' => \Illuminate\Support\Facades\Mail::to($email)
                    ->send(new \App\Mail\LoginAlertMail(
                        auth()->user(),
                        request()->ip(),
                        request()->userAgent(),
                        now()->format('d M Y, H:i')
                    )),
                default => abort(400, 'Unknown template'),
            };

            return response()->json(['success' => true, 'message' => 'Test email sent to ' . $email]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Mail transport errors name the SMTP account; the details go to the (redacted) log only.
            report($e);

            return response()->json(
                ['success' => false, 'message' => 'Failed to send test email. Check the mail settings; the error is recorded in the application log.'],
                500
            );
        }
    }

    // -------------------------
    // HELPERS
    // -------------------------

    protected function keyToInputName(string $key): string
    {
        $parts = explode('.', $key);
        return array_shift($parts) . collect($parts)->map(fn($p) => "[{$p}]")->implode('');
    }

    protected function flattenSchema(array $schema, string $prefix = ''): array
    {
        $result = [];

        foreach ($schema as $key => $value) {

            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (isset($value['type'])) {
                $result[$fullKey] = $value;
                continue;
            }

            if (is_array($value)) {
                $result += $this->flattenSchema($value, $fullKey);
            }
        }

        return $result;
    }

    protected function buildValidation(array $field): array
    {
        $rules = [];

        $rules[] = !empty($field['required']) ? 'required' : 'nullable';

        switch ($field['type']) {
            case 'text':
                $rules[] = 'string';
                $rules[] = 'max:1000';
                break;

            case 'color':
                $rules[] = 'string';
                $rules[] = 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
                break;

            case 'file':
                // 'sometimes' skips all other rules when the field is absent/empty.
                // Without it, 'nullable|file' still fails on an empty file input.
                $rules[] = 'sometimes';
                $rules[] = 'file';
                $rules[] = 'mimes:jpg,jpeg,png,gif,webp,svg,ico';
                $rules[] = 'max:2048';
                break;

            case 'number':
                $rules[] = 'numeric';
                if (isset($field['min'])) {
                    $rules[] = 'min:' . $field['min'];
                }
                if (isset($field['max'])) {
                    $rules[] = 'max:' . $field['max'];
                }
                break;

            case 'email':
                $rules[] = 'string';
                $rules[] = 'email';
                $rules[] = 'max:255';
                break;

            case 'textarea':
                $rules[] = 'string';
                $rules[] = 'max:5000';
                break;

            case 'boolean':
                $rules[] = 'boolean';
                break;

            case 'password':
                $rules[] = 'string';
                $rules[] = 'max:1000';
                break;

            // FIX Bug 2: Added missing select validation with in: rule
            case 'select':
                $rules[] = 'string';
                if (!empty($field['options'])) {
                    $rules[] = 'in:' . implode(',', $field['options']);
                }
                break;
        }

        return $rules;
    }
}
