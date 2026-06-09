<?php

namespace App\Http\Controllers;

use App\Libraries\RtcTokenBuilder;
use App\Services\AppSettingsService;
use Illuminate\Http\Request;

class AgoraController extends Controller
{
    public function token(Request $request, AppSettingsService $settings)
    {
        $appID = $settings->agoraAppId();
        $appCertificate = $settings->agoraAppCertificate();
        $channelName = $request->channel;
        $uid = 0;
        $role = 1;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = now()->timestamp;
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        require_once app_path('Libraries/RtcTokenBuilder.php');

        $token = RtcTokenBuilder::buildTokenWithUid(
            $appID,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );

        return response()->json([
            'token' => $token,
            'appId' => $appID
        ]);
    }
}
