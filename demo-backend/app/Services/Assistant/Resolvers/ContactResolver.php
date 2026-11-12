<?php

namespace App\Services\Assistant\Resolvers;

use App\Models\PageWidget;
use App\Services\SettingService;

/**
 * Verified contact details from the CMS settings (the same values the site
 * footer shows) and the public contact page. Values that fail validation are
 * left out rather than shown.
 */
class ContactResolver
{
    public function __construct(private SettingService $settings, private PagesResolver $pages) {}

    /** @return array{email: ?string, phone: ?string, address: list<string>, hours: ?string, maps_url: ?string, page: ?array, booking: bool} */
    public function details(int $channelId): array
    {
        $get = fn (string $key) => trim((string) ($this->settings->get($key, null, $channelId) ?? ''));

        $email = $get('footer.contact_email') ?: $get('store.email');
        $phone = $get('footer.contact_phone') ?: $get('store.phone');
        $maps = $get('footer.maps_url');
        $address = array_values(array_filter(array_map(fn ($line) => Text::plain($line, 120), preg_split('/\r\n|\r|\n/', $get('footer.address')) ?: [])));
        $page = $this->pages->findBySlug($channelId, 'contact');

        return [
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => preg_match('/^\+?[0-9 ().-]{5,30}$/', $phone) && preg_match('/\d{5,}/', preg_replace('/\D/', '', $phone)) ? $phone : null,
            'address' => $address,
            'hours' => Text::plain($get('footer.opening_hours'), 120) ?: null,
            'maps_url' => self::safeWebUrl($maps),
            'page' => $page,
            'booking' => $page !== null && PageWidget::query()->active()->where('page_id', $page['page']->id)
                ->whereHas('widget', fn ($q) => $q->active()->visibleInChannel($channelId)->where('type', 'meeting_booking'))
                ->exists(),
        ];
    }

    public static function safeWebUrl(string $url): ?string
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && ! isset($parts['user']) && ! isset($parts['pass']) ? $url : null;
    }
}
