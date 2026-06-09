<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTeacherController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.teachers.index', [
            'teachers' => Teacher::query()
                ->withTrashed()
                ->withCount(['subjects', 'sessions', 'complaints'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        return view('admin.pages.teachers.edit', [
            'teacher' => $teacher,
        ]);
    }

    public function show(Teacher $teacher): View
    {
        $teacher->loadCount(['subjects', 'sessions', 'complaints']);

        return view('admin.pages.teachers.show', [
            'teacher' => $teacher,
        ]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:teachers,phone,'.$teacher->id],
            'specialization' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $teacher->update($validated);

        return redirect()->route('admin.teachers.index')->with('status', 'تم تعديل بيانات الأستاذ.');
    }

    public function approve(Teacher $teacher): RedirectResponse
    {
        $teacher->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('status', 'تمت الموافقة على حساب الأستاذ.');
    }

    public function reject(Teacher $teacher): RedirectResponse
    {
        $teacher->update(['approval_status' => 'rejected']);
        $teacher->delete();

        return back()->with('status', 'تم رفض حساب الأستاذ وحذفه حذفاً ناعماً.');
    }

    public function toggleDisabled(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validate([
            'disabled_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher->update([
            'disabled_at' => $teacher->disabled_at ? null : now(),
            'disabled_reason' => $teacher->disabled_at ? null : ($validated['disabled_reason'] ?? null),
            'is_accepting_instant_sessions' => false,
        ]);

        return back()->with('status', $teacher->disabled_at ? 'تم تعطيل حساب الأستاذ.' : 'تم تفعيل حساب الأستاذ.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return back()->with('status', 'تم حذف حساب الأستاذ حذفاً ناعماً.');
    }

    public function sessions(Teacher $teacher): View
    {
        return view('admin.pages.teachers.sessions', [
            'teacher' => $teacher,
            'sessions' => $teacher->sessions()->with(['student', 'subject', 'complaints'])->latest('created_at')->paginate(15),
        ]);
    }

    public function wallet(Teacher $teacher): View
    {
        return view('admin.pages.teachers.wallet', [
            'teacher' => $teacher,
            'transactions' => $teacher->walletTransactions()->with('session.subject')->paginate(15),
        ]);
    }

    public function complaints(Teacher $teacher): View
    {
        return view('admin.pages.teachers.complaints', [
            'teacher' => $teacher,
            'complaints' => $teacher->complaints()->with(['student', 'session.subject'])->latest('submitted_at')->paginate(15),
        ]);
    }
}
