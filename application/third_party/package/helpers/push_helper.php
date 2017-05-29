<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * user_id로 간단한 푸쉬 등록
 *
 */
function add_push_new($message, $device_info, $activity_type ,$activity_seq, $target_seq , $target_type = null, $time=null, $push_info)
{

    if ($device_info->platform == 1) {
        add_apns_by_user_id($message, $device_info->push_token , $activity_type ,$activity_seq, $target_seq , $target_type, $time, $push_info);
    } else {
        add_gcm_by_user_id($message, $device_info->push_token , $activity_type ,$activity_seq, $target_seq , $target_type, $time, $push_info);
    }
}

/**
 * user_id로 GCM 등록
 *
 */
function add_gcm_by_user_id($message, $push_token ,$activity_type ,$activity_seq, $target_seq , $target_type = null, $time=null, $push_info)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $CI =& get_instance();
    $CI->load->model('push_gcm_logs');

    $insert = array();
    $insert['gcm_registration_id'] = $push_token;
    $insert['message'] = $message;
    $insert['target_seq'] = $target_seq;
    if($target_type )$insert['target_type'] = $target_type;
    $insert['activity_type'] = $activity_type;
    $insert['activity_seq'] = $activity_seq;
    if($time)$insert['time'] = $time;
    $insert['reserved_at'] = getNow();

    if(count($push_info) > 0){
        $insert['push_seq'] = $push_info->seq;
        $insert['target_url'] = $push_info->url;
        $insert['reserved_at'] = $push_info->reserved_at;
    }

    $insert['hash_key'] = md5($push_token.time().rand(0,99999));

    $CI->push_gcm_logs->create($insert);

}

/**
 * user_id로 APNS등록
 *
 */
function add_apns_by_user_id($message, $push_token, $activity_type ,$activity_seq, $target_seq , $target_type = null, $time=null, $push_info)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $CI =& get_instance();
    $CI->load->model('push_apns_logs');

    $insert = array();
    $insert['apns_token'] = $push_token;
    $insert['message'] = $message;
    $insert['target_seq'] = $target_seq;
    if($target_type) $insert['target_type'] = $target_type;
    $insert['activity_type'] = $activity_type;
    $insert['activity_seq'] = $activity_seq;
    if($time)$insert['time'] = $time;
    $insert['reserved_at'] = getNow();

    if(count($push_info) > 0){
        $insert['push_seq'] = $push_info->seq;
        $insert['target_url'] = $push_info->url;
        $insert['reserved_at'] = $push_info->reserved_at;
    }

    $sent_at = getNow();
    $insert['hash_key'] = md5($push_token.time().rand(0,99999));
    $insert['sent_at'] = $sent_at;

    $CI->push_apns_logs->create($insert);
}

/**
 * 전체 푸시 전송
 *
 * @param      string  $message     메시지
 * @param      string  $target_url  랜딩 정보
 * @param      string  $sent_at     보낸 시각
 * @param      int  $push_seq    푸시 시퀀스
 */
function send_push_all($message, $target_url = null, $sent_at = null, $push_seq = null)
{
    $CI =& get_instance();
    $CI->load->model('devices');

    $page = 0;
    $page_count = 200;

    $device_id_array = array();
    while (true)
    {
        $where = array();
        $where['search']['use_push'] = 1;
        $where['order_by'] = 'updated_at desc';
        $where['limit'] = $page_count;
        $where['offset'] = $page * $page_count;

        $infos = $CI->devices->finds($where);
        if (!$infos) break;
        if (count($infos)==0) break;
        foreach ($infos as $row)
        {
            if (array_key_exists($row->device_id, $device_id_array))
            {
                // 이미 동일 디바이스 아이디로 등록되었을 경우
                continue;
            }
            $device_id_array[$row->device_id] = $row->seq;
            add_push_by_push_token($row->push_token, $message, $target_url, $sent_at, $push_seq);
        }
        $page++;
    }
}

/**
 * 푸시 전송
 *
 * @param      string  $push_token  푸시 토큰
 * @param      string  $message     메시지
 * @param      string  $target_url  랜딩 정보
 * @param      string  $sent_at     보낸 시각
 * @param      int  $push_seq    푸시 시퀀스
 */
function add_push_by_push_token($push_token, $message, $target_url = null, $sent_at = null, $push_seq = null)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $CI =& get_instance();
    $CI->load->model('push_gcm_logs');
    $CI->load->model('push_apns_logs');
    $CI->load->model('devices');

    $where = array();
    $where['search']['push_token'] = $push_token;

    $infos = $CI->devices->finds($where);

    if (!$infos)
    {
        // 푸쉬 대상 없음
        return;
    }

    $platform_type = null;
    foreach ($infos as $row)
    {
        if ($row->use_push==0)
        {
            // 푸쉬 수신 안함
            return;
        }
        $platform_type = $row->platform;
    }

    if (!$platform_type)
    {
        // 어떤 플랫폼인지 파악하지 못함
        return;
    }

    if (!$sent_at)
    {
        $sent_at = getNow();
    }

    if ($platform_type==1)
    {
        $insert = array();
        $insert['apns_token'] = $push_token;
        $insert['message'] = $message;
        if ($target_url)
        {
            $insert['target_url'] = $target_url;
        }
        if ($push_seq)
        {
            $insert['push_seq'] = $push_seq;
        }
        $insert['hash_key'] = md5($push_token.time().rand(0,99999));
        $insert['sent_at'] = $sent_at;

        $CI->push_apns_logs->create($insert);

    } else {
        $insert = array();
        $insert['gcm_registration_id'] = $push_token;
        $insert['message'] = $message;
        if ($target_url)
        {
            $insert['target_url'] = $target_url;
        }
        if ($push_seq)
        {
            $insert['push_seq'] = $push_seq;
        }
        $insert['hash_key'] = md5($push_token.time().rand(0,99999));
        $insert['sent_at'] = $sent_at;

        $CI->push_gcm_logs->create($insert);

    }

}

