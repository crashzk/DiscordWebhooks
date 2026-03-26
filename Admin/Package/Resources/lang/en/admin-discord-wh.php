<?php

return [
    'title' => 'Discord Notifications',
    'description' => 'Set up Discord channels to receive site event notifications.',

    'add' => 'Add Channel',
    'edit' => 'Edit',
    'enable' => 'Enable',
    'disable' => 'Disable',
    'test' => 'Test',
    'test_title' => 'Test Message',
    'test_description' => '✅ Channel is working!',

    'cols' => [
        'channel' => 'Channel',
        'events' => 'Notifications',
        'stats' => 'Stats',
        'actions' => 'Actions',
    ],

    'empty' => [
        'title' => 'No channels yet',
        'sub' => 'Add a Discord channel to start receiving site event notifications.',
    ],

    'embed' => [
        'registered_on' => 'registered on',
        'verified' => 'verified their account',
        'provider' => 'Provider',
        'amount' => 'Amount',
        'promo' => 'Promo code',
        'payment_failed_desc' => 'A payment attempt has failed.',
    ],

    'fields' => [
        'channel_id' => 'Discord Channel ID',
        'url' => 'Webhook URL',
        'events' => 'Events',
        'color' => 'Color',
        'bot_name' => 'Bot name',
        'bot_avatar' => 'Bot avatar',
    ],

    'placeholders' => [
        'events' => 'Select events...',
    ],

    'hints' => [
        'channel_id' => 'Right-click the channel in Discord → "Copy Channel ID". Requires Developer Mode in Discord settings.',
        'url' => 'Channel Settings → Integrations → Webhooks → Create → Copy URL.',
        'events' => 'Which events to send to this channel.',
        'color' => 'Stripe on the left side of messages.',
        'bot_name' => 'Optional. How the bot appears in Discord.',
        'bot_avatar' => 'Optional. Square image.',
    ],

    'events' => [
        'user_registered' => 'Registration',
        'user_logged_in' => 'Login',
        'user_verified' => 'Verification',
        'social_linked' => 'Social linked',
        'payment_success' => 'Payment success',
        'payment_failed' => 'Payment failed',
    ],

    'messages' => [
        'saved' => 'Channel saved.',
        'deleted' => 'Channel deleted.',
        'test_ok' => 'Test sent — check Discord!',
        'test_fail' => 'Failed to send. Check webhook URL.',
    ],

    'errors' => [
        'no_channel_id' => 'Enter Discord channel ID.',
        'invalid_url' => 'Must be a Discord Webhook URL.',
        'no_events' => 'Select at least one event.',
    ],

    'confirms' => [
        'delete' => 'Delete channel? Notifications will stop.',
    ],
];
