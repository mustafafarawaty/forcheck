<?php

use App\Http\Controllers\AgoraController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminTeacherController;
use App\Http\Controllers\Admin\AdminWalletController;
use App\Http\Controllers\LiveSession\LiveSessionRoomController;
use App\Http\Controllers\Student\StudentAuthController;
use App\Http\Controllers\Student\StudentComplaintController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentSessionController;
use App\Http\Controllers\Student\StudentTeacherController;
use App\Http\Controllers\Student\StudentWalletController;
use App\Http\Controllers\Teacher\TeacherAuthController;
use App\Http\Controllers\Teacher\TeacherAvailabilityController;
use App\Http\Controllers\Teacher\TeacherComplaintController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherInstantSessionController;
use App\Http\Controllers\Teacher\TeacherProfileController;
use App\Http\Controllers\Teacher\TeacherSessionController;
use App\Http\Controllers\Teacher\TeacherSubjectController;
use App\Http\Controllers\Teacher\TeacherWalletController;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/student/login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
    Route::get('/teachers/{teacher}/edit', [AdminTeacherController::class, 'edit'])->name('teachers.edit');
    Route::get('/teachers/{teacher}', [AdminTeacherController::class, 'show'])->name('teachers.show');
    Route::put('/teachers/{teacher}', [AdminTeacherController::class, 'update'])->name('teachers.update');
    Route::post('/teachers/{teacher}/approve', [AdminTeacherController::class, 'approve'])->name('teachers.approve');
    Route::post('/teachers/{teacher}/reject', [AdminTeacherController::class, 'reject'])->name('teachers.reject');
    Route::post('/teachers/{teacher}/toggle-disabled', [AdminTeacherController::class, 'toggleDisabled'])->name('teachers.toggle-disabled');
    Route::delete('/teachers/{teacher}', [AdminTeacherController::class, 'destroy'])->name('teachers.destroy');
    Route::get('/teachers/{teacher}/sessions', [AdminTeacherController::class, 'sessions'])->name('teachers.sessions');
    Route::get('/teachers/{teacher}/wallet', [AdminTeacherController::class, 'wallet'])->name('teachers.wallet');
    Route::get('/teachers/{teacher}/complaints', [AdminTeacherController::class, 'complaints'])->name('teachers.complaints');
    Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [AdminStudentController::class, 'update'])->name('students.update');
    Route::post('/students/{student}/toggle-disabled', [AdminStudentController::class, 'toggleDisabled'])->name('students.toggle-disabled');
    Route::delete('/students/{student}', [AdminStudentController::class, 'destroy'])->name('students.destroy');
    Route::get('/students/{student}/sessions', [AdminStudentController::class, 'sessions'])->name('students.sessions');
    Route::get('/students/{student}/wallet', [AdminStudentController::class, 'wallet'])->name('students.wallet');
    Route::get('/students/{student}/complaints', [AdminStudentController::class, 'complaints'])->name('students.complaints');
    Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
    Route::patch('/complaints/{complaint}/status', [AdminComplaintController::class, 'updateStatus'])->name('complaints.status');
    Route::post('/complaints/{complaint}/reply', [AdminComplaintController::class, 'reply'])->name('complaints.reply');
    Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [AdminSessionController::class, 'show'])->name('sessions.show');
    Route::get('/wallet', [AdminWalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/{transaction}', [AdminWalletController::class, 'show'])->name('wallet.show');
    Route::patch('/wallet/{transaction}', [AdminWalletController::class, 'update'])->name('wallet.update');
    Route::post('/wallet/{transaction}/approve', [AdminWalletController::class, 'approve'])->name('wallet.approve');
    Route::post('/wallet/{transaction}/reject', [AdminWalletController::class, 'reject'])->name('wallet.reject');
    Route::view('/courses', 'admin.pages.courses.index')->name('courses.index');
    Route::view('/reports', 'admin.pages.reports.index')->name('reports.index');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});

