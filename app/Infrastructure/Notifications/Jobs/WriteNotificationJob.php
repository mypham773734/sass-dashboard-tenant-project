<?php

namespace App\Infrastructure\Notifications\Jobs;

use App\Application\Notification\Contracts\NotificationHandlerInterface;
use App\Application\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Infrastructure\Notifications\Events\NotificationCreated;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $event,
        private readonly int $tenantId,
        private readonly int $userId,
        private readonly array $context,
    ) {
        $this->onQueue(config('notification.queue', 'notifications'));
    }

    public function handle(NotificationRepositoryInterface $repo): void
    {
        $handler = $this->resolveHandler();
        $dto     = $handler->handle($this->tenantId, $this->context);

        $entity = $repo->createForUser(
            new CreateNotificationDTO(
                event: $dto->event,
                title: $dto->title,
                body:  $dto->body,
                url:   $dto->url,
                data:  $dto->data,
            ),
            userId:   $this->userId,
            tenantId: $this->tenantId,
        );

        broadcast(new NotificationCreated(
            notificationId: $entity->id,
            userId:         $this->userId,
            tenantId:       $this->tenantId,
            title:          $entity->title,
            body:           $entity->body,
            url:            $entity->url,
            createdAt:      Carbon::parse($entity->createdAt),
        ));
    }

    private function resolveHandler(): NotificationHandlerInterface
    {
        $handlerClass = config("notification.event_types.{$this->event}.handler")
            ?? throw new \InvalidArgumentException("Handler not configured for: {$this->event}");

        return app($handlerClass);
    }
}
