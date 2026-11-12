<?php

namespace App\Rules;

use App\Services\Widgets\VideoContainerInspector;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class HeroVideo implements ValidationRule
{
    public function __construct(private string $label = 'Hero video') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail("The {$this->label} could not be uploaded.");

            return;
        }

        if ($value->getSize() <= 0 || $value->getSize() > max(1, config('widgets.hero_video_max_kb', 102400)) * 1024) {
            $fail("The {$this->label} must be non-empty and no larger than ".config('widgets.hero_video_max_kb', 102400).' KiB.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mimes = [
            'mp4' => ['video/mp4', 'application/mp4', 'video/x-m4v'],
            'mov' => ['video/quicktime', 'video/x-quicktime'],
            'webm' => ['video/webm'],
        ];

        if (! in_array($value->getMimeType(), $mimes[$extension] ?? [], true)
            || ! (new VideoContainerInspector)->valid($value->getPathname(), $extension)) {
            $fail("The {$this->label} must be a valid MP4, WebM or MOV video with a matching file extension.");
        }
    }
}
