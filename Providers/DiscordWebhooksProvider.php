<?php

namespace Flute\Modules\DiscordWebhooks\Providers;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\DiscordWebhooks\Admin\Package\DiscordWebhooksPackage;
use Flute\Modules\DiscordWebhooks\Listeners\WebhookListener;
use Flute\Modules\DiscordWebhooks\Services\WebhookSender;

class DiscordWebhooksProvider extends ModuleServiceProvider
{
    public array $extensions = [];

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();

        if (is_admin_path()) {
            $this->loadPackage(new DiscordWebhooksPackage());
        }

        $container->set(WebhookSender::class, new WebhookSender());

        $sender = $container->get(WebhookSender::class);
        $listener = new WebhookListener($sender);
        $ev = events();

        $ev->addListener('flute.user_registered', [$listener, 'onUserRegistered']);
        $ev->addListener('flute.user_logged_in', [$listener, 'onUserLoggedIn']);
        $ev->addListener('flute.user_verified', [$listener, 'onUserVerified']);
        $ev->addListener('flute.social_provider_added', [$listener, 'onSocialProviderAdded']);
        $ev->addListener('payment.success', [$listener, 'onPaymentSuccess']);
        $ev->addListener('payment.failed', [$listener, 'onPaymentFailed']);
    }

    public function register(\DI\Container $container): void
    {
    }
}
