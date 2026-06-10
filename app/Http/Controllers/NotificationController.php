<?php

namespace App\Http\Controllers;

use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    public function index(): View
    {
        $userId = auth()->id();
        $tenantId = tenantContext()->getId();

        $notifications = \App\Models\Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->notificationRepository->markAsRead($id, auth()->id());

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): \Illuminate\Http\RedirectResponse
    {
        $this->notificationRepository->markAllAsRead(auth()->id(), tenantContext()->getId());

        return back()->with('success', 'All notifications marked as read.');
    }
}
