<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


function GET_PROJECT_NAMING_ARRAY()
{
    $CI =& get_instance();
    $CI->load->model('projects');
    $projects = $CI->projects->get_many();

    $result = [];
    foreach ($projects as $project){
        $result[$project->folder] = $project->name;
    }
    return $result;
}
function GET_OPERATION_SYSTEM_NAMING_ARRAY()
{
    $result[1]="Android";
    $result[2]="iPhone";
    return $result;
}

function GET_APP_TYPE_ARRAY()
{
    $result[1]="store";
    $result[2]="develop";
    $result[3]="test";
    return $result;
}


function starts_with_number($str)
{
    return preg_match('/^\d/', $str) === 1;
}

function remove_phone_dash($phone_number)
{
    $phone_number = str_replace("-", "", $phone_number);
    $phone_number = str_replace("+82", "0", $phone_number);

    return $phone_number;
}

/**
 * @param $type
 *
 * 1:실내 2:외부 3:룸 4:입석 5:바
 */
function get_table_string($type)
{
    if ($type == 1)
        return '테이블석(실내)';
    else if ($type == 2)
        return '테이블석(실외)';
    else if ($type == 3)
        return '룸';
    else if ($type == 4)
        return '입석';
    else if ($type == 5)
        return '카운터(바)석';
    else
        return "";
}

/**
 * @param $reservation_seq
 * @param $history_type
 * @param null $data : 변경 전 데이터
 * @return bool
 *
 * type
 * 1 : status 변경 (1:예약 2:방문확인 3:예약취소 4:NO_SHOW 5:LATE_ARRIVAL)
 * 2 : 시간 변경
 * 3 : 성인 인원 변경
 * 4 : 아이 인원 변경
 * 5 : 테이블 변경
 * 6 : 비고 변경
 * 7 : 직원 변경
 */
function add_history($reservation, $history_type, $from = null, $to = null)
{
    if (!$reservation || !$history_type)
        return false;

    $CI =& get_instance();
    $CI->load->model('reservation_historys');
    $CI->load->model('erl_company_seats');
    $CI->load->model('erl_company_staffs');
    $CI->load->model('erl_datas');

    if (is_numeric($reservation)) {

        $reservation = $CI->erl_datas->get_one($reservation);

        if (!$reservation)
            return false;
    }

    $msg = "";

    if ($history_type == HISTORY_STATUS) {

        $time = new DateTime($reservation->reg_date);

        $table = $reservation->table;
        $table_name = get_table_string($table->table_type);

        $status = $reservation->status;

        if ($to)
            $status = $to;

        // TODO: 오전, 오후 표시
        if ($status == 1)
            $msg = "{$time->format("m월 d일")} {$time->format("H시 i분")} $table_name {$table->number}번 테이블로 예약되었습니다.";
        else if ($status == 2)
            $msg = "방문 확인되었습니다.";
        else if ($status == 3)
            $msg = "예약이 취소되었습니다.";
        else if ($status == 4)
            $msg = "노쇼 처리되었습니다.";
        else if ($status == 5)
            $msg = "지연방문 확인되었습니다.";
    } else if ($history_type == HISTORY_TIME) {

        $org_time = new DateTime($from);
        $new_time = new DateTime($to);

        // TODO: 오전, 오후 표시
        $org_date_string = $org_time->format("m월 d일 H시 i분");
        $new_date_string = $new_time->format("m월 d일 H시 i분");

        $msg = "예약시간이 {$org_date_string}에서 {$new_date_string}으로 변경되었습니다.";

    } else if ($history_type == HISTORY_ADULT_PEOPLE) {

        $msg = "성인 인원이 {$from}명에서 {$to}명으로 변경되었습니다.";

    } else if ($history_type == HISTORY_CHILD_PEOPLE) {

        $msg = "아이 인원이 {$from}명에서 {$to}명으로 변경되었습니다.";

    } else if ($history_type == HISTORY_TABLE) {

        $org_table = $CI->erl_company_seats->get_one($from);
        $new_table = $CI->erl_company_seats->get_one($to);

        $org_table_name = get_table_string($org_table->table_type);
        $new_table_name = get_table_string($new_table->table_type);

        $msg = "$org_table_name {$org_table->number}번 테이블에서 $new_table_name {$new_table->number}번 테이블로 변경되었습니다.";

    } else if ($history_type == HISTORY_NOTE) {

        $msg = "특이사항이 '{$from}'에서 '{$to}' 으로 변경되었습니다.";

    } else if ($history_type == HISTORY_STAFF) {

        $org_staff = $CI->erl_company_staffs->get_one($from);
        $new_staff = $CI->erl_company_staffs->get_one($to);

        $msg = "담당자가 '{$org_staff->staff_name}' 에서 '{$new_staff->staff_name}' 으로 변경되었습니다.";
    }

    $CI->reservation_historys->save([
        "reservation_seq" => $reservation->seq,
        "type" => $history_type,
        "account_seq" => $reservation->account_seq,
        "customer_seq" => $reservation->customer_seq,
        "message" => $msg
    ]);

    return true;
}

