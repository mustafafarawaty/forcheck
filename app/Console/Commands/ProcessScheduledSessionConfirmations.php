<?php

namespace App\Console\Commands;

use App\Models\TeacherSession;
use App\Services\Student\StudentBookingService;
use Illuminate\Console\Command;

/**
 * Process confirmation deadlines for scheduled sessions.
 */
class ProcessScheduledSessionConfirmations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:process-confirmations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel or reassign scheduled sessions that passed the confirmation deadline.';

    /**
     * Execute the console command.
     */
    public function handle(StudentBookingService $bookingService): int
    {
        $sessions = TeacherSession::query()
            ->with(['student', 'subject'])
            ->where('status', 'upcoming')
            ->where('booking_type', 'scheduled')
            ->whereNotNull('confirmation_deadline_at')
            ->where('confirmation_deadline_at', '<=', now())
            ->get();

        foreach ($sessions as $session) {
            if ($session->student_confirmed_at && $session->teacher_confirmed_at) {
                continue;
            }

            if (! $session->student_confirmed_at) {
                $session->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'تم إلغاء الجلسة لعدم تأكيد حضور الطالب قبل الموعد.',
                ]);

                continue;
            }

            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'تم إلغاء الجلسة لعدم تأكيد حضور الأستاذ قبل الموعد.',
            ]);

            $bookingService->reassignScheduledSession($session);
        }

        $this->info('Scheduled session confirmations processed successfully.');

        return self::SUCCESS;
    }
}
