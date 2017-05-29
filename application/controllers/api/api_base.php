<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');



/**
 * 디바이스 키가 맞지 않을때 예외처리 클래스
 *
 */
class APIDeviceKeyInvalidException extends Exception
{
	public function __construct()
	{
		$trace = debug_backtrace();
		$function_name = $trace[2]["function"];

		$array = array('return_code' => 401, 'return_message' => 'device 인증키가 올바르지 않습니다', 'function_name' => $function_name);
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
		exit;
	}
}

/**
 * 필수 파라미터가 없을때 예외처리 클래스
 *
 */
class APIRequireParamException extends Exception
{
	public function __construct($param)
	{
		$trace = debug_backtrace();
		$function_name = $trace[1]["function"];

		$array = array('return_code' => "402", 'return_message' => '요청 파라메터중 필수 파라메터가 빠져있습니다. '.$param, 'function_name' => $function_name);
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
		exit;
	}
}

/**
 * 맞지 않는 파라미터가 들어왔을때 예외처리 클래스
 *
 */
class APIInvalidParamException extends Exception
{
	public function __construct($param)
	{
		$trace = debug_backtrace();
		$function_name = $trace[1]["function"];

		$array = array('return_code' => "403", 'return_message' => '파라메터 값에 오류가 있습니다. '.$param, 'function_name' => $function_name);
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
		exit;
	}
}


/**
 * 데이터를 찾을수 없을때 예외처리 클래스
 *
 */
class NotFoundException extends Exception
{
	public function __construct()
	{
		$trace = debug_backtrace();
		$function_name = $trace[1]["function"];

		$array = array('return_code' => "502", 'return_message' => '데이터를 찾을 수 없습니다.', 'function_name' => $function_name);
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
		exit;
	}
}

/**
 * 폰 인증이 필요할때 예외처리 클래스
 *
 */
class NeedPhoneVerifyException extends Exception
{
	public function __construct()
	{
		$trace = debug_backtrace();
		$function_name = $trace[1]["function"];

		$array = array('return_code' => "503", 'return_message' => '휴대폰 인증이 필요합니다.', 'function_name' => $function_name);
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
		exit;
	}
}



/**
 * API 기본 클래스
 *
 */
class APIBase_Controller extends CI_Controller
{
	/**
	 * 토큰 정보
	 *
	 * @var string $token_info
	 */
	protected $token_info = null;

	/**
	 * header를 html로 할지 json으로 할지
	 *
	 * @var bool $is_html
	 */
	protected $is_html = FALSE;

	/**
	 * Constructor
	 *
	 * @param bool  $is_html
	 */
	public function __construct($is_html = FALSE)
	{
		parent::__construct();

		$this->load->model("devices");
//		$this->load->model("accounts");
		$this->load->model("auth_tokens");

		header("Content-Type: text/json; charset=utf-8");
	}

	protected function _post($name, $default = null)
	{

		$data = trim($this->input->post($name));

		if ($data == null)
			return $default;

		return $data;
	}

	protected function _get($name, $default = null)
	{

		$data = trim($this->input->get($name));

		if ($data == null)
			return $default;

		return $data;
	}

    /**
	 * json 결과 반환
	 *
	 * @param array  $data 반환 데이터
	 * @param string  $retcode 반환 코드
	 * @param string  $message 메시지
	 * @return array json 데이터
	 */
	protected function get_result_set($data = null, $retcode = '1', $message = '' , $cursor_next = 'undefined')
	{
		$trace = debug_backtrace();
		$function_name = $trace[1]["function"];

		if($retcode == "1")
		{
			$result = array('return_code' => $retcode+0, 'return_message' => $message . '', 'function_name' => $function_name, 'result' => $data);
            if ($cursor_next != 'undefined') $result['cursor_next'] = $cursor_next == null ? null : strval($cursor_next);
			return $result;
		} else {
			$result = array('return_code' => $retcode+0, 'return_message' => $message.'', 'function_name' => $function_name);

			return $result;
		}
	}

	protected function check_auth_token()
	{
		if(!isset($_REQUEST['auth_token']) || trim($_REQUEST['auth_token']) == '')
			throw new APIDeviceKeyInvalidException();

		$auth_token = $_REQUEST['auth_token'];

		$seq_alpha = substr($auth_token, 0, strpos($auth_token, '_'));
		$seq = AlphaID($seq_alpha, true);
		$token = substr($auth_token, strpos($auth_token, '_')+1);

		$token_info = $this->auth_tokens->get($seq);

		if ((!$token_info) || ($token_info->token!==$token))
			throw new APIDeviceKeyInvalidException();

		$device_info = $this->devices->get($token_info->device_seq);
		$account_info = $this->account_views->get($token_info->account_seq);
		
		//ACCOUNTS table 의 seq = 0 은 디폴트 유저용으로 채워둬야 문제가 없음
		//seq == 0인건 예외

        $trace = debug_backtrace();
        $function_name = $trace[1]["function"];

		if ($token_info->account_seq>0)
		{
            if ($function_name!='sign_out')
            {
                if ($account_info->is_retracted==1)
                {

                    throw new APIException("탈퇴한 계정입니다.", -102);
                }

                if ($account_info->is_blocked==1)
                {
                    throw new APIException("관리자에 의해 차단된 계정입니다. 처음으로 돌아갑니다.", -102);
                }
            }
		}

//		$this->accounts->update_last_logged_at($token_info->account_seq);

		$result = new stdClass;
		$result->token_info = $token_info;
		$result->device_info = $device_info;
		$result->account_info = $account_info;

		return $result;
	}

	/**
	 * 필수 파라미터 확인
	 *
	 * @param array  $params 필수파라미터 문자열
	 */
	protected function check_require_param($params)
	{
		if(is_array($params))
		{
			foreach($params as $p)
			{
				if(trim($_REQUEST[$p]) == "")
					throw new APIRequireParamException($p." is required");
			}
		} else {
			if($_REQUEST[$params] == "")
				throw new APIRequireParamException($p." is required");
		}
	}

	/**
	 * json 결과 출력
	 *
	 * @param array $array 출력할 json배열
	 */
	function response($array)
	{
		if (!isset($_REQUEST['callback']))
		{
			echo pretty_json(json_encode($array));
		} else {
			echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
		}
	}
}