/** ====================================================
 * API
 * =====================================================*/
/**
 * 패스워드 생성
 */
function PASSWORD($text)
{
    $CI =& get_instance();
    $CI->load->model('model_base');

    return $CI->model_base->PASSWORD($text);
}

/*
* @author  Kevin van Zonneveld &lt;kevin@vanzonneveld.net>
* @author  Simon Franz
* @author  Deadfish
* @author  SK83RJOSH
* @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
* @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
* @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
* @link    http://kevin.vanzonneveld.net/
*
* @param mixed   $in   String or long input to translate
* @param boolean $to_num  Reverses translation when true
* @param mixed   $pad_up  Number or boolean padds the result up to a specified length
* @param string  $pass_key Supplying a password makes it harder to calculate the original ID
*
* @return mixed string or long
*
*/
function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $out = '';
    $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = strlen($index);

    if ($pass_key !== null) {
        // Although this function's purpose is to just make the
        // ID short - and not so much secure,
        // with this patch by Simon Franz (http://blog.snaky.org/)
        // you can optionally supply a password to make it harder
        // to calculate the corresponding numeric ID

        $index_length = strlen($index);
        for ($n = 0; $n < $index_length; ++$n) {
            $i[] = substr($index, $n, 1);
        }

        $pass_hash = hash('sha256', $pass_key);
        $pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);

        $index_length = strlen($index);
        for ($n = 0; $n < strlen($index); ++$n) {
            $p[] = substr($pass_hash, $n, 1);

        }

        array_multisort($p, SORT_DESC, $i);
        $index = implode($i);
    }

    if ($to_num) {
        // Digital number  <<--  alphabet letter code
        $len = strlen($in) - 1;

        for ($t = $len; $t >= 0; --$t) {
            $bcp = bcpow($base, $len - $t);
            $out = $out + strpos($index, substr($in, $t, 1)) * $bcp;
        }

        if (is_numeric($pad_up)) {
            --$pad_up;

            if ($pad_up > 0) {
                $out -= pow($base, $pad_up);
            }
        }
    } else {
        // Digital number  -->>  alphabet letter code
        if (is_numeric($pad_up)) {
            --$pad_up;

            if ($pad_up > 0) {
                $in += pow($base, $pad_up);
            }
        }

        for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; --$t) {
            $bcp = bcpow($base, $t);
            $a = floor($in / $bcp) % $base;
            $out = $out . substr($index, $a, 1);
            $in = $in - ($a * $bcp);
        }
    }

    return $out;
}

/**
 * 현재 시간 조회
 */
function getNow()
{
    $result = date('Y-m-d H:i:s');

    return $result;
}

/**
 * 이미지 파일 경로 치환
 */
function get_image_url($url)
{
    if (strlen($url) == 0) {
        return "";
    }

    if ((strpos($url, 'http://') !== false) || (strpos($url, 'https://') !== false)) {

        return $url;
    }

    return base_url($url);
}

/**
 * api 사용 히스토리 추적
 * @param $api
 * @param $parameters
 */
function api_history_add($api, $parameters, $result = null)
{
    //커멘트 로그
    $insert = array(
        'api' => $api,
        //		'parameters' => serialize($parameters),
        'parameters' => json_encode($parameters),
        'created_at' => getNow(),
        'ip' => (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : ''
    );
    if ($result) $insert['result'] = json_encode($result);

    $CI =& get_instance();
    $CI->db->insert('API_HISTORY', $insert);
}


/**
 * 이미지 파일 업로드
 */
function upload_image($field_name, $is_resize = true)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    return upload_file($field_name, 'gif|jpg|png|jpeg', $is_resize);
}

/**
 * 이미지 파일 업로드
 */
