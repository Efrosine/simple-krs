<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassCatalogController;
use App\Http\Controllers\EnrollmentController;
use App\Events\DemoMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('classes.index');
});

Route::view('/socket-demo', 'socket-demo')->name('socket-demo');

Route::get('/api/node', function () {
    return response()->json([
        'node_id' => config('node.id', 'local-node'),
        'hostname' => gethostname() ?: null,
        'status' => 'ok',
        'checked_at' => now()->toIso8601String(),
    ]);
})->name('node.info');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/classes', [ClassCatalogController::class, 'index'])->name('classes.index');\n    Route::get('/admin/cluster-status', [ClusterMonitoringController::class, 'status'])->name('admin.cluster-status');
    Route::post('/classes/{course}/enroll', [EnrollmentController::class, 'store'])->name('classes.enroll');
    Route::delete('/classes/{course}/enroll', [EnrollmentController::class, 'destroy'])->name('classes.drop');

    Route::get('/admin', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);

        return view('admin.index', [
            'studentCount' => \App\Models\User::where('role', 'student')->count(),
            'courseCount' => \App\Models\Course::count(),
            'enrollmentCount' => \App\Models\Enrollment::count(),
        ]);
    })->name('admin.index');
});

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
