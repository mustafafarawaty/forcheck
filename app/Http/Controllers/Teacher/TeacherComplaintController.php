<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherComplaintRequest;
use App\Models\Teacher;
use App\Models\TeacherComplaint;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Teacher\TeacherComplaintService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Handles teacher complaints.
 */
class TeacherComplaintController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherComplaintService $complaintService,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Show teacher complaints page.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $complaints = TeacherComplaint::query()
            ->with('session.subject')
            ->where(function ($query) use ($teacher): void {
                $query->where('teacher_id', $teacher->id)
                    ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('teacher_id', $teacher->id));
            })
            ->latest()
            ->get();

        if (Schema::hasColumn('teacher_complaints', 'teacher_read_at')) {
            $complaints->each(function (TeacherComplaint $complaint): void {
                if (is_null($complaint->teacher_read_at)) {
                    $complaint->forceFill(['teacher_read_at' => now()])->save();
                }
            });
        }

        return view('teacher.pages.complaints.index', [
            'complaints' => $complaints,
            'sessions' => $teacher->sessions()->with('subject')->latest('created_at')->get(),
        ]);
    }

    public function show(Request $request, int $complaintId): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $complaint = TeacherComplaint::query()
            ->with(['student', 'session.subject', 'messagesChronological'])
            ->whereKey($complaintId)
            ->where(function ($query) use ($teacher): void {
                $query->where('teacher_id', $teacher->id)
                    ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('teacher_id', $teacher->id));
            })
            ->firstOrFail();

        if (Schema::hasColumn('teacher_complaints', 'teacher_read_at') && is_null($complaint->teacher_read_at)) {
            $complaint->forceFill(['teacher_read_at' => now()])->save();
        }

        return view('teacher.pages.complaints.show', [
            'complaint' => $complaint,
            'canReply' => $complaint->status === 'in_progress' && $complaint->messages()->where('sender_role', 'admin')->exists(),
        ]);
    }

    public function reply(Request $request, int $complaintId): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $complaint = $teacher->complaints()->findOrFail($complaintId);

        abort_unless($complaint->status === 'in_progress' && $complaint->messages()->where('sender_role', 'admin')->exists(), 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $complaint->messages()->create([
            'sender_role' => 'teacher',
            'sender_name' => $teacher->name,
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'تم إرسال الرد.');
    }

    /**
     * Store a new complaint.
     */
    public function store(StoreTeacherComplaintRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->complaintService->store($teacher, $request->validated());
        $this->realtime->broadcastAdminDashboard();

        return back()->with('status', 'تم إرسال الشكوى.');
    }
}