function upload_app_icon($field_name, $is_resize = true, $upload_path)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));
    return upload_file($field_name, 'ico|jpg|png|jpeg', $upload_path, $is_resize);
}

function upload_app($field_name, $is_resize = true, $upload_path)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));
    return upload_file($field_name, 'ipa|apk',$upload_path, false,MAX_UPLOAD_IMAGE_WIDTH,false);
}
function upload_plist($field_name, $is_resize = true, $upload_path)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));
    return upload_file($field_name, 'plist',$upload_path, false,MAX_UPLOAD_IMAGE_WIDTH,false);
}
/**
 * 이미지 파일 업로드 & 크롭
 */
// function upload_image_in_addition_crop($field_name, $is_resize = true, $crop = true)
// {
//     log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

//     return upload_file($field_name, 'gif|jpg|png|jpeg', $is_resize, MAX_UPLOAD_IMAGE_WIDTH, true, $crop);
// }

/**
 * 파일 업로드
 */
function upload_file($field_name, $allowed_types = 'gif|jpg|png|jpeg', $post_data, $is_resize = false, $image_width = MAX_UPLOAD_IMAGE_WIDTH, $convert_jpg = true )//, $image_crop = false)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));
    $project_name = $post_data['project_name'];
    $platform = GET_OPERATION_SYSTEM_NAMING_ARRAY()[$post_data['platform']];
    $version = $post_data['version'];
    $type = GET_APP_TYPE_ARRAY()[$post_data['type']];
    $name = $post_data['icon']['name'];
    $sub_dir = $project_name."/".$platform."/". $version."/".$type."/".$name;

//    $sub_dir = date('Y') . '/' . date('m') . '/' . date('d') . '/' . date('H') . '/' . date('i') . '/';
    $config['upload_path'] = './uploads/' . $sub_dir;

    mkdir_with_sub($config['upload_path']);

    //$config['allowed_types'] = $allowed_types;
    $config['allowed_types'] = '*';
    $config['max_size'] = 0;
    $config['max_filename'] = 0;
    $config['encrypt_name'] = true;
    $config['remove_spaces'] = true;

    $CI =& get_instance();
    $CI->load->library('upload', $config);
    $CI->upload->initialize($config);

    $result = array();
    $result['is_success'] = false;
    $result['data'] = '';

    if (!$CI->upload->do_upload($field_name)) {
        $xx = array('upload_data' => $CI->upload->data());
        $result['data'] = $CI->upload->display_errors();

    } else {
        $data = array('upload_data' => $CI->upload->data());

        $result['is_success'] = true;
        $result['data'] = '/uploads/' . $sub_dir . $data['upload_data']['file_name'];
        $result['size'] = $data['upload_data']['file_size'];

        if ($is_resize && $data['upload_data']['is_image'] = 1) {
            if ($data['upload_data']['image_width'] > $image_width) {
                $image_config['image_library'] = 'gd2';
                $image_config['source_image'] = $data['upload_data']['full_path'];
                $image_config['maintain_ratio'] = TRUE;
                $image_config['create_thumb'] = FALSE;
                $image_config['width'] = $image_width;
                $image_config['height'] = $data['upload_data']['image_height'];
                $CI =& get_instance();
                $CI->load->library('image_lib', $image_config);
                $CI->image_lib->resize();
            }

            if ($convert_jpg) {
                $CI->load->library('simpleimage');
                $image = new SimpleImage($data['upload_data']['full_path']);
                $jpg_name = $data['upload_data']['file_path'] . $data['upload_data']['raw_name'] . ".jpg";
                $image->save($jpg_name, IMAGETYPE_JPEG);
                if ($data['upload_data']['file_ext'] != ".jpg") {
                    @unlink($data['upload_data']['full_path']);
                }
                $result['data'] = '/uploads/' . $sub_dir . $data['upload_data']['raw_name'] . ".jpg";
            }
        }

        // if($image_crop && $data['upload_data']['is_image'] = 1){

        //     $image_config['image_library'] = 'gd2';
        //     $image_config['source_image'] = $data['upload_data']['full_path'];
        //     $image_config['width'] = 300;
        //     $image_config['height'] = 300;

        // if($width > 680) {
        //     $config['x_axis'] = ;
        //     $config['y_axis'] = ;
        // }

        //     $CI->load->library('image_lib', $image_config);
        //     $CI->image_lib->crop();
        // }

        $insert = array();
        $insert['object_id'] = $result['data'];
        $insert['filename'] = $data['upload_data']['orig_name'];
        $insert['is_image'] = $data['upload_data']['is_image'];
        $insert['width'] = $data['upload_data']['image_width'];
        $insert['height'] = $data['upload_data']['image_height'];
        $insert['filesize'] = $result['size'];
    }

    return $result;
}

