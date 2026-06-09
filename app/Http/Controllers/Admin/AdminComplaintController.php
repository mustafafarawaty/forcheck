<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherComplaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminComplaintController extends Controller
{
    public function index(): View
    {
        $complaints = TeacherComplaint::query()
            ->with(['teacher', 'student', 'session.subject'])
            ->latest('submitted_at')
            ->paginate(20);

        // mark visible complaints on this page as read for admin
        $complaints->getCollection()->each(function (TeacherComplaint $c): void {
            if (is_null($c->admin_read_at)) {
                $c->forceFill(['admin_read_at' => now()])->save();
            }
        });

        return view('admin.pages.complaints.index', [
            'complaints' => $complaints,
        ]);
    }

    public function show(TeacherComplaint $complaint): View
    {
        $complaint->load(['teacher', 'student', 'session.subject', 'messagesChronological']);

        // mark this complaint as read for admin
        if (is_null($complaint->admin_read_at)) {
            $complaint->forceFill(['admin_read_at' => now()])->save();
        }

        return view('admin.pages.complaints.show', [
            'complaint' => $complaint,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function updateStatus(Request $request, TeacherComplaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed,closed'],
        ]);

        $complaint->update([
            'status' => $validated['status'],
            'student_read_at' => null,
        ]);

        return back()->with('status', 'تم تعديل حالة الشكوى.');
    }

    public function reply(Request $request, TeacherComplaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $complaint->messages()->create([
            'sender_role' => 'admin',
            'sender_name' => 'الإدارة',
            'message' => $validated['message'],
        ]);

        if ($complaint->status !== 'closed') {
            $complaint->update([
                'status' => 'in_progress',
                'student_read_at' => null,
            ]);
        } else {
            $complaint->update(['student_read_at' => null]);
        }

        return back()->with('status', 'تم إرسال الرد.');
    }

    private function statusOptions(): array
    {
        return [
            'in_progress' => 'قيد المعالجة',
            'completed' => 'مكتملة',
            'closed' => 'مغلقة',
        ];
    }
}
