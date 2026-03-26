<?php

namespace Flute\Modules\DiscordWebhooks\database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;

/**
 * One record = one Discord webhook channel.
 * Subscribes to multiple events via JSON array.
 */
#[Entity]
#[Table(indexes: [
    new Index(columns: ['enabled']),
])]
#[Behavior\CreatedAt(field: 'createdAt', column: 'created_at')]
#[Behavior\UpdatedAt(field: 'updatedAt', column: 'updated_at')]
class DiscordWebhook extends ActiveRecord
{
    #[Column(type: 'primary')]
    public int $id;

    /** Discord channel ID for display */
    #[Column(type: 'string')]
    public string $channelId;

    #[Column(type: 'string')]
    public string $name;

    /** Full webhook URL */
    #[Column(type: 'string')]
    public string $url;

    /** JSON array of event names */
    #[Column(type: 'text')]
    public string $events = '[]';

    #[Column(type: 'boolean', default: true)]
    public bool $enabled = true;

    #[Column(type: 'integer', nullable: true)]
    public ?int $color = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $botName = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $botAvatar = null;

    #[Column(type: 'integer', default: 0)]
    public int $sendCount = 0;

    #[Column(type: 'integer', default: 0)]
    public int $failCount = 0;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $lastSentAt = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    /** @return string[] */
    public function getEvents(): array
    {
        return json_decode($this->events, true) ?: [];
    }

    /** @param string[] $events */
    public function setEvents(array $events): void
    {
        $this->events = json_encode(array_values(array_filter($events)));
    }

    public function hasEvent(string $event): bool
    {
        return in_array($event, $this->getEvents(), true);
    }

    public function recordSuccess(): void
    {
        $this->sendCount++;
        $this->lastSentAt = new \DateTimeImmutable();
    }

    public function recordFailure(): void
    {
        $this->failCount++;
    }

    /**
     * Build full URL from webhook ID + token.
     */
    public static function buildUrl(string $webhookId, string $token): string
    {
        return "https://discord.com/api/webhooks/{$webhookId}/{$token}";
    }

    /**
     * Parse webhook ID from URL.
     */
    public function getWebhookId(): ?string
    {
        if (preg_match('#/webhooks/(\d+)/#', $this->url, $m)) {
            return $m[1];
        }

        return null;
    }
}