Route::get('/agora/token', [AgoraController::class, 'token']);


Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/login', [TeacherAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [TeacherAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [TeacherAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [TeacherAuthController::class, 'register'])->name('register.store');

    Route::middleware('teacher.auth')->group(function (): void {
        Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');
        Route::post('/instant-availability', [TeacherInstantSessionController::class, 'toggle'])->name('instant.toggle');
        Route::post('/instant-availability/heartbeat', [TeacherInstantSessionController::class, 'heartbeat'])->name('instant.heartbeat');
        Route::post('/instant-availability/offline', [TeacherInstantSessionController::class, 'offline'])->name('instant.offline');
        Route::get('/instant-requests/poll', [TeacherInstantSessionController::class, 'poll'])->name('instant.poll');
        Route::post('/instant-requests/{liveRequestId}/accept', [TeacherInstantSessionController::class, 'accept'])->name('instant.accept');
        Route::post('/instant-requests/{liveRequestId}/reject', [TeacherInstantSessionController::class, 'reject'])->name('instant.reject');
        Route::get('/subjects', [TeacherSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/subjects', [TeacherSubjectController::class, 'store'])->name('subjects.store');
        Route::get('/profile', [TeacherProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [TeacherProfileController::class, 'update'])->name('profile.update');
        Route::get('/wallet', [TeacherWalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/{transaction}', [TeacherWalletController::class, 'show'])->name('wallet.show');
        Route::post('/wallet/withdraw', [TeacherWalletController::class, 'withdraw'])->name('wallet.withdraw');
        Route::post('/wallet/{transaction}/cancel', [TeacherWalletController::class, 'cancel'])->name('wallet.cancel');
        Route::post('/wallet/{transaction}/complaint', [TeacherWalletController::class, 'complaint'])->name('wallet.complaint');
        Route::get('/availability', [TeacherAvailabilityController::class, 'index'])->name('availability.index');
        Route::post('/availability', [TeacherAvailabilityController::class, 'store'])->name('availability.store');
        Route::get('/sessions', [TeacherSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session}', [TeacherSessionController::class, 'show'])->name('sessions.show');
        Route::post('/sessions/{session}/complaint', [TeacherSessionController::class, 'complaint'])->name('sessions.complaint');
        Route::post('/sessions/{sessionId}/cancel', [TeacherSessionController::class, 'cancel'])->name('sessions.cancel');
        Route::post('/sessions/{sessionId}/confirm', [TeacherInstantSessionController::class, 'confirm'])->name('sessions.confirm');
        Route::get('/sessions/{sessionId}/room', [LiveSessionRoomController::class, 'show'])->name('sessions.room.show');
        Route::get('/sessions/{sessionId}/room/state', [LiveSessionRoomController::class, 'state'])->name('sessions.room.state');
        Route::post('/sessions/{sessionId}/room/join', [LiveSessionRoomController::class, 'join'])->name('sessions.room.join');
        Route::post('/sessions/{sessionId}/room/signal', [LiveSessionRoomController::class, 'signal'])->name('sessions.room.signal');
        Route::get('/sessions/{sessionId}/room/agora-token', [LiveSessionRoomController::class, 'agoraToken'])->name('sessions.room.agora-token');
        Route::post('/sessions/{sessionId}/room/message', [LiveSessionRoomController::class, 'message'])->name('sessions.room.message');
        Route::post('/sessions/{sessionId}/room/file', [LiveSessionRoomController::class, 'file'])->name('sessions.room.file');
        Route::post('/sessions/{sessionId}/room/notes', [LiveSessionRoomController::class, 'notes'])->name('sessions.room.notes');
        Route::post('/sessions/{sessionId}/room/complaint', [LiveSessionRoomController::class, 'complaint'])->name('sessions.room.complaint');
        Route::post('/sessions/{sessionId}/room/recording', [LiveSessionRoomController::class, 'recording'])->name('sessions.room.recording');
        Route::post('/sessions/{sessionId}/room/end', [LiveSessionRoomController::class, 'end'])->name('sessions.room.end');
        Route::get('/complaints', [TeacherComplaintController::class, 'index'])->name('complaints.index');
        Route::post('/complaints', [TeacherComplaintController::class, 'store'])->name('complaints.store');
        Route::get('/complaints/{complaintId}', [TeacherComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaintId}/reply', [TeacherComplaintController::class, 'reply'])->name('complaints.reply');
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
        Route::get('/wallet', [StudentWalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/{transaction}', [StudentWalletController::class, 'show'])->name('wallet.show');
        Route::post('/wallet/deposit', [StudentWalletController::class, 'deposit'])->name('wallet.deposit');
        Route::post('/wallet/withdraw', [StudentWalletController::class, 'withdraw'])->name('wallet.withdraw');
        Route::post('/wallet/{transaction}/cancel', [StudentWalletController::class, 'cancel'])->name('wallet.cancel');
        Route::post('/wallet/{transaction}/complaint', [StudentWalletController::class, 'complaint'])->name('wallet.complaint');
        Route::get('/sessions', [StudentSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/poll', [StudentSessionController::class, 'poll'])->name('sessions.poll');
        Route::post('/sessions/book/preview', [StudentSessionController::class, 'previewBooking'])->name('sessions.book.preview');
        Route::post('/sessions/book', [StudentSessionController::class, 'storeBooking'])->name('sessions.book');
        Route::post('/sessions/{sessionId}/confirm', [StudentSessionController::class, 'confirm'])->name('sessions.confirm');
        Route::post('/sessions/{sessionId}/cancel', [StudentSessionController::class, 'cancel'])->name('sessions.cancel');
        Route::post('/sessions/{session}/complaint', [StudentSessionController::class, 'complaint'])->name('sessions.complaint');
        Route::get('/sessions/{sessionId}/room', [LiveSessionRoomController::class, 'show'])->name('sessions.room.show');
        Route::get('/sessions/{sessionId}/room/state', [LiveSessionRoomController::class, 'state'])->name('sessions.room.state');
        Route::post('/sessions/{sessionId}/room/join', [LiveSessionRoomController::class, 'join'])->name('sessions.room.join');
        Route::post('/sessions/{sessionId}/room/signal', [LiveSessionRoomController::class, 'signal'])->name('sessions.room.signal');
        Route::get('/sessions/{sessionId}/room/agora-token', [LiveSessionRoomController::class, 'agoraToken'])->name('sessions.room.agora-token');
        Route::post('/sessions/{sessionId}/room/message', [LiveSessionRoomController::class, 'message'])->name('sessions.room.message');
        Route::post('/sessions/{sessionId}/room/file', [LiveSessionRoomController::class, 'file'])->name('sessions.room.file');
        Route::post('/sessions/{sessionId}/room/complaint', [LiveSessionRoomController::class, 'complaint'])->name('sessions.room.complaint');
        Route::post('/sessions/{sessionId}/room/recording', [LiveSessionRoomController::class, 'recording'])->name('sessions.room.recording');
        Route::post('/sessions/{sessionId}/room/end', [LiveSessionRoomController::class, 'end'])->name('sessions.room.end');
        Route::get('/sessions/{session}', [StudentSessionController::class, 'show'])->name('sessions.show');
        Route::get('/complaints', [StudentComplaintController::class, 'index'])->name('complaints.index');
        Route::post('/complaints', [StudentComplaintController::class, 'store'])->name('complaints.store');
        Route::get('/complaints/{complaintId}', [StudentComplaintController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaintId}/reply', [StudentComplaintController::class, 'reply'])->name('complaints.reply');
        Route::get('/teachers', [StudentTeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacherId}', [StudentTeacherController::class, 'show'])->name('teachers.show');
        Route::post('/teachers/{teacherId}/book', [StudentTeacherController::class, 'storeBooking'])->name('teachers.book');
    });
});
