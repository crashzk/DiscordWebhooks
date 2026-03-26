<?php

namespace Flute\Modules\DiscordWebhooks\Services;

use Flute\Core\Database\Entities\User;
use Flute\Modules\DiscordWebhooks\database\Entities\DiscordWebhook;
use Throwable;

/**
 * Discord webhook notification service.
 *
 * ## For modules — register events in boot():
 *
 *   WebhookSender::event('shop.purchase', '🛒 Покупка в магазине');
 *   WebhookSender::event('shop.refund', '↩️ Возврат');
 *
 * ## For modules — send notifications:
 *
 *   app(WebhookSender::class)->dispatch('shop.purchase', [
 *       'user'   => $user,
 *       'title'  => 'Новая покупка',
 *       'fields' => ['Товар' => 'VIP', 'Сумма' => '100₽'],
 *       'color'  => 0x57F287,
 *   ]);
 */
class WebhookSender
{
    /** @var DiscordWebhook[]|null */
    private ?array $channels = null;

    /** @var array<string, string> event_key => label */
    private static array $registry = [];

    // ─── Public API ─────────────────────────────────────

    /**
     * Register an event type. Call in module boot().
     * Shows up in the admin multiselect.
     */
    public static function event(string $key, string $label): void
    {
        self::$registry[$key] = $label;
    }

    /**
     * Get all available events (core + modules).
     *
     * @return array<string, string>
     */
    public static function events(): array
    {
        return array_merge(self::coreEvents(), self::$registry);
    }

    /**
     * Dispatch notification to all channels subscribed to this event.
     */
    public function dispatch(string $event, array $data = []): void
    {
        foreach ($this->resolve($event) as $ch) {
            $this->send($ch, $data);
        }
    }

    /**
     * Send raw payload to a URL (for testing / one-off).
     */
    public function sendRaw(string $url, array $payload): bool
    {
        return $this->post($url, $payload);
    }

    public function clearCache(): void
    {
        $this->channels = null;
    }

    // ─── Core events ────────────────────────────────────

    private static function coreEvents(): array
    {
        return [
            'flute.user_registered' => '🆕 ' . __('admin-discord-wh.events.user_registered'),
            'flute.user_logged_in' => '🔑 ' . __('admin-discord-wh.events.user_logged_in'),
            'flute.user_verified' => '✅ ' . __('admin-discord-wh.events.user_verified'),
            'flute.social_provider_added' => '🔗 ' . __('admin-discord-wh.events.social_linked'),
            'payment.success' => '💰 ' . __('admin-discord-wh.events.payment_success'),
            'payment.failed' => '❌ ' . __('admin-discord-wh.events.payment_failed'),
        ];
    }

    // ─── Sending ────────────────────────────────────────

    private function send(DiscordWebhook $ch, array $data): void
    {
        $user = $data['user'] ?? null;
        $color = $ch->color ?? $data['color'] ?? 0x5865F2;
        $site = self::siteInfo();

        $embed = [
            'color' => $color,
            'timestamp' => date('c'),
        ];

        // Title — clickable link to site
        $title = $data['title'] ?? '';

        if ($title !== '') {
            $embed['title'] = $title;

            if ($site['url']) {
                $embed['url'] = $site['url'];
            }
        }

        // Description
        if (!empty($data['description'])) {
            $embed['description'] = $data['description'];
        }

        // Author — user info with avatar, clickable to profile
        if ($user instanceof User) {
            $author = ['name' => $user->name];

            if ($user->avatar) {
                $author['icon_url'] = self::absUrl($user->avatar, $site['url']);
            }

            if ($site['url']) {
                $author['url'] = $site['url'] . '/profile/' . $user->id;
            }

            $embed['author'] = $author;
        }

        // Fields
        $fields = [];

        if ($user instanceof User && $user->login) {
            $fields[] = ['name' => 'Login', 'value' => '`' . $user->login . '`', 'inline' => true];
        }

        if ($user instanceof User && $site['url']) {
            $fields[] = [
                'name' => 'Profile',
                'value' => '[→ Open](' . $site['url'] . '/profile/' . $user->id . ')',
                'inline' => true,
            ];
        }

        foreach ($data['fields'] ?? [] as $k => $v) {
            $fields[] = ['name' => (string) $k, 'value' => (string) $v, 'inline' => true];
        }

        if ($fields) {
            $embed['fields'] = $fields;
        }

        // Thumbnail — site logo (PNG/JPEG only, Discord ignores SVG)
        if ($site['logo']) {
            $embed['thumbnail'] = ['url' => $site['logo']];
        }

        // Footer — site name + favicon
        $footer = ['text' => $site['name']];

        if ($site['favicon']) {
            $footer['icon_url'] = $site['favicon'];
        }

        $embed['footer'] = $footer;

        // Payload
        $payload = ['embeds' => [$embed]];

        // Bot appearance — fallback to site name
        $payload['username'] = $ch->botName ?: $site['name'];

        if ($ch->botAvatar) {
            $payload['avatar_url'] = $ch->botAvatar;
        } elseif ($site['favicon']) {
            $payload['avatar_url'] = $site['favicon'];
        }

        $ok = $this->post($ch->url, $payload);

        try {
            $ok ? $ch->recordSuccess() : $ch->recordFailure();
            $ch->save();
        } catch (Throwable) {
            // @mago-expect no-empty-catch-clause
        }
    }