/**
 * APNS 발송
 * @param $apns_token
 * @param $data
 * @param $app_id
 * @param bool $use_sandbox
 * @return string
 */
function send_apns_once($apns_token, $data, $app_id = APP_APNS_DEV, $use_sandbox = false)
{
    $deviceToken = $apns_token;
    $message = $data->message;

    $badge = 0;
    $body = array();
    if($data->activity_type) $body['activity_type'] = $data->activity_type;
    if($data->activity_seq) $body['activity_seq'] = $data->activity_seq;
    if($data->target_type) $body['target_type'] = $data->target_type;
    if($data->target_seq) $body['target_seq'] = $data->target_seq;
    if($data->target_url) $body['target_url'] = $data->target_url;
    if($data->hash_key) $body['hash_key'] = $data->hash_key;
    if($data->time) $body['time'] = $data->time;

    if (trim($message) != '') {
        $body['aps'] = array('alert' => $message, 'badge' => $badge + 0, 'sound' => 'default');
    }

    if ($use_sandbox == USE_SANDBOX) {
        $gateway = 'ssl://gateway.sandbox.push.apple.com:2195';
        $apnsCert = BASEPATH . 'develop_apns.pem';
    } else {
        $gateway = 'ssl://gateway.push.apple.com:2195';
        if ($app_id == APP_APNS_DEV) {
            $apnsCert = BASEPATH . 'production_apns.pem';
        } else {
            $apnsCert = BASEPATH . 'production_apns.pem';
        }
    }

    $streamContext = stream_context_create();
    stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
    $fp = stream_socket_client($gateway, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $streamContext);

    if (!$fp) {
        $error_msg = "Failed to connect $error $errorString\n";

        return $error_msg;
    }

    // 배열을 json으로 변경
    $payload = json_encode($body);
    $msg = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;

    //print "Sending message :" . $payload . "\n";
    $writeResult = fwrite($fp, $msg);
    fclose($fp);

    return $payload.'-'.$writeResult;
}


/**
 * GCM 전송
 *
 * @param      string  $registration_ids  푸시 토큰
 * @param      array  $data              데이터
 */
function send_gcm($registration_ids, $data)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $gcm_auth_key = 'xxxxxxxxxxx';

    $gcm_registration_id = $registration_ids;

    $message = $data->message;
    $type = $data->type;
    $hash_key = $data->hash_key;
    $activity_type = $data->activity_type;
    $activity_seq= $data->activity_seq;
    $target_seq = $data->target_seq;
    $target_type = $data->target_type;
    $target_url = $data->target_url;
    $time = $data->time;

    //$title   		= $data->title;
    //$id      		= $data->id;

    $headers = array(
        'Content-Type:application/json',
        'Authorization:key=' . $gcm_auth_key // API KEY
    );

    $arr = array();
    $arr['data'] = array();
    $arr['data']['type'] = $type . '';
    $arr['data']['message'] = $message;
    $arr['data']['hash_key'] = $hash_key;
    $arr['data']['activity_type'] = $activity_type;
    $arr['data']['activity_seq'] = $activity_seq;
    $arr['data']['target_type'] = $target_type;
    $arr['data']['target_seq'] = $target_seq;
    $arr['data']['time'] = $time;
    if (isset($data->target_url))
        $arr['data']['target_url'] = $target_url;
    if (isset($data->defaults))
        $arr['data']['defaults'] = $data->defaults;
    if (isset($data->flags))
        $arr['data']['flags'] = $data->flags;
    if (isset($data->ledARGB))
        $arr['data']['ledARGB'] = $data->ledARGB;
    if (isset($data->ledOffMS))
        $arr['data']['ledOffMS'] = $data->ledOffMS;
    if (isset($data->ledOnMS))
        $arr['data']['ledOnMS'] = $data->ledOnMS;
    if (isset($data->number))
        $arr['data']['number'] = $data->number;
    if (isset($data->priority))
        $arr['data']['priority'] = $data->priority;
    if (isset($data->toast_show))
        $arr['data']['toast_show'] = $data->toast_show;
    if (isset($data->toast_duration))
        $arr['data']['toast_duration'] = $data->toast_duration;
    if (isset($data->toast_gravity))
        $arr['data']['toast_gravity'] = $data->toast_gravity;


    $arr['registration_ids'] = array();
    $arr['registration_ids'][0] = $gcm_registration_id;

    $ch = curl_init();
    //{"multicast_id":7311951567043614455,"success":0,"failure":1,"canonical_ids":0,"results":[{"error":"MismatchSenderId"}]}
    curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
    $response = curl_exec($ch);
    curl_close($ch);


    return $response;
}

/**
 * 등록된 push 토큰 존재여부 체크
 * @param $account_seq
 * @return bool
 */
function exist_push_token($account_seq)
{
    $ci =& get_instance();
    $ci->load->model('badges');

    $db = $ci->db;
    $db->from('AUTH_TOKENS T');
    $db->join('DEVICES D',"D.seq = T.device_seq");
    $db->where('T.account_seq',$account_seq);
    $db->where('D.push_token !=','');
    $infos = $db->get()->result();

    return (count($infos) > 0)?true:false;
}