/**
 * 썸네일 이미지 생성
 */
function get_thumb_data($path, $percent = 30)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    list($original_width, $original_height, $original_type, $original_attr) = getimagesize($path);

    $upload_path = ini_get('upload_tmp_dir');

    $config['image_library'] = 'gd2';
    $config['source_image'] = $path;
    $config['create_thumb'] = false;
    $config['maintain_ratio'] = true;

    $file = basename($path);
    $config['new_image'] = $upload_path . 'thumb_' . $percent . 'p_' . $file;
    $config['width'] = ($original_width / 10) * ($percent / 10);
    $config['height'] = ($original_height / 10) * ($percent / 10);

    $CI =& get_instance();

    $CI->load->library('image_lib', $config);
    $CI->image_lib->initialize($config);

    $CI->image_lib->resize();

    return $config['new_image'];
}

/**
 * 지정 사이즈 썸네일 이미지 생성
 */
function get_thumb_data_by_size($path, $width = null, $height = null)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    list($original_width, $original_height, $original_type, $original_attr) = getimagesize($path);

    if (!$width) {
        $width = $original_width;
        $height = $original_height;
    }

    $path_parts = pathinfo($path);
    $upload_path = $path_parts['dirname'] . '/';

    $config['image_library'] = 'gd2';
    $config['source_image'] = $path;
    $config['create_thumb'] = false;
    $config['maintain_ratio'] = true;

    if ($original_height > $original_width) {
        $config['rotation_angle'] = 90;
    }

    $file = basename($path);
    $file = str_replace('.', '_new3.', $file);
    $config['new_image'] = $upload_path . $file;
    $config['width'] = $width;
    if ($height == null) {
        $config['height'] = $original_height / ($original_width / $width);
    }

    // 원본 사이즈가 줄이려는 사이즈보다 작으면 해상도를 줄이지 않음
    if ($original_width < $width) {
        $config['width'] = $original_width;
        $config['height'] = $original_height;
    }

    $CI =& get_instance();

    $CI->load->library('image_lib', $config);
    $CI->image_lib->clear();
    $CI->image_lib->initialize($config);

    if ($original_height > $original_width) {
        $CI->image_lib->rotate();
    } else {
        $CI->image_lib->resize();
    }

    return $config['new_image'];
}

/**
 * url 경로로 값을 저장
 */
function save_from_url($url, $path = null, $filename = null)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    if (!$path) {
        $path = ini_get('upload_tmp_dir');

        if ($path[strlen($path) - 1] != '/') {
            $path .= '/';
        }

        if (!is_writable($path)) {
            $path = './uploads/';
        }
    }

    $sub_dir = date('Y') . '/' . date('m') . '/' . date('d') . '/' . date('H') . '/' . date('i') . '/';
    $path .= $sub_dir;
    debug_slack('#crawl_tp_new_post', "{$path} 에 저장을 시도합니다.");
    mkdir_with_sub($path);

    if (!$filename) {
        $basename = md5(basename($url) . time() . rand(0, 9999999)) . '_' . time() . '_' . rand(0, 9999999) . '.jpg';
    } else {
        $basename = $filename;
        debug_slack('#crawl_tp_new_post', "{$filename} 으로 저장을 시도합니다.");
    }
    $newfname = $path . $basename;

    $ch = curl_init($url);
    $fp = fopen($newfname, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (!file_exists($newfname)) {
        debug_slack('#crawl_tp_new_post', "{$newfname} 저장에 실패하였습니다.");
        return false;
    }

    return $newfname;
}

/**
 * 하위 디렉토리 포함하여 디렉토리 생성
 *
 */
function mkdir_with_sub($fullDir)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    $dirs = explode("/", $fullDir);
    $currentFolder = '';

    //for ($x=0; $x<count($dirs); $x++)
    $x = 0;
    $count_dirs = count($dirs);
    while ($x < $count_dirs) {
        $currentFolder .= $dirs[$x] . '/';
        if (!is_dir($currentFolder)) {
            if (!@mkdir($currentFolder, 0777, true)) {
                //die("Could not make " . $currentFolder);
            }
            @chmod($currentFolder, 0777);
        }

        ++$x;
    }

}

