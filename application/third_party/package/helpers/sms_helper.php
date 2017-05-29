<?php
/**
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * API STORE 발신번호 등록
 */
function register_sms_apistore($phone_number, $comment)
{
    $data = [
        "sendnumber" => $phone_number, // 보내는번호 (필수)
        "comment" => $comment // 코멘트 (필수)
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, API_STORE_REGISTER_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-waple-authorization: " . API_STORE_KEY));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    $response = json_decode($response);
return $response;
    /*
    100 : User Error
    200 : OK
    300 : Parameter Error 400 : Etc Error
    500 : 중복등록
     */

//    echo '<pre>';
//    echo print_r($response);
//    echo '</pre>';
//    die;
}

/**
 * API STORE SMS 발송
 */
function send_sms_apistore($dest_phone, $subject, $contents)
{
    $data = [
        "dest_phone" => $dest_phone, // 받는번호 (필수)
        "send_phone" => API_STORE_SENDER_PHONE, // 보내는번호 (필수)
        "send_name" => API_STORE_SENDER_NAME, // 보내는사람 (옵션)
        "subject" => $subject, // 제목 (옵션)
        "msg_body" => $contents, // 메시지내용 (옵션)
        "apiVersion" => API_STORE_VERSION, // api version (필수)
        "id" => API_STORE_ID // api 구매 아이디 (필수)
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, API_STORE_SMS_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-waple-authorization: " . API_STORE_KEY));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    $response = json_decode($response);
    /*
    처리 결과 코드
    100 : User Error
    200 : OK
    300 : Parameter Error 400 : Etc Error
    500 : 발신번호 사전 등록제에 의한 미등록 차단
    600 : 선불제 충전요금 부족처리 결과 코드
    */

//    echo '<pre>';
//    echo print_r($response);
//    echo '</pre>';
//    die;
    return $response;
}


/**
 * 휴대폰 인증용 public키 생성
 *
 */
function generate_phone_verify_public_key($seed)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $salt = 'U%U$UAW#%326523SHREHR';
    $key = hash('sha256', $seed . $salt);

    return $key;
}

/**
 * 휴대폰 인증용 private키 생성 (SMS전송용)
 *
 */
function generate_phone_verify_private_key()
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $key = rand(1000, 9999);

    return $key . '';
}

/**
 * 전화번호 검증증 * @param $phone_number
 * @return bool
 */
function validator_phone_number($phone_number)
{
    //TODO:전화번호 유효성 체크 강화 필요
    if (strlen($phone_number) < 10) {
        //throw new APIException("유효한 전화번호가 아닙니다", 0);
        return false;
    }

    return true;
}

/**
 * 전화번호 포함여부 조회 및 반환
 * @param $string
 * @return bool
 */
function contain_phone_number($string)
{
    $string = preg_replace("/[^0-9]/", "", $string);
    if(preg_match("/^01[0-9]{8,9}$/", $string)) {
        return $string;
    }
    return FALSE;
}
