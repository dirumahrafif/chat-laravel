<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ChatUserController;
use App\Http\Controllers\MessageController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/room', [RoomController::class, 'store'])->name('room.store');

Route::middleware(['check.room.expired'])->group(function () {
    Route::get('/room/{code}', [RoomController::class, 'show'])->name('room.show');

    Route::post('/room/{code}/join', [ChatUserController::class, 'join'])->name('room.join');

    Route::post('/room/{code}/typing', [ChatUserController::class, 'typing'])->name('room.typing');

    Route::get('/room/{code}/typing/status', [ChatUserController::class, 'typingStatus'])->name('room.typing.status');

    Route::post('/room/{code}/leave', [ChatUserController::class, 'leave'])->name('room.leave');

    Route::get('/room/{code}/messages', [MessageController::class, 'index'])->name('room.messages');

    Route::post('/room/{code}/messages', [MessageController::class, 'store'])->name('room.messages.store')->middleware('throttle:30,1');
});
