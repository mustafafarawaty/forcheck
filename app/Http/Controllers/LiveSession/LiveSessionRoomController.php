<?php

namespace App\Http\Controllers\LiveSession;

use App\Http\Controllers\Controller;
use App\Http\Requests\LiveSession\EndLiveSessionRequest;
use App\Http\Requests\LiveSession\StoreLiveSessionComplaintRequest;
use App\Http\Requests\LiveSession\StoreLiveSessionFileRequest;
use App\Http\Requests\LiveSession\StoreLiveSessionMessageRequest;
use App\Http\Requests\LiveSession\StoreLiveSessionRecordingRequest;
use App\Http\Requests\LiveSession\UpdateLiveSessionNotesRequest;
use App\Libraries\RtcTokenBuilder;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Services\AppSettingsService;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeChannelService;
use App\Services\Realtime\RealtimeUpdateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared live session room controller for teacher and student areas.
 */
class LiveSessionRoomController extends Controller
{
    public function __construct(
        private readonly LiveSessionRoomService $roomService,
        private readonly RealtimeUpdateService $realtime,
        private readonly RealtimeChannelService $channels,
        private readonly AppSettingsService $settings,
    ) {
    }

    /**
     * Display the room page.
     */
    public function show(Request $request, int $sessionId): View
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);

        return view('live-sessions.show', [
            'session' => $session,
            'actorRole' => $role,
            'roomState' => $this->roomService->roomState($session, $role),
            'stateUrl' => $this->routeFor($role, 'state', $session),
            'joinUrl' => $this->routeFor($role, 'join', $session),
            'signalUrl' => $this->routeFor($role, 'signal', $session),
            'agoraTokenUrl' => $this->routeFor($role, 'agora-token', $session),
            'messageUrl' => $this->routeFor($role, 'message', $session),
            'fileUrl' => $this->routeFor($role, 'file', $session),
            'notesUrl' => $role === 'teacher'
                ? $this->routeFor($role, 'notes', $session)
                : null,
            'complaintUrl' => $this->routeFor($role, 'complaint', $session),
            'recordingUrl' => $this->routeFor($role, 'recording', $session),
            'endUrl' => $this->routeFor($role, 'end', $session),
            'roomChannel' => $this->channels->liveSessionChannel($session),
            'agoraChannel' => "session-{$session->id}",
            'appId' => $this->settings->agoraAppId(),
            'iceServers' => [],
            'redirectUrl' => $role === 'teacher'
                ? route('teacher.sessions.show', $session->id, false)
                : route('student.sessions.show', $session->id, false),
            'autojoin' => $request->boolean('autojoin'),
            'sessionStatus' => $session->status,
            'sessionScheduledAt' => optional($session->scheduled_at)->timestamp,
            'sessionStartedAt' => optional($session->started_at)->timestamp,
            'sessionDurationHours' => (int) ($session->duration_hours ?: 1),
            'sessionEndTime' => optional($session->plannedEndAt())->timestamp,
        ]);
    }

    /**
     * Poll room state.
     */
    public function state(Request $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);

        return response()->json(
            $this->roomService->roomState($session, $role)
        );
    }

    /**
     * Join the room.
     */
    public function join(Request $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);
        $joined = $this->roomService->join($session, $role);
        $this->realtime->broadcastRoomEvent($joined, 'state');
        $this->realtime->broadcastSessionParticipants($joined);

        return response()->json([
            'status' => 'ok',
            'session' => $this->roomService->roomState($joined, $role)['session'],
        ]);
    }

    /**
     * Store a signal packet.
     */
    public function signal(Request $request, int $sessionId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Store a chat message.
     */
    public function message(StoreLiveSessionMessageRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);
        $message = $this->roomService->storeMessage($session, $role, $request->validated('message'));
        $this->realtime->broadcastRoomEvent($session, 'message', [
            'id' => $message->id,
            'sender_role' => $message->sender_role,
            'sender_name' => $message->sender_name,
            'message' => $message->message,
            'created_at' => $message->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'ok',
            'message_id' => $message->id,
            'message' => [
                'id' => $message->id,
                'sender_role' => $message->sender_role,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Upload a room file.
     */
    public function file(StoreLiveSessionFileRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);
        $file = $this->roomService->uploadFile($session, $role, $request->file('file'));
        $this->realtime->broadcastRoomEvent($session, 'file', [
            'id' => $file->id,
            'uploader_role' => $file->uploader_role,
            'uploader_name' => $file->uploader_name,
            'original_name' => $file->original_name,
            'file_url' => $file->file_url,
            'size' => $file->size,
            'created_at' => $file->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'ok',
            'file_id' => $file->id,
            'name' => $file->original_name,
        ]);
    }

    /**
     * Save notes.
     */
    public function notes(UpdateLiveSessionNotesRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);

        abort_unless($role === 'teacher', 403);

        $session = $this->resolveSession($actor, $role, $sessionId);
        $updated = $this->roomService->saveTeacherNotes($session, $request->validated());

        return response()->json([
            'status' => 'ok',
            'teacher_private_notes' => $updated->teacher_private_notes,
            'student_summary_notes' => $updated->student_summary_notes,
        ]);
    }

    /**
     * Store a complaint.
     */
    public function complaint(StoreLiveSessionComplaintRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);
        $data = $request->validated();
        $data['attachment'] = $request->file('attachment');
        $complaint = $this->roomService->storeComplaint($session, $role, $data);
        $this->realtime->broadcastRoomEvent($session, 'complaint', [
            'id' => $complaint->id,
            'title' => $complaint->title,
            'status' => $complaint->status,
            'submitted_by' => $complaint->submitted_by,
            'submitted_at' => $complaint->submitted_at?->toIso8601String(),
        ]);
        $this->realtime->broadcastAdminDashboard();

        return response()->json([
            'status' => 'ok',
            'complaint_id' => $complaint->id,
        ]);
    }

    /**
     * Upload a recorded media file.
     */
    public function recording(StoreLiveSessionRecordingRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);

        if ($request->boolean('recording_finalize')) {
            $updated = $this->roomService->finalizeRecordingUpload($session);
        } elseif ($request->hasFile('recording')) {
            $chunkIndex = $request->integer('recording_chunk_index');
            $isLastChunk = $request->boolean('recording_is_last_chunk');

            if ($request->has('recording_chunk_index')) {
                $updated = $this->roomService->uploadRecordingChunk(
                    $session,
                    $request->file('recording'),
                    $chunkIndex,
                    $isLastChunk
                );
            } else {
                $updated = $this->roomService->uploadRecording($session, $request->file('recording'));
            }
        } else {
            $updated = $session;
        }

        $this->realtime->broadcastRoomEvent($updated, 'recording');

        return response()->json([
            'status' => 'ok',
            'recording_url' => $updated->recording_url,
        ]);
    }

    /**
     * Generate Agora token for the session.
     */
    public function agoraToken(Request $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);

        $appId = $this->settings->agoraAppId();
        $appCertificate = $this->settings->agoraAppCertificate();

        if (! $appId || ! $appCertificate) {
            abort(500, 'Agora configuration missing');
        }

        $channelName = "session-{$session->id}";
        $uid = 0;
        $plannedEndAt = $session->plannedEndAt() ?? now()->addHours((int) ($session->duration_hours ?: 1));
        $privilegeExpireTs = max(
            now()->addHour()->timestamp,
            $plannedEndAt->copy()->addMinutes(15)->timestamp,
        );

        $token = RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            RtcTokenBuilder::RolePublisher,
            $privilegeExpireTs,
        );

        return response()->json([
            'token' => $token,
            'appId' => $appId,
            'channel' => $channelName,
            'uid' => $uid,
        ]);
    }

    /**
     * End the running session.
     */
    public function end(EndLiveSessionRequest $request, int $sessionId): JsonResponse
    {
        [$actor, $role] = $this->actorFromRequest($request);
        $session = $this->resolveSession($actor, $role, $sessionId);
        $ended = $this->roomService->endSession($session, $role, $request->validated('cancellation_reason'));
        $this->realtime->broadcastRoomEvent($ended, 'ended');
        $this->realtime->broadcastSessionParticipants($ended);

        return response()->json([
            'status' => 'ok',
            'redirect_url' => $role === 'teacher'
                ? route('teacher.sessions.show', $ended->id, false)
                : route('student.sessions.show', $ended->id, false),
            'session' => $this->roomService->roomState($ended, $role)['session'],
        ]);
    }

    /**
     * Resolve the authenticated actor and role from the request.
     *
     * @return array{0: Teacher|Student, 1: string}
     */
    private function actorFromRequest(Request $request): array
    {
        /** @var Teacher|null $teacher */
        $teacher = $request->attributes->get('authenticatedTeacher');

        if ($teacher) {
            return [$teacher, 'teacher'];
        }

        /** @var Student $student */
        $student = $request->attributes->get('authenticatedStudent');

        return [$student, 'student'];
    }

    /**
     * Resolve the owned session based on actor role.
     */
    private function resolveSession(Teacher|Student $actor, string $role, int $sessionId): TeacherSession
    {
        return $role === 'teacher'
            ? $this->roomService->resolveForTeacher($actor, $sessionId)
            : $this->roomService->resolveForStudent($actor, $sessionId);
    }

    /**
     * Resolve room route names for the current role.
     */
    private function routeFor(string $role, string $action, TeacherSession $session): string
    {
        $prefix = $role === 'teacher' ? 'teacher' : 'student';

        return route("{$prefix}.sessions.room.{$action}", $session->id, false);
    }

    /**
     * Get the session end timestamp for client countdown.
     */
    private function sessionEndTime(TeacherSession $session): ?int
    {
        return optional($session->plannedEndAt())->timestamp;
    }
}
