<?php

namespace Flute\Modules\DiscordWebhooks\Listeners;

use Flute\Modules\DiscordWebhooks\Services\WebhookSender;
use Throwable;

class WebhookListener
{
    private WebhookSender $sender;

    public function __construct(WebhookSender $sender)
    {
        $this->sender = $sender;
    }

    public function onUserRegistered($event): void
    {
        $this->safe('flute.user_registered', function () use ($event) {
            $user = $event->getUser();
            $this->sender->dispatch('flute.user_registered', [
                'user' => $user,
                'title' => '🆕 ' . __('admin-discord-wh.events.user_registered'),
                'description' =>
                    '**'
                        . $user->name
                        . '** '
                        . __('admin-discord-wh.embed.registered_on')
                        . ' '
                        . config('app.name', 'Flute'),
                'color' => 0x57F287,
            ]);
        });
    }

    public function onUserLoggedIn($event): void
    {
        $this->safe('flute.user_logged_in', function () use ($event) {
            $this->sender->dispatch('flute.user_logged_in', [
                'user' => $event->getUser(),
                'title' => '🔑 ' . __('admin-discord-wh.events.user_logged_in'),
                'color' => 0x5865F2,
            ]);
        });
    }

    public function onUserVerified($event): void
    {
        $this->safe('flute.user_verified', function () use ($event) {
            $user = $event->getUser();
            $this->sender->dispatch('flute.user_verified', [
                'user' => $user,
                'title' => '✅ ' . __('admin-discord-wh.events.user_verified'),
                'description' => '**' . $user->name . '** ' . __('admin-discord-wh.embed.verified'),
                'color' => 0x57F287,
            ]);
        });
    }

    public function onSocialProviderAdded($event): void
    {
        $this->safe('flute.social_provider_added', function () use ($event) {
            $fields = [];

            if (method_exists($event, 'getSocialNetwork')) {
                $sn = $event->getSocialNetwork();
                $fields['🔗 ' . __('admin-discord-wh.embed.provider')] =
                    '`' . ( $sn->socialNetwork->key ?? 'Unknown' ) . '`';
            }

            $user = method_exists($event, 'getUser')
                ? $event->getUser()
                : ($event->getSocialNetwork()->user ?? null);

            $this->sender->dispatch('flute.social_provider_added', [
                'user' => $user,
                'title' => '🔗 ' . __('admin-discord-wh.events.social_linked'),
                'color' => 0x5865F2,
                'fields' => $fields,
            ]);
        });
    }

    public function onPaymentSuccess($event): void
    {
        $this->safe('payment.success', function () use ($event) {
            $user = $event->getUser();
            $invoice = $event->getInvoice();
            $fields = [];

            if ($invoice->originalAmount ?? null) {
                $currency = $invoice->currency->code ?? '';
                $fields['💵 ' . __('admin-discord-wh.embed.amount')] =
                    '**' . $invoice->originalAmount . ' ' . $currency . '**';
            }

            if ($invoice->promoCode ?? null) {
                $fields['🏷️ ' . __('admin-discord-wh.embed.promo')] = '`' . $invoice->promoCode->code . '`';
            }

            $this->sender->dispatch('payment.success', [
                'user' => $user,
                'title' => '💰 ' . __('admin-discord-wh.events.payment_success'),
                'color' => 0xFEE75C,
                'fields' => $fields,
            ]);
        });
    }

    public function onPaymentFailed($event): void
    {
        $this->safe('payment.failed', function () {
            $this->sender->dispatch('payment.failed', [
                'title' => '❌ ' . __('admin-discord-wh.events.payment_failed'),
                'description' => __('admin-discord-wh.embed.payment_failed_desc'),
                'color' => 0xED4245,
            ]);
        });
    }

    private function safe(string $event, callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            logs()->warning("Discord webhook [{$event}]: {$e->getMessage()}");
        }
    }
}
