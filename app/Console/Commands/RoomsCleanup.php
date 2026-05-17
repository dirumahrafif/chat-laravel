<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rooms:cleanup')]
#[Description('Delete all expired rooms and their related data')]
class RoomsCleanup extends Command
{
    public function handle()
    {
        $expiredRooms = \App\Models\Room::where('expired_at', '<=', now())->get();

        $count = $expiredRooms->count();
        foreach ($expiredRooms as $room) {
            $room->delete();
        }

        $this->info("Deleted {$count} expired room(s).");
    }
}
