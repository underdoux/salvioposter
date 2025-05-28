<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count (for AJAX requests).
     */
    public function unreadCount()
    {
        try {
            $count = auth()->user()->notifications()
                ->unread()
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to get unread notifications count: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get notifications count'], 500);
        }
    }

    /**
     * Get recent notifications (for dropdown menu).
     */
    public function recent()
    {
        try {
            $data = $this->notificationService->getUserNotifications(auth()->user(), true);

            return response()->json([
                'notifications' => $data['notifications'],
                'unread_count' => $data['unread_count']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get recent notifications: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get notifications'], 500);
        }
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(Request $request)
    {
        try {
            $notificationIds = $request->input('notification_ids', []);
            $this->notificationService->markAsRead(auth()->user(), $notificationIds);

            return response()->json(['message' => 'Notifications marked as read']);
        } catch (\Exception $e) {
            Log::error('Failed to mark notifications as read: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update notifications'], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        try {
            $this->notificationService->markAsRead(auth()->user());
            return redirect()->back()->with('success', 'All notifications marked as read.');
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update notifications.');
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification)
    {
        try {
            if ($notification->user_id !== auth()->id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $notification->delete();
            return response()->json(['message' => 'Notification deleted']);
        } catch (\Exception $e) {
            Log::error('Failed to delete notification: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete notification'], 500);
        }
    }

    /**
     * Clear all notifications.
     */
    public function clearAll()
    {
        try {
            auth()->user()->notifications()->delete();
            return redirect()->back()->with('success', 'All notifications cleared.');
        } catch (\Exception $e) {
            Log::error('Failed to clear notifications: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to clear notifications.');
        }
    }
}
