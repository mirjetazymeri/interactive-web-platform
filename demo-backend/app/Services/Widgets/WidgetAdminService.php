<?php

namespace App\Services\Widgets;

use App\Models\Widget;
use App\Services\Widgets\Types\CarouselWidgetTypeService;
use App\Services\Widgets\Types\ContactFormWidgetTypeService;
use App\Services\Widgets\Types\FaqWidgetTypeService;
use App\Services\Widgets\Types\FeaturesWidgetTypeService;
use App\Services\Widgets\Types\HeroWidgetTypeService;
use App\Services\Widgets\Types\HtmlWidgetTypeService;
use App\Services\Widgets\Types\ImageGalleryWidgetTypeService;
use App\Services\Widgets\Types\MapWidgetTypeService;
use App\Services\Widgets\Types\PricingWidgetTypeService;
use App\Services\Widgets\Types\SeparatorWidgetTypeService;
use App\Services\Widgets\Types\ServiceXWidgetTypeService;
use App\Services\Widgets\Types\TestimonialsWidgetTypeService;
use App\Services\Widgets\Types\VideoWidgetTypeService;
use App\Services\Widgets\Types\WidgetTypeConfigService;
use Illuminate\Http\Request;

class WidgetAdminService
{
    /** @var array<string, WidgetTypeConfigService> */
    private array $typeServices;

    public function __construct(
        CarouselWidgetTypeService $carouselService,
        VideoWidgetTypeService $videoService,
        HtmlWidgetTypeService $htmlService,
        HeroWidgetTypeService $heroService,
        SeparatorWidgetTypeService $separatorService,
        ContactFormWidgetTypeService $contactFormService,
        ImageGalleryWidgetTypeService $imageGalleryService,
        MapWidgetTypeService $mapService,
        FeaturesWidgetTypeService $featuresService,
        FaqWidgetTypeService $faqService,
        TestimonialsWidgetTypeService $testimonialsService,
        PricingWidgetTypeService $pricingService,
        public readonly HeroMediaService $heroMedia,
        ServiceXWidgetTypeService $serviceXService,
        \App\Services\Widgets\Types\ProjectsWidgetTypeService $projectsService,
        \App\Services\Widgets\Types\HeroWorldWidgetTypeService $heroWorldService,
        \App\Services\Widgets\Types\MeetingBookingWidgetTypeService $meetingBookingService,
        \App\Services\Widgets\Types\FooterCtaWidgetTypeService $footerCtaService
    ) {
        $this->typeServices = [
            'projects' => $projectsService,
            $heroWorldService->type()     => $heroWorldService,
            $meetingBookingService->type() => $meetingBookingService,
            $footerCtaService->type()     => $footerCtaService,
            $serviceXService->type()      => $serviceXService,
            $carouselService->type()      => $carouselService,
            $videoService->type()         => $videoService,
            $htmlService->type()          => $htmlService,
            $heroService->type()          => $heroService,
            $separatorService->type()     => $separatorService,
            $contactFormService->type()   => $contactFormService,
            $imageGalleryService->type()  => $imageGalleryService,
            $mapService->type()           => $mapService,
            $featuresService->type()      => $featuresService,
            $faqService->type()           => $faqService,
            $testimonialsService->type()  => $testimonialsService,
            $pricingService->type()       => $pricingService,
        ];
    }

    public function getTypes(): array
    {
        return [
            'projects' => __('projects.projects'),
            'hero-world'    => __('hero_world.name'),
            'meeting_booking' => __('meetings.widget_name'),
            'footer_cta'    => __('footer_cta.name'),
            'service_x'     => 'ServiceX',
            'carousel'      => 'Image Carousel',
            'video'         => 'Video Block',
            'html'          => 'Custom HTML',
            'hero'          => 'Hero Banner',
            'separator'     => 'Separator',
            'contact_form'  => 'Contact Form',
            'image_gallery' => 'Image Gallery',
            'map'           => 'Map',
            'features'      => 'Features',
            'faq'           => 'FAQ',
            'testimonials'  => 'Testimonials',
            'pricing'       => 'Pricing',
        ];
    }

    public function getLayouts(): array
    {
        return [
            '1x1'      => '1 x 1 (single)',
            '1x4'      => '1 x 4',
            '1x6'      => '1 x 6',
            '2x3'      => '2 x 3',
            '3x3'      => '3 x 3',
            'carousel' => 'Carousel',
        ];
    }

