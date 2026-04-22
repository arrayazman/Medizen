<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Semua notifikasi ditandai telah dibaca');
    }

    public function getLatest()
    {
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $latest = Auth::user()->unreadNotifications()->latest()->take(5)->get();

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $latest
        ]);
    }
}
