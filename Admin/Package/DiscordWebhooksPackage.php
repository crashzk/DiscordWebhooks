<?php

namespace Flute\Modules\DiscordWebhooks\Admin\Package;

use Flute\Admin\Support\AbstractAdminPackage;

class DiscordWebhooksPackage extends AbstractAdminPackage
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadRoutesFromFile('routes.php');
        $this->loadViews('Resources/views', 'admin-discord-wh');
        $this->loadTranslations('Resources/lang');
    }

    public function getPermissions(): array
    {
        return ['admin'];
    }

    public function getMenuItems(): array
    {
        return [
            [
                'title' => __('admin-discord-wh.title'),
                'icon' => 'ph.bold.webhooks-logo-bold',
                'url' => url('/admin/discord-webhooks'),
            ],
        ];
    }

    public function getPriority(): int
    {
        return 50;
    }
}