    public function validatedPayload(Request $request): array
    {
        return $request->validated();
    }

    public function buildConfig(Request $request, string $type, array $incoming, ?array $existing): array
    {
        $existing = $existing ?? [];
        $config = [];

        $config['show_title'] = $request->boolean('config.show_title');
        $config['layout'] = $type === 'carousel'
            ? (string) $request->input('config.layout', '1x1')
            : '1x1';
        $config['section_label'] = trim((string) $request->input('config.section_label', ''));

        if ($type === 'projects' && ! array_key_exists('projects', $incoming)) {
            if (! array_key_exists('show_title', $incoming)) {
                $config['show_title'] = (bool) ($existing['show_title'] ?? false);
            }
            if (! array_key_exists('section_label', $incoming)) {
                $config['section_label'] = $existing['section_label'] ?? '';
            }
        }

        $config['images'] = $this->processImageUploads($request, $existing);
        $config['images_links'] = array_values(array_filter(
            (array) $request->input('config.images_links', $existing['images_links'] ?? [])
        ));
        $config['images_alt'] = array_values((array) $request->input('config.images_alt', []));

        $typeService = $this->typeServices[$type] ?? null;
        if ($typeService !== null) {
            $config = $typeService->extendConfig($request, $config, $incoming, $existing);
        }

        if ($type === 'hero') {
            $config = $this->heroMedia->prepare($request, $config, $existing);
        }

        return $config;
    }

    public function syncChannels(Widget $widget, ?array $channels): void
    {
        $user = auth()->user();

        if (empty($channels)) {
            $channels = [$user->current_channel_id];
        }

        $allowed = $user->channels->pluck('id')->toArray();
        $channels = array_values(array_intersect($channels, $allowed));

        if (empty($channels)) {
            $channels = [$user->current_channel_id];
        }

        $widget->channels()->sync($channels);
    }

    public function syncTranslations(Widget $widget, array $translations): void
    {
        foreach ($translations as $translation) {
            $locale = trim((string) ($translation['locale'] ?? ''));
            if ($locale === '') {
                continue;
            }

            if ($widget->type === 'projects') {
                $row = $widget->translations()->firstOrNew(['locale' => $locale]);
                if (array_key_exists('title', $translation)) {
                    $row->title = trim((string) ($translation['title'] ?? '')) ?: null;
                }
                $config = app(\App\Services\Widgets\Types\ProjectsWidgetTypeService::class)->mergeTranslation((array) $row->config, $translation);
                if (blank($row->title) && empty($config)) {
                    if ($row->exists) {
                        $row->delete();
                    }
                } else {
                    $row->config = $config ?: null;
                    $row->save();
                }
                continue;
            }

            $title = trim((string) ($translation['title'] ?? ''));
            $config = $this->extractTranslationConfig($widget->type, (array) $translation);

            $payload = [
                'title'  => $title !== '' ? $title : null,
                'config' => $config ?: null,
            ];

            if (blank($payload['title']) && empty($payload['config'])) {
                $widget->translations()->where('locale', $locale)->delete();
                continue;
            }

            $widget->translations()->updateOrCreate(
                ['locale' => $locale],
                $payload
            );
        }
    }

    private function extractTranslationConfig(string $type, array $translation): array
    {
        $config = [];

        $sectionLabel = trim((string) ($translation['section_label'] ?? ''));
        if ($sectionLabel !== '') {
            $config['section_label'] = $sectionLabel;
        }

        $typeService = $this->typeServices[$type] ?? null;
        if ($typeService !== null) {
            $config = array_merge($config, $typeService->extractTranslationConfig($translation));
        }

        return $config;
    }

    private function processImageUploads(Request $request, array $existing): array
    {
        $disk = 'public';
        $dir = 'widgets';
        $output = [];

        $files = $request->file('config.images', []);
        $keepKeys = (array) $request->input('config.keep_images', []);

        foreach ($keepKeys as $key) {
            if (!empty($existing['images'][$key])) {
                $output[] = $existing['images'][$key];
            }
        }

        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                $output[] = $file->store($dir, $disk);
            }
        }

        return $output;
    }

}
