<?php

use Flute\Core\Router\Router;
use Flute\Modules\DiscordWebhooks\Admin\Package\Screens\DiscordWebhooksScreen;

Router::screen('/admin/discord-webhooks', DiscordWebhooksScreen::class);
