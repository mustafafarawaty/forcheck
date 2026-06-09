@extends($actorRole === 'teacher' ? 'teacher.layouts.app' : 'student.layouts.app')

@section('title', 'Live Session')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div
    id="video-call-app"
    class="video-call-container"
    data-video-call-app
    data-video-call-config='{!! json_encode([
        'agoraTokenUrl' => $agoraTokenUrl,
        'agoraChannel' => $agoraChannel,
        'sessionId' => $session->id,
        'appId' => $appId,
        'autojoin' => $autojoin,
        'recordingUrl' => $recordingUrl,
        'messageUrl' => route(
        $actorRole === 'teacher'
            ? 'teacher.sessions.room.message'
            : 'student.sessions.room.message',
        $session->id
    ),
    'realtimeChannel' => $roomChannel,
        'role' => $actorRole,
        'stateUrl' => $stateUrl,
        'joinUrl' => $joinUrl,
        'endUrl' => $endUrl,
        'redirectUrl' => $redirectUrl,
        'initialState' => $roomState,
        'serverNowTs' => now()->timestamp,
        'sessionStatus' => $sessionStatus,
        'sessionScheduledAt' => $sessionScheduledAt,
        'sessionStartedAt' => $sessionStartedAt,
        'sessionDurationHours' => $sessionDurationHours,
        'sessionEndTime' => $sessionEndTime,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}'
></div>
@endsection
