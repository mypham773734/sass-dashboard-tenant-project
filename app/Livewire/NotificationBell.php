<?php

namespace App\Livewire;

use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;
    public array $notifications = [];
    public bool $isOpen = false;
    public int $userId = 0;
    public int $tenantId = 0;

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {
        parent::__construct();
    }

    public function mount(): void
    {
        $authId = authContext()->getId(); 
        $this->userId = $authId ?? 0;
        $this->tenantId = tenantContext()->getId() ?? 0;
        $this->refresh();
    }

    #[On('echo-private:tenant.{tenantId}.user.{userId},notification-created')]
    public function onNotificationCreated($data): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $userId = authContext()->getId(); 
        $tenantId = tenantContext()->getId();

        if (!$userId || !$tenantId) {
            return;
        }

        $this->unreadCount = $this->notificationRepository->countUnreadByUser($userId, $tenantId);

        $this->notifications = collect(
            $this->notificationRepository->getUnreadByUser($userId, $tenantId, 10)
        )->map(function ($notification) {
            return [
                'id'    => $notification->id,
                'title' => $notification->title,
                'body'  => $notification->body,
                'url'   => $notification->url,
                'isRead' => $notification->isRead,
                'createdAt' => $notification->createdAt,
            ];
        })->toArray();
    }

    public function markRead(int $notificationId): void
    {
        $this->notificationRepository->markAsRead($notificationId, auth()->id());
        $this->refresh();
    }

    public function markAllAsRead(): void
    {
        $this->notificationRepository->markAllAsRead(auth()->id(), tenantContext()->getId());
        $this->isOpen = false;
        $this->refresh();
    }

    public function toggleDropdown(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
