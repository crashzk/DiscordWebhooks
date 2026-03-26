<?php

namespace Flute\Modules\DiscordWebhooks\Admin\Package\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Support\FileUploader;
use Flute\Modules\DiscordWebhooks\database\Entities\DiscordWebhook;
use Flute\Modules\DiscordWebhooks\Services\WebhookSender;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DiscordWebhooksScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin';

    public $webhooks;

    public function mount(): void
    {
        $this->name = __('admin-discord-wh.title');
        $this->description = __('admin-discord-wh.description');
        $this->webhooks = DiscordWebhook::query()->orderBy('id', 'DESC')->fetchAll();

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-discord-wh.title'));
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-discord-wh.add'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.plus-bold')
                ->modal('createChannelModal'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('webhooks', [
                TD::make('channel', __('admin-discord-wh.cols.channel'))
                    ->render(static function (DiscordWebhook $wh) {
                        $dot = $wh->enabled
                            ? '<span style="color:var(--success);font-size:9px">●</span>'
                            : '<span style="color:var(--text-700);font-size:9px">●</span>';

                        $id = '<code style="font-size:12px">#' . e($wh->channelId) . '</code>';

                        $bot = $wh->botName
                            ? '<div style="font-size:12px;color:var(--text-600);margin-top:2px">'
                            . e($wh->botName)
                            . '</div>'
                            : '';

                        return (
                            '<div style="display:flex;align-items:start;gap:6px">'
                            . $dot
                            . '<div>'
                            . $id
                            . $bot
                            . '</div></div>'
                        );
                    })
                    ->width('200px'),

                TD::make('events', __('admin-discord-wh.cols.events'))->render(static function (DiscordWebhook $wh) {
                    $all = WebhookSender::events();
                    $out = [];

                    foreach ($wh->getEvents() as $ev) {
                        $label = $all[$ev] ?? $ev;
                        $out[] =
                            '<span style="display:inline-block;padding:2px 8px;border-radius:6px;background:var(--transp-05);font-size:12px;margin:2px 2px 2px 0">'
                            . e($label)
                            . '</span>';
                    }

                    return $out
                        ? '<div style="display:flex;flex-wrap:wrap">' . implode('', $out) . '</div>'
                        : '<span style="color:var(--text-700)">—</span>';
                }),

                TD::make('stats', __('admin-discord-wh.cols.stats'))
                    ->render(static function (DiscordWebhook $wh) {
                        $sent = '<span style="color:var(--success)">' . $wh->sendCount . '</span>';
                        $fail = $wh->failCount > 0
                            ? ' / <span style="color:var(--error)">' . $wh->failCount . '</span>'
                            : '';

                        $last = $wh->lastSentAt
                            ? '<div style="font-size:11px;color:var(--text-700);margin-top:2px">'
                            . $wh->lastSentAt->format('d.m H:i')
                            . '</div>'
                            : '';

                        return '<div>' . $sent . $fail . $last . '</div>';
                    })
                    ->width('110px'),

                TD::make('actions', __('admin-discord-wh.cols.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(static fn(DiscordWebhook $wh) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('admin-discord-wh.test'))
                                ->method('testChannel', ['id' => $wh->id])
                                ->icon('ph.bold.paper-plane-right-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('admin-discord-wh.edit'))
                                ->modal('editChannelModal', ['channel' => $wh->id])
                                ->icon('ph.bold.pencil-simple-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(
                                $wh->enabled ? __('admin-discord-wh.disable') : __('admin-discord-wh.enable'),
                            )
                                ->method('toggleChannel', ['id' => $wh->id])
                                ->icon($wh->enabled ? 'ph.bold.pause-bold' : 'ph.bold.play-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),
                            DropDownItem::make(__('def.delete'))
                                ->confirm(__('admin-discord-wh.confirms.delete'))
                                ->method('deleteChannel', ['id' => $wh->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ])),
            ])
                ->empty(
                    'ph.regular.webhooks-logo',
                    __('admin-discord-wh.empty.title'),
                    __('admin-discord-wh.empty.sub'),
                )
                ->emptyButton(
                    Button::make(__('admin-discord-wh.add'))->icon('ph.bold.plus-bold')->modal('createChannelModal'),
                ),
        ];
    }

    // ─── Modals ─────────────────────────────────────────

    public function createChannelModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, $this->buildFormFields())
            ->title(__('admin-discord-wh.add'))
            ->applyButton(__('def.save'))
            ->method('saveChannel');
    }

    public function editChannelModal(Repository $parameters)
    {
        $channelId = $parameters->get('channel');
        $wh = DiscordWebhook::findByPK($channelId);

        if (!$wh) {
            $this->flashMessage(__('def.not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, $this->buildFormFields($wh))
            ->title(__('admin-discord-wh.edit'))
            ->applyButton(__('def.save'))
            ->method('updateChannel');
    }

    // ─── Actions ────────────────────────────────────────

    public function saveChannel(): void
    {
        $data = request()->input();

        if (!$this->validateChannel($data)) {
            return;
        }

        $wh = new DiscordWebhook();
        $this->fillChannel($wh, $data);
        $wh->save();

        app(WebhookSender::class)->clearCache();
        $this->webhooks = DiscordWebhook::query()->orderBy('id', 'DESC')->fetchAll();
        $this->flashMessage(__('admin-discord-wh.messages.saved'), 'success');
        $this->closeModal();
    }

    public function updateChannel(): void
    {
        $data = request()->input();
        $channelId = $this->modalParams->get('channel');
        $wh = DiscordWebhook::findByPK($channelId);

        if (!$wh) {
            $this->flashMessage(__('def.not_found'), 'error');

            return;
        }

        if (!$this->validateChannel($data)) {
            return;
        }

        $this->fillChannel($wh, $data);
        $wh->save();

        app(WebhookSender::class)->clearCache();
        $this->webhooks = DiscordWebhook::query()->orderBy('id', 'DESC')->fetchAll();
        $this->flashMessage(__('admin-discord-wh.messages.saved'), 'success');
        $this->closeModal();
    }

    public function deleteChannel(): void
    {
        $wh = DiscordWebhook::findByPK((int) request()->input('id'));

        if ($wh) {
            $wh->delete();
            app(WebhookSender::class)->clearCache();
            $this->webhooks = DiscordWebhook::query()->orderBy('id', 'DESC')->fetchAll();
            $this->flashMessage(__('admin-discord-wh.messages.deleted'), 'success');
        }
    }

    public function toggleChannel(): void
    {
        $wh = DiscordWebhook::findByPK((int) request()->input('id'));

        if ($wh) {
            $wh->enabled = !$wh->enabled;
            $wh->save();
            app(WebhookSender::class)->clearCache();
            $this->webhooks = DiscordWebhook::query()->orderBy('id', 'DESC')->fetchAll();
        }
    }

    public function testChannel(): void
    {
        $wh = DiscordWebhook::findByPK((int) request()->input('id'));

        if (!$wh) {
            return;
        }

        $siteName = config('app.name', 'Flute');
        $siteUrl = rtrim(config('app.url', ''), '/');
        $events = WebhookSender::events();

        $eventLabels = [];

        foreach ($wh->getEvents() as $ev) {
            $eventLabels[] = $events[$ev] ?? $ev;
        }

        $embed = [
            'title' => '🧪 ' . __('admin-discord-wh.test_title'),
            'description' => __('admin-discord-wh.test_description'),
            'color' => $wh->color ?? 0x5865F2,
            'timestamp' => date('c'),
            'fields' => [
                ['name' => '📌 Channel', 'value' => '`#' . $wh->channelId . '`', 'inline' => true],
                ['name' => '📡 Events', 'value' => implode(', ', $eventLabels) ?: '—', 'inline' => false],
            ],
        ];

        if ($siteUrl) {
            $embed['url'] = $siteUrl;

            $logo = config('app.logo', '');
            $ext = strtolower(pathinfo($logo, PATHINFO_EXTENSION));

            if ($logo && in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                $embed['thumbnail'] = ['url' => $siteUrl . '/' . ltrim($logo, '/')];
            }
        }

        $embed['footer'] = ['text' => $siteName];

        if ($siteUrl) {
            $embed['footer']['icon_url'] = $siteUrl . '/favicon.ico';
        }

        $payload = [
            'embeds' => [$embed],
            'username' => $wh->botName ?: $siteName,
        ];

        if ($wh->botAvatar) {
            $payload['avatar_url'] = $wh->botAvatar;
        } elseif ($siteUrl) {
            $payload['avatar_url'] = $siteUrl . '/favicon.ico';
        }

        $ok = app(WebhookSender::class)->sendRaw($wh->url, $payload);

        $this->flashMessage(
            $ok ? __('admin-discord-wh.messages.test_ok') : __('admin-discord-wh.messages.test_fail'),
            $ok ? 'success' : 'error',
        );
    }

    // ─── Form builder ───────────────────────────────────

    private function buildFormFields(?DiscordWebhook $wh = null): array
    {
        $fields = [];

        $fields[] = LayoutFactory::field(
            Input::make('channel_id')
                ->type('text')
                ->value($wh?->channelId ?? '')
                ->placeholder('1234567890123456789'),
        )
            ->label(__('admin-discord-wh.fields.channel_id'))
            ->small(__('admin-discord-wh.hints.channel_id'))
            ->required();

        $fields[] = LayoutFactory::field(
            Input::make('url')
                ->type('url')
                ->value($wh?->url ?? '')
                ->placeholder('https://discord.com/api/webhooks/...'),
        )
            ->label(__('admin-discord-wh.fields.url'))
            ->small(__('admin-discord-wh.hints.url'))
            ->required();

        $fields[] = LayoutFactory::field(
            Select::make('events')
                ->options(WebhookSender::events())
                ->value($wh ? $wh->getEvents() : null)
                ->multiple()
                ->placeholder(__('admin-discord-wh.placeholders.events')),
        )
            ->label(__('admin-discord-wh.fields.events'))
            ->small(__('admin-discord-wh.hints.events'))
            ->required();

        $fields[] = LayoutFactory::field(
            Input::make('color')
                ->type('color')
                ->value($wh?->color ? '#' . str_pad(dechex($wh->color), 6, '0', STR_PAD_LEFT) : '#5865f2'),
        )
            ->label(__('admin-discord-wh.fields.color'))
            ->small(__('admin-discord-wh.hints.color'));

        $fields[] = LayoutFactory::field(
            Input::make('bot_name')
                ->type('text')
                ->value($wh?->botName ?? '')
                ->placeholder(config('app.name', 'Flute')),
        )
            ->label(__('admin-discord-wh.fields.bot_name'))
            ->small(__('admin-discord-wh.hints.bot_name'));

        $fields[] = LayoutFactory::field(
            Input::make('bot_avatar')
                ->type('file')
                ->filePond()
                ->accept('image/png, image/jpeg, image/gif, image/webp')
                ->defaultFile($wh?->botAvatar ?: null),
        )
            ->label(__('admin-discord-wh.fields.bot_avatar'))
            ->small(__('admin-discord-wh.hints.bot_avatar'));

        return $fields;
    }

    // ─── Helpers ────────────────────────────────────────

    private function validateChannel(array $data): bool
    {
        $channelId = trim($data['channel_id'] ?? '');
        $url = trim($data['url'] ?? '');

        if ($channelId === '') {
            $this->flashMessage(__('admin-discord-wh.errors.no_channel_id'), 'error');

            return false;
        }

        if ($url === '' || !str_contains($url, 'discord.com/api/webhooks/')) {
            $this->flashMessage(__('admin-discord-wh.errors.invalid_url'), 'error');

            return false;
        }

        $events = $this->extractEvents($data);

        if (empty($events)) {
            $this->flashMessage(__('admin-discord-wh.errors.no_events'), 'error');

            return false;
        }

        return true;
    }

    private function fillChannel(DiscordWebhook $wh, array $data): void
    {
        $wh->channelId = trim($data['channel_id']);
        $wh->name = trim($data['channel_id']);
        $wh->url = trim($data['url']);
        $wh->setEvents($this->extractEvents($data));

        $color = $data['color'] ?? '';

        if ($color && str_starts_with($color, '#')) {
            $wh->color = hexdec(ltrim($color, '#'));
        }

        $wh->botName = !empty($data['bot_name']) ? $data['bot_name'] : null;

        $file = request()->files->get('bot_avatar');

        if ($file instanceof UploadedFile && $file->isValid()) {
            $uploader = app(FileUploader::class);
            $path = $uploader->uploadImage($file, 2);

            if ($path) {
                $wh->botAvatar = url($path);
            }
        } elseif (!empty($data['bot_avatar_clear'])) {
            $wh->botAvatar = null;
        }
    }

    private function extractEvents(array $data): array
    {
        $raw = $data['events'] ?? [];

        if (is_string($raw)) {
            return array_filter(array_map('trim', explode(',', $raw)));
        }

        if (!is_array($raw)) {
            return [];
        }

        $events = [];

        foreach ($raw as $key => $value) {
            if (is_numeric($value) || is_string($value)) {
                $events[] = (string) $value;

                continue;
            }

            if ($value === 'on' || $value === true || $value === '1') {
                $events[] = (string) $key;
            }
        }

        return array_values(array_unique(array_filter($events)));
    }
}
