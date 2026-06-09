<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TeacherComplaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStudentController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.students.index', [
            'students' => Student::query()
                ->withTrashed()
                ->withCount(['sessions', 'walletTransactions'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function edit(Student $student): View
    {
        return view('admin.pages.students.edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:students,phone,'.$student->id],
            'study_level' => ['required', 'string', 'max:255'],
            'about' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $student->update($validated);

        return redirect()->route('admin.students.index')->with('status', 'تم تعديل بيانات الطالب.');
    }

    public function toggleDisabled(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'disabled_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $student->update([
            'disabled_at' => $student->disabled_at ? null : now(),
            'disabled_reason' => $student->disabled_at ? null : ($validated['disabled_reason'] ?? null),
        ]);

        return back()->with('status', $student->disabled_at ? 'تم تعطيل حساب الطالب.' : 'تم تفعيل حساب الطالب.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return back()->with('status', 'تم حذف حساب الطالب حذفاً ناعماً.');
    }

    public function sessions(Student $student): View
    {
        return view('admin.pages.students.sessions', [
            'student' => $student,
            'sessions' => $student->sessions()->with(['teacher', 'subject', 'complaints'])->latest('created_at')->paginate(15),
        ]);
    }

    public function wallet(Student $student): View
    {
        return view('admin.pages.students.wallet', [
            'student' => $student,
            'transactions' => $student->walletTransactions()->with('session.subject')->paginate(15),
        ]);
    }

    public function complaints(Student $student): View
    {
        return view('admin.pages.students.complaints', [
            'student' => $student,
            'complaints' => TeacherComplaint::query()
                ->with(['teacher', 'session.subject'])
                ->where(function ($query) use ($student): void {
                    $query->where('student_id', $student->id)
                        ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('student_id', $student->id));
                })
                ->latest('submitted_at')
                ->paginate(15),
        ]);
    }
}
