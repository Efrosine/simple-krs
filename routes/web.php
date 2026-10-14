<?php

use App\Events\DemoMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/socket-demo', 'socket-demo');

Route::post('/socket-demo/publish', function (Request $request) {
    $validated = $request->validate([
        'message' => ['required', 'string', 'max:200'],
    ]);

    $message = new DemoMessage(
        message: $validated['message'],
        sentAt: now()->toIso8601String(),
    );

    broadcast($message);

    return response()->json([
        'ok' => true,
        'message' => $message->message,
        'sent_at' => $message->sentAt,
    ]);
})->name('socket-demo.publish');
