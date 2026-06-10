<?php

namespace App\Infrastructure\Notifications\Handlers;

use App\Application\Notification\Contracts\NotificationHandlerInterface;
use App\Application\Notification\DTOs\NotificationDTO;

class GenericNotificationHandler implements NotificationHandlerInterface
{
    public function handle(int $tenantId, array $context): NotificationDTO
    {
        $event  = $context['__event__'];
        $config = config("notification.event_types.{$event}");

        $title = $this->renderTemplate($config['title_template'], $context);
        $recipientIds = $this->resolveRecipients($config['recipients'], $context);
        $url = isset($config['url_template'])
            ? $this->buildRoute($config['url_template'], $context)
            : '';

        return new NotificationDTO(
            event:        $event,
            recipientIds: $recipientIds,
            title:        $title,
            body:         null,
            url:          $url,
            data:         $context,
        );
    }

    private function renderTemplate(string $template, array $context): string
    {
        return preg_replace_callback(
            '/{(\w+)}/',
            fn($m) => $context[$m[1]] ?? $m[0],
            $template
        );
    }

    private function resolveRecipients($config, array $context): array
    {
        if (is_string($config)) {
            return [$context[$config]];
        }

        if (is_array($config)) {
            return array_values(array_filter(
                array_map(fn($k) => $context[$k] ?? null, $config)
            ));
        }

        return [];
    }

    private function buildRoute(string $template, array $context): string
    {
        if (!preg_match('/^(\w+\.\w+):(.+)$/', $template, $m)) {
            return '';
        }

        $routeName = $m[1];
        $paramKey  = $m[2];
        $paramValue = $context[$paramKey] ?? null;

        return $paramValue ? route($routeName, $paramValue) : '';
    }
}
