<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeacherComplaint;
use App\Services\Realtime\RealtimeUpdateService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentComplaintController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    public function index(Request $request): View
    {
        $student = $this->authenticatedStudent($request);
        $this->markComplaintsAsRead($student->id);

        return view('student.pages.complaints.index', [
            'complaints' => $student->complaints()->with(['teacher', 'session.subject'])->latest('submitted_at')->get(),
            'sessions' => $student->sessions()->with(['teacher', 'subject'])->latest('created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);

        $validated = $request->validate([
            'teacher_session_id' => ['nullable', 'integer', 'exists:teacher_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $session = null;
        if (! empty($validated['teacher_session_id'])) {
            $session = $student->sessions()->whereKey($validated['teacher_session_id'])->firstOrFail();
            $session->load('teacher');
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store("complaints/students/{$student->id}", 'public');
        }

        unset($validated['attachment']);

        $student->complaints()->create([
            ...$validated,
            'teacher_id' => $session?->teacher_id,
            'submitted_by' => 'student',
            'status' => 'pending',
            'submitted_at' => now(),
            'student_read_at' => now(),
        ]);

        $this->realtime->broadcastAdminDashboard();

        return back()->with('status', 'تم إرسال الشكوى.');
    }

    public function show(Request $request, int $complaintId): View
    {
        $student = $this->authenticatedStudent($request);
        $complaint = $this->resolveComplaint($student->id, $complaintId);
        $complaint->forceFill(['student_read_at' => now()])->save();

        return view('student.pages.complaints.show', [
            'complaint' => $complaint,
            'canReply' => $complaint->status === 'in_progress' && $complaint->messages()->where('sender_role', 'admin')->exists(),
        ]);
    }

    public function reply(Request $request, int $complaintId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $complaint = $this->resolveComplaint($student->id, $complaintId);

        abort_unless($complaint->status === 'in_progress' && $complaint->messages()->where('sender_role', 'admin')->exists(), 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $complaint->messages()->create([
            'sender_role' => 'student',
            'sender_name' => $student->name,
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'تم إرسال الرد.');
    }

    private function resolveComplaint(int $studentId, int $complaintId): TeacherComplaint
    {
        return TeacherComplaint::query()
            ->with(['teacher', 'student', 'session.subject', 'messagesChronological'])
            ->whereKey($complaintId)
            ->where(function ($query) use ($studentId): void {
                $query->where('student_id', $studentId)
                    ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('student_id', $studentId));
            })
            ->firstOrFail();
    }

    private function markComplaintsAsRead(int $studentId): void
    {
        TeacherComplaint::query()
            ->whereNull('student_read_at')
            ->where(function ($query) use ($studentId): void {
                $query->where('student_id', $studentId)
                    ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('student_id', $studentId));
            })
            ->update(['student_read_at' => now()]);
    }
}
