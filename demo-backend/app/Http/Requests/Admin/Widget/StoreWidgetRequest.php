<?php

namespace App\Http\Requests\Admin\Widget;

use App\Rules\HeroVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void { ProjectsRules::prepare($this); }

    public function rules(): array {
        return [
            'type'         => ['required', 'in:carousel,video,html,hero,separator,contact_form,image_gallery,map,features,faq,testimonials,pricing,service_x,projects,hero-world,meeting_booking,footer_cta'],
            'title'        => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'in:active,inactive'],
            'config'       => ['nullable', 'array'],
            'channels'     => ['nullable', 'array'],
            'channels.*'   => ['integer', 'exists:channels,id'],

            // Widget media uploads
            'config.bg_image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'config.bg_video_file' => ['bail', 'nullable', 'file', 'prohibited_unless:type,hero', new HeroVideo],
            'config.remove_media' => ['sometimes', 'boolean'],
            'config.remove_bg_image' => ['sometimes', 'boolean'],
            'config.slides.*.image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'config.images.*' => ['nullable', 'file', 'image', 'max:5120'],
            'config.items.*.image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'config.items.*.icon_file' => ['nullable', 'file', 'image', 'max:2048'],
            'config.items.*.logo_file' => ['nullable', 'file', 'image', 'max:2048'],
            'config.items.*.media_type' => ['nullable', 'in:icon,image,logo'],

            // Map widget config
            'config.mode' => ['nullable', 'in:coordinates,embed', 'required_if:type,map'],
            'config.location_mode' => [
                'nullable',
                'in:single,multi',
                Rule::requiredIf(fn () => $this->input('type') === 'map' && $this->input('config.mode') === 'coordinates'),
            ],
            'config.provider' => ['nullable', 'in:osm,google,mapbox'],
            'config.lat' => [
                'nullable',
                'numeric',
                'between:-90,90',
                Rule::requiredIf(fn () => $this->input('type') === 'map'
                    && $this->input('config.mode') === 'coordinates'
                    && $this->input('config.location_mode', 'single') === 'single'),
            ],
            'config.lng' => [
                'nullable',
                'numeric',
                'between:-180,180',
                Rule::requiredIf(fn () => $this->input('type') === 'map'
                    && $this->input('config.mode') === 'coordinates'
                    && $this->input('config.location_mode', 'single') === 'single'),
            ],
            'config.zoom' => ['nullable', 'integer', 'between:1,20'],
            'config.embed_url' => [
                'nullable',
                'url',
                Rule::requiredIf(fn () => $this->input('type') === 'map' && $this->input('config.mode') === 'embed'),
            ],
            'config.marker_title' => ['nullable', 'string', 'max:255'],
            'config.marker_text' => ['nullable', 'string', 'max:500'],
            'config.markers' => [
                'nullable',
                'array',
                Rule::requiredIf(fn () => $this->input('type') === 'map'
                    && $this->input('config.mode') === 'coordinates'
                    && $this->input('config.location_mode', 'single') === 'multi'),
            ],
            'config.markers.*.lat' => ['required_with:config.markers', 'numeric', 'between:-90,90'],
            'config.markers.*.lng' => ['required_with:config.markers', 'numeric', 'between:-180,180'],
            'config.markers.*.title' => ['nullable', 'string', 'max:255'],
            'config.markers.*.text' => ['nullable', 'string', 'max:500'],

            'translations' => ['nullable', 'array'],
            'translations.*.locale' => ['required_with:translations', 'string', 'max:10'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.section_label' => ['nullable', 'string', 'max:255'],
            'translations.*.html' => ['nullable', 'string'],
            'translations.*.css' => ['nullable', 'string'],
            'translations.*.js' => ['nullable', 'string'],
            'translations.*.video_url' => ['nullable', 'url'],
            'translations.*.slides' => ['nullable', 'array'],
            'translations.*.slides.*.alt' => ['nullable', 'string', 'max:255'],
            'translations.*.slides.*.headline' => ['nullable', 'string', 'max:255'],
            'translations.*.slides.*.subtitle' => ['nullable', 'string', 'max:255'],
            'translations.*.slides.*.cta_text' => ['nullable', 'string', 'max:255'],
            'translations.*.slides.*.cta_link' => ['nullable', 'string', 'max:1000'],
            'config.rotating_labels' => ['nullable', 'string', 'max:2000'],
            'translations.*.rotating_labels' => ['nullable', 'string', 'max:2000'],
            'translations.*.marker_title' => ['nullable', 'string', 'max:255'],
            'translations.*.marker_text' => ['nullable', 'string', 'max:500'],
            ...($this->input('type') === 'service_x' ? ServiceXRules::rules() : []),
            ...($this->input('type') === 'projects' ? ProjectsRules::rules() : []),
            ...($this->input('type') === 'hero-world' ? HeroWorldRules::rules() : []),
            ...($this->input('type') === 'footer_cta' ? FooterCtaRules::rules() : []),
            ...($this->input('type') === 'meeting_booking' ? MeetingBookingRules::rules((int) $this->user()?->current_channel_id) : []),
        ];
    }

    public function after(): array
    {
        return [function (\Illuminate\Validation\Validator $validator) {
            ProjectsRules::validateImages($this, $validator);
            if ($this->input('type') === 'service_x' && ! $validator->errors()->any()) {
                foreach ($this->input('config.services', []) ?? [] as $index => $service) {
                    if ($this->hasFile("config.services.$index.image_file") && $this->boolean("config.services.$index.remove_image")) {
                        $validator->errors()->add("config.services.$index.image_file", 'Choose an image upload or removal, not both.');
                    }
                }
            }
            $image = $this->hasFile('config.bg_image_file');
            $video = $this->hasFile('config.bg_video_file');
            if (($image && $video) || (($image || $video) && ($this->boolean('config.remove_media') || $this->boolean('config.remove_bg_image')))) {
                $validator->errors()->add('config.bg_image_file', 'Choose one Hero image or video, or remove the existing media; these actions cannot be combined.');
            }

            $user = $this->user();
            if ($user) {
                $allowed = $user->channels->pluck('id')->all();
                if (empty($allowed) || (! $validator->errors()->has('channels') && ! $validator->errors()->has('channels.*')
                    && array_diff((array) $this->input('channels', []), $allowed))) {
                    $validator->errors()->add('channels', 'Choose only channels you have access to.');
                }
            }
        }];
    }

}