    /**
     * Gather site info once per request.
     *
     * @return array{name: string, url: string, logo: string|null, favicon: string|null}
     */
    private static function siteInfo(): array
    {
        static $info;

        if ($info !== null) {
            return $info;
        }

        $url = rtrim(config('app.url', ''), '/');
        $name = config('app.name', 'Flute');

        // Logo — Discord needs PNG/JPEG/GIF, not SVG
        $logo = config('app.logo', '');
        $logoUrl = null;

        if ($logo && $url) {
            $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));

            // SVG doesn't render in Discord embeds — skip
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                $logoUrl = self::absUrl($logo, $url);
            }
        }

        // Favicon
        $favicon = $url ? $url . '/favicon.ico' : null;

        $info = [
            'name' => $name,
            'url' => $url,
            'logo' => $logoUrl,
            'favicon' => $favicon,
        ];

        return $info;
    }

    private static function absUrl(string $path, string $baseUrl): string
    {
        return str_starts_with($path, 'http') ? $path : $baseUrl . '/' . ltrim($path, '/');
    }

    // ─── Resolution ─────────────────────────────────────

    /** @return DiscordWebhook[] */
    private function resolve(string $event): array
    {
        $this->load();

        $result = [];

        foreach ($this->channels as $ch) {
            if ($ch->hasEvent($event)) {
                $result[] = $ch;
            }
        }

        return $result;
    }

    private function load(): void
    {
        if ($this->channels !== null) {
            return;
        }

        try {
            $this->channels = DiscordWebhook::query()->where('enabled', true)->fetchAll();
        } catch (Throwable) {
            // @mago-expect no-empty-catch-clause Table may not exist before migration
            $this->channels = [];
        }
    }

    // ─── HTTP ───────────────────────────────────────────

    private function post(string $url, array $payload): bool
    {
        try {
            // Validate webhook URL is a Discord domain to prevent SSRF
            $parsed = parse_url($url);
            $host = strtolower($parsed['host'] ?? '');
            if (
                !in_array($parsed['scheme'] ?? '', ['http', 'https'], true)
                || !str_ends_with($host, 'discord.com') && !str_ends_with($host, 'discordapp.com')
            ) {
                logs()->warning('Discord webhook URL rejected (not a Discord domain)', [
                    'url' => mb_substr($url, 0, 50),
                ]);

                return false;
            }

            // Discord requires the URL to end with /slack or just the webhook path
            // Append ?wait=true to get response body on success (helps debugging)
            $sendUrl = $url . ( str_contains($url, '?') ? '&' : '?' ) . 'wait=true';

            // Clean payload — remove null values that Discord rejects
            $payload = array_filter($payload, static fn($v) => $v !== null);

            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $ch = curl_init($sendUrl);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json),
                ],
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                logs()->warning("Discord webhook curl error: {$curlError}", [
                    'url' => mb_substr($url, 0, 50),
                ]);

                return false;
            }

            // Discord returns 200 (with ?wait=true) or 204 on success
            if ($code >= 200 && $code < 300) {
                return true;
            }

            logs()->warning("Discord webhook HTTP {$code}", [
                'url' => mb_substr($url, 0, 50),
                'body' => mb_substr((string) $resp, 0, 300),
            ]);

            return false;
        } catch (Throwable $e) {
            logs()->warning("Discord webhook error: {$e->getMessage()}");

            return false;
        }
    }
}
