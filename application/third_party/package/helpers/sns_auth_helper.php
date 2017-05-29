<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 페이스북 인증
 * @param $auth_key 인증 키
 * @param $user_key 유저 키
 * @throws APIException
 *
 * app.accesstoken 만드는 방법
 * https://graph.facebook.com/oauth/access_token?client_id={app-id}&client_secret={app-secret}&grant_type=client_credentials
 *
 */

function verify_facebook_key($auth_key, $user_key)
{
    $facebook_app_id = 'xxx';
    $facebook_apptoken = 'xxx|yyy';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        "https://graph.facebook.com/debug_token?".
        "input_token=$auth_key&".
        "access_token=$facebook_apptoken"
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    curl_close($ch);

    $response = json_decode($response, true);
    if (!($response['data']['app_id'] == $facebook_app_id &&
        $response['data']['user_id'] == $user_key &&
        $response['data']['is_valid'] == true)) {
        $message = "페이스북 인증에 실패하였습니다.";
        api_history_add($_SERVER['PATH_INFO']."->error:{$message}",$auth_key);
        throw new APIException($message, -5);
    }
}

/**
 * 카카오 인증
 * @param $auth_key 인증 키
 * @param $user_key 유저 키
 * @throws APIException
 */
function verify_kakao_key($auth_key, $user_key)
{
    // app id를 검증할 수 없는 보안 이슈 존재

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        "https://kapi.kakao.com/v1/user/access_token_info"
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer {$auth_key}"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);
    if ($response['id'] != $user_key) {
        $message = "카카오 인증에 실패하였습니다.";
        api_history_add($_SERVER['PATH_INFO']."->error:{$message}",$auth_key);
        throw new APIException($message, -5);
    }
}
