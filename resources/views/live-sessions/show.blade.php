@extends($actorRole === 'teacher' ? 'teacher.layouts.app' : 'student.layouts.app')

@section('title', 'غرفة الجلسة')
@section('page_title', 'غرفة الجلسة المباشرة')

@section('content')
    @php
        $videoCallConfig = [
            'role' => $actorRole,
            'sessionId' => $session->id,
            'stateUrl' => $stateUrl,
            'joinUrl' => $joinUrl,
            'signalUrl' => $signalUrl,
            'agoraTokenUrl' => $agoraTokenUrl,
            'messageUrl' => $messageUrl,
            'fileUrl' => $fileUrl,
            'notesUrl' => $notesUrl,
            'complaintUrl' => $complaintUrl,
            'recordingUrl' => $recordingUrl,
            'endUrl' => $endUrl,
            'roomChannel' => $roomChannel,
            'initialState' => $roomState,
            'redirectUrl' => $redirectUrl,
            'appId' => $appId,
            'autojoin' => $autojoin,
        ];
    @endphp

    <div
        data-video-call-app
        data-video-call-config='@json($videoCallConfig)'
    ></div>
@endsection