/** ====================================================
 * Class APIException
 * =====================================================*/
class APIException extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        $trace = debug_backtrace();
        $function_name = $trace[2]["function"];

        $array = array('return_code' => $code + 0, 'return_message' => $message, 'function_name' => $function_name);
        if (!isset($_REQUEST['callback'])) {
            echo pretty_json(json_encode($array));
        } else {
            echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
        }
        exit;
    }
}

/**
 * 에러 메세지 노출 후 이전페이지로 이동
 * @param string $message 에러 메세지
 */
function print_error_back($message)
{
    $strip_message = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $message);
    $strip_message = htmlspecialchars(strip_tags($strip_message), ENT_QUOTES);
    $strip_message = trim(preg_replace('/\s+/', ' ', $strip_message));
    echo "<script>window.alert('{$strip_message}'); window.history.back();</script>";
    echo '<noscript>Error! Please back your location history.</noscript>';
    exit;
}

/**
 * 에러메세지 후 특정페이지로 이동
 * @param string $message 에러메세지
 * @param string $url 주소
 */
function print_error_go($message, $url = '')
{
    if (!preg_match('#^(\w+:)?//#i', $url)) {
        $url = site_url($url);
    }
    $strip_message = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $message);
    $strip_message = htmlspecialchars(strip_tags($strip_message), ENT_QUOTES);
    $strip_message = trim(preg_replace('/\s+/', ' ', $strip_message));
    echo "<script>window.alert('{$strip_message}'); window.location.replace('{$url}');</script>";
    echo '<noscript><a href="' . $url . '">Error! Click here to redirect.</a></noscript>';
    exit;
}


/**
 * 검색폼 관련 get 인자 모음 반환
 *
 * @parameter $merge_target_array
 * @return array
 */
function get_search_args($merge_target_array = array())
{
    $CI =& get_instance();

    return array_merge(array(
        'start_at' => trim($CI->input->get('start_at')),
        'end_at' => trim($CI->input->get('end_at')),
        'keyword' => trim($CI->input->get('keyword')),
        'daterange' => trim($CI->input->get('daterange')),
        'daterange_options' => array( // select option 출력용 데이터들
            'custom' => '날짜 선택',
            'all' => '전체',
            'today' => '오늘',
            'yesterday' => '어제',
            '7_days' => '최근 7일',
            '30_days' => '최근 30일',
        ),
    ), $merge_target_array);
}


/**
 * 검색폼 날짜 인자 where 로 변환
 *
 * @param $column - 데이터베이스 날짜 컬럼
 * @param $search_args - get_search_args()
 * @return string - WHERE 쿼리 string
 */
function get_search_date_where($column, $search_args)
{
    $where = '';
    if (empty($column)) return $where;

    $daterange = isset($search_args['daterange']) ? $search_args['daterange'] : null;
    $start_at = isset($search_args['start_at']) ? $search_args['start_at'] : null;
    $end_at = isset($search_args['end_at']) ? $search_args['end_at'] : null;
    $same_at = null;

    switch ($daterange) {
        case 'custom':
            if (!empty($start_at) && preg_match('/(\d{4}-\d{2}-\d{2})/', $start_at))
                $start_at = date('Y-m-d', strtotime($start_at));
            if (!empty($end_at) && preg_match('/(\d{4}-\d{2}-\d{2})/', $end_at))
                $end_at = date('Y-m-d', strtotime($end_at));
            break;
        case 'all':
            break;
        case 'today':
            $same_at = date('Y-m-d');
            break;
        case 'yesterday':
            $start_at = date('Y-m-d', strtotime('-1 day'));
            break;
        case '7_days':
        case '30_days':
            $date_string = '-' . str_replace('_', ' ', $daterange); // 'x_days' => '-x days'
            $start_at = date('Y-m-d', strtotime($date_string));
            break;
    }

    if (!empty($same_at))
        $where .= " AND DATE_FORMAT({$column}, '%Y-%m-%d') = '{$same_at}'";
    if (!empty($start_at))
        $where .= " AND DATE_FORMAT({$column}, '%Y-%m-%d') >= '{$start_at}'";
    if (!empty($end_at))
        $where .= " AND DATE_FORMAT({$column}, '%Y-%m-%d') <= '{$end_at}'";

    return $where;
}


