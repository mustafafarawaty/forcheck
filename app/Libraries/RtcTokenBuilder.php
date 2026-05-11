<?php

namespace App\Libraries;

require_once 'AccessToken.php';

class RtcTokenBuilder
{
    public const RoleAttendee = 0;
    public const RolePublisher = 1;
    public const RoleSubscriber = 2;
    public const RoleAdmin = 101;

    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs)
    {
        return self::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs);
    }

    public static function buildTokenWithUserAccount($appID, $appCertificate, $channelName, $userAccount, $role, $privilegeExpireTs)
    {
        $token = AccessToken::init($appID, $appCertificate, $channelName, $userAccount);
        $privileges = AccessToken::Privileges;
        $token->addPrivilege($privileges['kJoinChannel'], $privilegeExpireTs);

        if (
            $role === self::RoleAttendee ||
            $role === self::RolePublisher ||
            $role === self::RoleAdmin
        ) {
            $token->addPrivilege($privileges['kPublishVideoStream'], $privilegeExpireTs);
            $token->addPrivilege($privileges['kPublishAudioStream'], $privilegeExpireTs);
            $token->addPrivilege($privileges['kPublishDataStream'], $privilegeExpireTs);
        }

        return $token->build();
    }
}
