<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\ChatUser;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatUserController extends Controller
{
    public function join(Request $request, $code)
    {
        $request->validate([
            'name' => 'required|string|max:30',
        ]);

        $room = Room::where('code', $code)->firstOrFail();

        if ($room->isExpired()) {
            return response()->json(['error' => 'Room has expired.'], 410);
        }

        $name = strip_tags(trim($request->name));

        $existingNames = ChatUser::where('room_id', $room->id)
            ->pluck('name')
            ->toArray();

        if (!in_array($name, $existingNames)) {
            $finalName = $name;
        } else {
            $suffix = 2;
            while (in_array($name . ' (' . $suffix . ')', $existingNames)) {
                $suffix++;
            }
            $finalName = $name . ' (' . $suffix . ')';
        }

        $user = ChatUser::create([
            'room_id' => $room->id,
            'name' => $finalName,
            'last_seen' => now(),
        ]);

        Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => $finalName . ' joined the room',
        ]);

        session([
            'chat_user_id' => $user->id,
            'chat_user_name' => $finalName,
        ]);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'name' => $finalName,
        ]);
    }

    public function typing(Request $request, $code)
    {
        $userId = session('chat_user_id');
        if (!$userId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $room = Room::where('code', $code)->firstOrFail();
        $user = ChatUser::where('id', $userId)->where('room_id', $room->id)->first();

        if ($user) {
            $user->update(['typing_at' => now(), 'last_seen' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function typingStatus(Request $request, $code)
    {
        $userId = session('chat_user_id');
        $room = Room::where('code', $code)->firstOrFail();

        $typingUsers = ChatUser::where('room_id', $room->id)
            ->where('id', '!=', $userId)
            ->where('typing_at', '>=', now()->subSeconds(3))
            ->pluck('name');

        return response()->json(['typing' => $typingUsers]);
    }

    public function leave(Request $request, $code)
    {
        $userId = session('chat_user_id');
        if (!$userId) {
            return redirect()->route('home');
        }

        $room = Room::where('code', $code)->firstOrFail();
        $user = ChatUser::find($userId);

        if ($user) {
            Message::create([
                'room_id' => $room->id,
                'user_id' => $userId,
                'message' => $user->name . ' left the room',
            ]);
        }

        session()->forget(['chat_user_id', 'chat_user_name']);

        return redirect()->route('home')->with('info', 'You left the room.');
    }
}
