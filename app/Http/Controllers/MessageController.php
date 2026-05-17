<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\ChatUser;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index($code)
    {
        $room = Room::where('code', $code)->firstOrFail();

        if ($room->isExpired()) {
            return response()->json(['error' => 'Room has expired.'], 410);
        }

        $userId = session('chat_user_id');
        if ($userId) {
            ChatUser::where('id', $userId)->where('room_id', $room->id)->update(['last_seen' => now()]);
        }

        $messages = Message::where('room_id', $room->id)
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'name' => $msg->user->name ?? 'Unknown',
                    'message' => e($msg->message),
                    'time' => $msg->created_at->format('H:i'),
                ];
            });

        $onlineCount = ChatUser::where('room_id', $room->id)
            ->where('last_seen', '>=', now()->subSeconds(30))
            ->count();

        return response()->json([
            'messages' => $messages,
            'online_count' => $onlineCount,
        ]);
    }

    public function store(Request $request, $code)
    {
        $userId = session('chat_user_id');
        if (!$userId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $room = Room::where('code', $code)->firstOrFail();

        if ($room->isExpired()) {
            return response()->json(['error' => 'Room has expired.'], 410);
        }

        $message = strip_tags(trim($request->message));
        if (empty($message)) {
            return response()->json(['error' => 'Message cannot be empty.'], 422);
        }

        $msg = Message::create([
            'room_id' => $room->id,
            'user_id' => $userId,
            'message' => $message,
        ]);

        ChatUser::where('id', $userId)->update(['last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'user_id' => $msg->user_id,
                'name' => session('chat_user_name'),
                'message' => e($msg->message),
                'time' => $msg->created_at->format('H:i'),
            ],
        ]);
    }
}