/**
 * 검색폼 키워드 인자 where 로 변환
 *
 * @param $column - 데이터베이스 키워드 검색 컬럼
 * @param $allowed_columns - 데이터베이스 필터 스트링 배열
 * @param $search_args - get_search_args()
 * @return string - WHERE 쿼리 string
 */
function get_search_keyword_where($column, $allowed_columns, $search_args)
{
    $where = '';
    if (empty($column) || $column === 'none') return $where;

    // join query 처리 TABLENAME.column
    $splitter = explode('.', $column);
    $column_name = '';
    if (isset($splitter[1])) {
        $column_name = $splitter[1];
    } else {
        $column_name = $splitter[0];
    }

    if (empty($search_args['keyword']) || !array_key_exists($column_name, $allowed_columns))
        return $where;

    return " AND {$column} like '%{$search_args['keyword']}%'";
}


/**
 * 페이지 네비게이션 args array 리턴
 *
 * @param int $total_count 아이템 총 개수
 * @param int $page 현재 페이지
 * @param int $views 페이지당 아이템 (views per page)
 * @return array
 */
function get_paging_args($total_count, $page = null, $views = null)
{
    $CI =& get_instance();
    $input = $CI->input->get();
    $page = !empty($page)? $page : 0;
    $views = !empty($views)? $views : 20;
//    $page = trim($CI->input->get('page')) ?: 0;
//    $views = trim($CI->input->get('views')) ?: 20;

    $offset = $page * $views;

    $total_page = ceil($total_count / $views);
    if ($page > $total_page) $page = $total_page;

    $begin_page = floor($page / 10) * 10;
    $end_page = $begin_page + 10;
    if ($end_page > $total_page) $end_page = $total_page;

    $is_show_begin_page = true;
    if ($begin_page == 0) $is_show_begin_page = false;
    $is_show_end_page = true;
    if ($end_page == $total_page) $is_show_end_page = false;

    $is_show_next_page = true;
    if ($end_page == $total_page) $is_show_next_page = false;
    $is_show_prev_page = true;
    if ($begin_page == 0) $is_show_prev_page = false;

    return array(
        'total_count' => $total_count,
        'page' => $page,
        'views' => $views,
        'offset' => $offset,
        'total_page' => $total_page,
        'begin_page' => $begin_page,
        'end_page' => $end_page,
        'is_show_begin_page' => $is_show_begin_page,
        'is_show_end_page' => $is_show_end_page,
        'is_show_next_page' => $is_show_next_page,
        'is_show_prev_page' => $is_show_prev_page,
    );
}


/**
 * 쿼리스트링 URL 을 병합해서 반환
 *
 * @param string $url : 타겟 url
 * @param array $querystrings : 이어붙여 병합할 쿼리스트링 정보 배열
 * @param bool $merge : 쿼리스트링 병합 여부
 * @return string
 */
function qs_url($url, $querystrings, $merge = true)
{
    if (!preg_match('#^(\w+:)?//#i', $url)) {
        $url = site_url($url);
    }
    // 현재 페이지의 쿼리스트링을 가져온다
    $base_querystrings = $merge && isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    // URL 정보 분리 (array)
    $url_parts = parse_url($url . $base_querystrings);
    $url_query = array();
    // URL 정보에서 쿼리스트링이 있으면 $url_query 에 저장 (array)
    if (isset($url_parts['query'])) {
        parse_str($url_parts['query'], $url_query);
    }
    // 현재 URL 의 쿼리스트링과 $querystrings 쿼리스트링과 병합 (array)
    $queries = array_merge($url_query, $querystrings);
    // 타겟 URL 의 쿼리스트링 정보 삭제
    $constructed_url = strtok($url, '?');
    // 병합된 쿼리스트링 정보가 있으면 타겟 URL 에 병합된 쿼리스트링을 이어 붙여 반환, 없으면 그냥 반환
    return empty($queries) ? $url : $constructed_url . '?' . http_build_query($queries, '', '&');
}


function get_qs_url($url, $querystrings, $base_querystrings = '')
{
    $url_parts = parse_url($url);
    $query = array();
    parse_str($querystrings, $query);
    $url_query = array();
    if (isset($url_parts['query'])) parse_str($url_parts['query'], $url_query);
    $queries = array_merge($url_query, $query);
    $constructed_url = strtok($url, '?');
    return $constructed_url . '?' . http_build_query($queries) . $base_querystrings; // 원래 주소의 get data를 이어붙임
}

