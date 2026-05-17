<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\ChatUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:30',
        ]);

        $name = strip_tags(trim($request->name));

        do {
            $code = strtoupper(Str::random(8));
        } while (Room::where('code', $code)->exists());

        $room = Room::create([
            'code' => $code,
            'expired_at' => now()->addHours(24),
        ]);

        $user = ChatUser::create([
            'room_id' => $room->id,
            'name' => $name,
        ]);

        session([
            'chat_user_id' => $user->id,
            'chat_user_name' => $name,
        ]);

        return redirect()->route('room.show', $code);
    }

    public function show($code)
    {
        $room = Room::where('code', $code)->firstOrFail();

        if ($room->isExpired()) {
            return redirect()->route('home')->with('error', 'Room has expired.');
        }

        $userId = session('chat_user_id');
        $userName = session('chat_user_name');
        $expiredAt = $room->expired_at->toIso8601String();

        return view('room', compact('room', 'userId', 'userName', 'expiredAt'));
    }
}
