<?php

use App\Http\Controllers\Student\StudentAuthController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentSessionController;
use App\Http\Controllers\Student\StudentTeacherController;
use App\Http\Controllers\LiveSession\LiveSessionRoomController;
use App\Http\Controllers\Teacher\TeacherAuthController;
use App\Http\Controllers\Teacher\TeacherAvailabilityController;
use App\Http\Controllers\Teacher\TeacherComplaintController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherInstantSessionController;
use App\Http\Controllers\Teacher\TeacherProfileController;
use App\Http\Controllers\Teacher\TeacherSessionController;
use App\Http\Controllers\Teacher\TeacherSubjectController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::redirect('/', '/student/login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.pages.dashboard')->name('dashboard');
    Route::view('/students', 'admin.pages.students.index')->name('students.index');
    Route::view('/courses', 'admin.pages.courses.index')->name('courses.index');
    Route::view('/reports', 'admin.pages.reports.index')->name('reports.index');
    Route::view('/settings', 'admin.pages.settings.index')->name('settings.index');
});

Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/login', [TeacherAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [TeacherAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [TeacherAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [TeacherAuthController::class, 'register'])->name('register.store');

    Route::middleware('teacher.auth')->group(function (): void {
        Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');
        Route::post('/instant-availability', [TeacherInstantSessionController::class, 'toggle'])->name('instant.toggle');
        Route::get('/instant-requests/poll', [TeacherInstantSessionController::class, 'poll'])->name('instant.poll');
        Route::post('/instant-requests/{liveRequestId}/accept', [TeacherInstantSessionController::class, 'accept'])->name('instant.accept');
        Route::post('/instant-requests/{liveRequestId}/reject', [TeacherInstantSessionController::class, 'reject'])->name('instant.reject');
        Route::get('/subjects', [TeacherSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [TeacherSubjectController::class, 'store'])->name('subjects.store');
        Route::get('/profile', [TeacherProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
        Route::get('/availability', [TeacherAvailabilityController::class, 'index'])->name('availability.index');
        Route::post('/availability', [TeacherAvailabilityController::class, 'store'])->name('availability.store');
        Route::get('/sessions', [TeacherSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions/{sessionId}/cancel', [TeacherSessionController::class, 'cancel'])->name('sessions.cancel');
        Route::post('/sessions/{sessionId}/confirm', [TeacherInstantSessionController::class, 'confirm'])->name('sessions.confirm');
        Route::get('/sessions/{sessionId}/room', [LiveSessionRoomController::class, 'show'])->name('sessions.room.show');
        Route::get('/sessions/{sessionId}/room/state', [LiveSessionRoomController::class, 'state'])->name('sessions.room.state');
        Route::post('/sessions/{sessionId}/room/join', [LiveSessionRoomController::class, 'join'])->name('sessions.room.join');
        Route::post('/sessions/{sessionId}/room/signal', [LiveSessionRoomController::class, 'signal'])->name('sessions.room.signal');
        Route::post('/sessions/{sessionId}/room/message', [LiveSessionRoomController::class, 'message'])->name('sessions.room.message');
        Route::post('/sessions/{sessionId}/room/file', [LiveSessionRoomController::class, 'file'])->name('sessions.room.file');
        Route::post('/sessions/{sessionId}/room/notes', [LiveSessionRoomController::class, 'notes'])->name('sessions.room.notes');
        Route::post('/sessions/{sessionId}/room/complaint', [LiveSessionRoomController::class, 'complaint'])->name('sessions.room.complaint');
        Route::post('/sessions/{sessionId}/room/recording', [LiveSessionRoomController::class, 'recording'])->name('sessions.room.recording');
        Route::post('/sessions/{sessionId}/room/end', [LiveSessionRoomController::class, 'end'])->name('sessions.room.end');
        Route::get('/complaints', [TeacherComplaintController::class, 'index'])->name('complaints.index');
        Route::post('/complaints', [TeacherComplaintController::class, 'store'])->name('complaints.store');
    });
});

Route::prefix('student')->name('student.')->group(function () {
    Route::get('/login', [StudentAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StudentAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [StudentAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [StudentAuthController::class, 'register'])->name('register.store');

    Route::middleware('student.auth')->group(function (): void {
        Route::post('/logout', [StudentAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', StudentDashboardController::class)->name('dashboard');
        Route::get('/profile', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
        Route::get('/sessions', [StudentSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions/book', [StudentSessionController::class, 'storeBooking'])->name('sessions.book');
        Route::post('/sessions/{sessionId}/confirm', [StudentSessionController::class, 'confirm'])->name('sessions.confirm');
        Route::post('/sessions/{sessionId}/cancel', [StudentSessionController::class, 'cancel'])->name('sessions.cancel');
        Route::get('/sessions/{sessionId}/room', [LiveSessionRoomController::class, 'show'])->name('sessions.room.show');
        Route::get('/sessions/{sessionId}/room/state', [LiveSessionRoomController::class, 'state'])->name('sessions.room.state');
        Route::post('/sessions/{sessionId}/room/join', [LiveSessionRoomController::class, 'join'])->name('sessions.room.join');
        Route::post('/sessions/{sessionId}/room/signal', [LiveSessionRoomController::class, 'signal'])->name('sessions.room.signal');
        Route::post('/sessions/{sessionId}/room/message', [LiveSessionRoomController::class, 'message'])->name('sessions.room.message');
        Route::post('/sessions/{sessionId}/room/file', [LiveSessionRoomController::class, 'file'])->name('sessions.room.file');
        Route::post('/sessions/{sessionId}/room/complaint', [LiveSessionRoomController::class, 'complaint'])->name('sessions.room.complaint');
        Route::post('/sessions/{sessionId}/room/recording', [LiveSessionRoomController::class, 'recording'])->name('sessions.room.recording');
        Route::post('/sessions/{sessionId}/room/end', [LiveSessionRoomController::class, 'end'])->name('sessions.room.end');
        Route::get('/teachers', [StudentTeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacherId}', [StudentTeacherController::class, 'show'])->name('teachers.show');
        Route::post('/teachers/{teacherId}/book', [StudentTeacherController::class, 'storeBooking'])->name('teachers.book');
    });
});
