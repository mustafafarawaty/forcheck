<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class AdminSessionController extends Controller
{
    public function index(): View
    {
        $sessions = TeacherSession::query()
            ->with(['teacher', 'student', 'subject', 'messages', 'files', 'complaints'])
            ->latest('created_at')
            ->paginate(20);

        if (Schema::hasColumn('teacher_sessions', 'admin_read_at')) {
            foreach ($sessions as $session) {
                if (is_null($session->admin_read_at)) {
                    $session->forceFill(['admin_read_at' => now()])->save();
                }
            }
        }

        return view('admin.pages.sessions.index', [
            'sessions' => $sessions,
        ]);
    }

    public function show(TeacherSession $session): View
    {
        $session->load([
            'teacher',
            'student',
            'subject',
            'messages' => fn ($query) => $query->oldest('id'),
            'files',
            'complaints.messagesChronological',
            'walletTransactions',
        ]);

        return view('admin.pages.sessions.show', [
            'session' => $session,
        ]);
    }
}
