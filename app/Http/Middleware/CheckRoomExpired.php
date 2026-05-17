<?php

namespace App\Http\Middleware;

use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoomExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->route('code');
        if ($code) {
            $room = Room::where('code', $code)->first();
            if ($room && $room->isExpired()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Room has expired.'], 410);
                }
                return redirect()->route('home')->with('error', 'Room has expired.');
            }
        }

        return $next($request);
    }
}