/**
 * 현재 url의 page를 제외한 get 데이터 가져오는 함수
 */
function get_url_parameter()
{
    $query_string = $_SERVER["QUERY_STRING"];

    $querys = explode('&', $query_string);

    $base_parameter = '';
    foreach ($querys as $query) {
        $parameter = explode('=', $query);

        if ($parameter[0] != 'page') {
            $base_parameter .= '&' . $query;
        }
    }
    return $base_parameter;
}


/**
 * curl로 post 전송
 *
 */
function curl_send_data_with_post($url, $data)
{
    log_message('info', __METHOD__ . ' ' . var_export(func_get_args(), true));

    /*
    example:
        $data = array('first_name' => 'John', 'email' => 'john@example.com', 'phone' => '1234567890',  );
        echo curl_send_data_with_post('http://www.example.com/', $data);
    */

    if (empty($url) OR empty($data)) {
        return 'Error: invalid Url or Data';
    }


    //url-ify the data for the POST
    $fields_string = '';
    foreach ($data as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    $fields_string = rtrim($fields_string, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); # timeout after 10 seconds, you can increase it
    //curl_setopt($ch,CURLOPT_HEADER,false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  # Set curl to return the data instead of printing it to the browser.
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)"); # Some server may refuse your request if you dont pass user agent

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    //execute post
    $result = curl_exec($ch);

    //close connection
    curl_close($ch);

    return $result;
}


/**
 * api post 호출
 *
 * @param string $rel_url
 * @param array $data
 * @return * result
 */
function get_json_api_post($rel_url, $data, $message = '', $return_url = '', $raw_result = false)
{
    $result = null;
    $json = null;
    $return_message = '서버 접속에 실패했습니다.\n다시 시도해주세요.';

    try {
        $resp = curl_send_data_with_post($rel_url, $data);

        $json = json_decode($resp, true);
        if (empty($json)) {
            $return_message = '데이터를 불러오는데 실패했습니다.\n다시 시도해주세요.';
            throw new Exception($message);
        }

        if ($raw_result) {
            $result = $json;
        } else {
            $result = element('result', $json, array());
            if ((int)element('return_code', $json, 0) !== 1) {
                $return_message = isset($json['return_message']) ? $json['return_message'] : $return_message;
                throw new Exception($message);
            }
        }
    } catch (Exception $e) {
        $em = $e->getMessage();
        $message = isset($em) ? $em : $message;

        !empty($return_url) ?
            print_error_go($message . $return_message, $return_url) :
            print_error_back($message . $return_message);
    }

    return !empty($result) ? $result : null;
}

/**
 * 전화번호 필터링
 * @param $phone_number
 * @return mixed|string
 */
function phone_number_filter($phone_number)
{
    $phone_number = trim($phone_number);

    $phone_number = str_replace('+', '', $phone_number);
    $phone_number = str_replace(')', '', $phone_number);
    $phone_number = str_replace('(', '', $phone_number);
    $phone_number = str_replace('-', '', $phone_number);
    $phone_number = str_replace(' ', '', $phone_number);

    return $phone_number;
}

/*** 입력값 검증
 * @param $info
 * @param $account_seq
 */
function info_validation_check($info, $account_seq)
{
    if (empty($info)) {
        print_error_back(ACCESS_DENIED);
    }

    if ($info->account_seq != $account_seq) {
        print_error_back(ACCESS_DENIED);
    }

}

function find_date($num ,$type, $date)
{
    if($type == "day"){
        $result = date("Y-m-d", strtotime("{$num} day", strtotime($date)));
    } else if( $type == "month") {
        $result = date("Y-m-d", strtotime("{$num} month", strtotime($date)));
    }
    return $result;
}

function get_date($ci){
    $get = $ci->input->get(null, TRUE);

    //default
    $now = getNow();
    $date_time = new DateTime($now ? $now : "");
    $end_at = $date_time->format('Y-m-d');
    $date_time->modify("-7 day");
    $start_at = $date_time->format('Y-m-d');

    //input이 있을경우 교체
    $start_at = empty($get['start_at'])? $start_at: $get['start_at'];
    $end_at = empty($get['end_at'])? $end_at: $get['end_at'];
    $result = [
        'start_at' => $start_at,
        'end_at' => $end_at
    ];

        return (Object)$result;
}