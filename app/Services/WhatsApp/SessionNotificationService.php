<?php

namespace App\Services\WhatsApp;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Services\AppSettingsService;

class SessionNotificationService
{
    private WhatsAppClient $whatsapp;

    private AppSettingsService $settings;

    public function __construct()
    {
        $this->whatsapp = app(WhatsAppClient::class);
        $this->settings = app(AppSettingsService::class);
    }

    public function sendFifteenMinuteReminder(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $time = $session->scheduled_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        if ($session->teacher && $session->teacher->phone) {
            $msg = "⏰ تذكير: لديك جلسة بعد 15 دقيقة مع {$studentName} في مادة {$subjectName} الساعة {$time}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "⏰ تذكير: لديك جلسة بعد 15 دقيقة مع {$teacherName} في مادة {$subjectName} الساعة {$time}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }

        $this->sendToAdmins("تذكير: جلسة بعد 15 دقيقة بين {$teacherName} و {$studentName} في مادة {$subjectName} الساعة {$time}");
    }

    public function sendTwelveHourReminder(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $time = $session->scheduled_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        if ($session->teacher && $session->teacher->phone) {
            $msg = "📅 تذكير: لديك جلسة مجدولة مع {$studentName} في مادة {$subjectName} يوم {$time}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "📅 تذكير: لديك جلسة مجدولة مع {$teacherName} في مادة {$subjectName} يوم {$time}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }
    }

    public function sendTenMinuteReminder(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $time = $session->scheduled_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        if ($session->teacher && $session->teacher->phone) {
            $msg = "تذكير: لديك جلسة بعد 10 دقائق مع {$studentName} في مادة {$subjectName} الساعة {$time}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "تذكير: لديك جلسة بعد 10 دقائق مع {$teacherName} في مادة {$subjectName} الساعة {$time}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }

        $this->sendToAdmins("تذكير: جلسة بعد 10 دقائق بين {$teacherName} و {$studentName} في مادة {$subjectName} الساعة {$time}");
    }

    public function sendStartNotification(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        $teacherUrl = route('teacher.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]);
        $studentUrl = route('student.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]);

        if ($session->teacher && $session->teacher->phone) {
            $msg = "موعد جلستك الآن مع {$studentName} في مادة {$subjectName}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
            $this->whatsapp->sendMessage($session->teacher->phone, $teacherUrl);
        }

        if ($session->student && $session->student->phone) {
            $msg = "موعد جلستك الآن مع {$teacherName} في مادة {$subjectName}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
            $this->whatsapp->sendMessage($session->student->phone, $studentUrl);
        }
    }

    public function notifySessionCompleted(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $teacherName = $session->teacher?->name ?? 'أستاذ';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');

        if ($session->teacher && $session->teacher->phone) {
            $msg = "✅ تم إكمال الجلسة مع {$studentName} في مادة {$subjectName}.";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "✅ تم إكمال الجلسة مع {$teacherName} في مادة {$subjectName}.";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }
    }

    public function notifyAdminSessionCompleted(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $startTime = $session->started_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $endTime = $session->ended_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $duration = $session->duration_hours ?? 1;
        $teacherName = $session->teacher?->name ?? 'أستاذ';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');

        $msg = "✅ تم إكمال الجلسة رقم {$session->id}\n"
            ."الأستاذ: {$teacherName}\n"
            ."الطالب: {$studentName}\n"
            ."المادة: {$subjectName}\n"
            ."وقت البدء: {$startTime}\n"
            ."وقت الانتهاء: {$endTime}\n"
            ."المدة: {$duration} ساعة\n"
            ."السعر: {$session->price} ل.س\n"
            ."عمولة الإدارة: {$session->admin_commission_amount} ل.س\n"
            ."صافي الأستاذ: {$session->teacher_earning_amount} ل.س";

        $this->sendToAdmins($msg);
    }

    public function notifySessionCancelled(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $reason = $session->cancellation_reason ?? 'بدون سبب';
        $teacherName = $session->teacher?->name ?? 'أستاذ';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');

        if ($session->teacher && $session->teacher->phone) {
            $msg = "❌ تم إلغاء الجلسة مع {$studentName} في مادة {$subjectName}.\nالسبب: {$reason}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "❌ تم إلغاء الجلسة مع {$teacherName} في مادة {$subjectName}.\nالسبب: {$reason}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }
    }

    public function notifyAdminSessionCancelled(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $scheduledTime = $session->scheduled_at?->format('Y-m-d H:i') ?? 'غير محدد';
        $reason = $session->cancellation_reason ?? 'بدون سبب';
        $teacherName = $session->teacher?->name ?? 'أستاذ';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');

        $msg = "❌ تم إلغاء الجلسة رقم {$session->id}\n"
            ."الأستاذ: {$teacherName}\n"
            ."الطالب: {$studentName}\n"
            ."المادة: {$subjectName}\n"
            ."الموعد: {$scheduledTime}\n"
            ."السبب: {$reason}";

        $this->sendToAdmins($msg);
    }

    public function sendInstantSessionAccepted(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        if ($session->teacher && $session->teacher->phone) {
            $msg = "🎯 تم قبول طلب جلسة مباشرة مع {$studentName} في مادة {$subjectName}.\n"
                .'يمكنك الآن الانضمام إلى الغرفة.';
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
        }

        if ($session->student && $session->student->phone) {
            $msg = "🎯 تم قبول طلب جلستك المباشرة مع {$teacherName} في مادة {$subjectName}.\n"
                .'يمكنك الآن الانضمام إلى الغرفة.';
            $this->whatsapp->sendMessage($session->student->phone, $msg);
        }
    }

    public function sendInstantStartNotification(TeacherSession $session): void
    {
        $session->loadMissing(['teacher', 'student', 'subject']);

        $subjectName = $session->subject?->name ?? 'غير محدد';
        $studentName = $session->student?->name ?? ($session->student_name ?: 'طالب');
        $teacherName = $session->teacher?->name ?? 'أستاذ';

        $teacherUrl = route('teacher.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]);
        $studentUrl = route('student.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]);

        if ($session->teacher && $session->teacher->phone) {
            $msg = "🔔 حان موعد جلستك المباشرة مع {$studentName} في مادة {$subjectName}";
            $this->whatsapp->sendMessage($session->teacher->phone, $msg);
            $this->whatsapp->sendMessage($session->teacher->phone, $teacherUrl);
        }

        if ($session->student && $session->student->phone) {
            $msg = "🔔 حان موعد جلستك المباشرة مع {$teacherName} في مادة {$subjectName}";
            $this->whatsapp->sendMessage($session->student->phone, $msg);
            $this->whatsapp->sendMessage($session->student->phone, $studentUrl);
        }
    }

    public function notifyAdminNewStudent(Student $student): void
    {
        $email = $student->email ?? 'لا يوجد';
        $msg = "🆕 طالب جديد مسجل\n"
            ."الاسم: {$student->name}\n"
            ."الهاتف: {$student->phone}\n"
            ."البريد: {$email}";

        $this->sendToAdmins($msg);
    }

    public function notifyAdminNewTeacher(Teacher $teacher): void
    {
        $email = $teacher->email ?? 'لا يوجد';
        $msg = "🆕 أستاذ جديد مسجل\n"
            ."الاسم: {$teacher->name}\n"
            ."الهاتف: {$teacher->phone}\n"
            ."البريد: {$email}";

        $this->sendToAdmins($msg);
    }

    private function sendToAdmins(string $message): void
    {
        $phone = $this->settings->whatsappAdminPhone();

        if ($phone) {
            $this->whatsapp->sendMessage($phone, $message);
        }
    }
}
