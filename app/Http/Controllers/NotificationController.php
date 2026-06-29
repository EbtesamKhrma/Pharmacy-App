<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ===== عرض كل الإشعارات =====
    public function getNotifications(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $notifications = Notification::where('pharmacy_id', $request->pharmacy_id)
            ->latest()
            ->get();

        return response()->json([
            'unread_count'  => $notifications->where('is_read', false)->count(),
            'notifications' => $notifications,
        ]);
    }

    // ===== تعليم إشعار كمقروء =====
    public function markAsRead($id): \Illuminate\Http\JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'تم تعليم الإشعار كمقروء',
        ]);
    }

    // ===== تعليم كل الإشعارات كمقروءة =====
    public function markAllAsRead(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        Notification::where('pharmacy_id', $request->pharmacy_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'تم تعليم كل الإشعارات كمقروءة',
        ]);
    }

    // ===== حذف إشعار =====
    public function deleteNotification($id): \Illuminate\Http\JsonResponse
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'تم حذف الإشعار',
        ]);
    }
}
