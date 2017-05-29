<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('api_base.php');

/**
 * 장비 관련 클래스
 *
 */
class Device extends APIBase_Controller
{

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();

		$this->load->model('devices');
		$this->load->model('auth_tokens');

		header("Content-Type: text/json; charset=utf-8");
	}

	/**
	 * 디바이스 등록
	 *
	 * 앱 설치 후 첫 실행시 디바이스 등록을 수행
	 */
	public function create()
	{
		$this->validator->set_rules('device_id', 'device_id', 'required'); // UUID
		$this->validator->set_rules('model_name', 'model_name', 'required'); // 모델명
		$this->validator->set_rules('platform', 'platform', 'required'); // 플랫폼 (1 : ios, 2 : android, 3 : web)
		$this->validator->set_rules('os_ver', 'os_ver', 'required'); // 운영체제 버전
		$this->validator->run();

		$input = $this->input->post();

		//api 사용 추적용
		api_history_add($_SERVER['PATH_INFO'],$input);

		$device_id = trim($input['device_id']);
		$model_name = trim($input['model_name']);
		$platform = trim($input['platform']);
		$os_ver = trim($input['os_ver']);

		$insert = array();
		$insert['device_id'] = $device_id;
		$insert['model_name'] = $model_name;
		$insert['platform'] = $platform;
		$insert['os_ver'] = $os_ver;

		$c_id = $this->devices->create($insert);
		$token_info = $this->auth_tokens->new_authtoken($c_id);
		$result = $this->auth_tokens->trim($token_info);

		$this->response($this->get_result_set($result, 1, '디바이스 등록'));

	}

	/**
	 * 사용자 푸시 토큰 업데이트
	 */
	public function update_push_info()
	{
		$auth_info = check_auth_token();

		$this->validator->set_rules('push_token', 'push_token', 'required'); // 푸시 토큰
		$this->validator->run();

        $input = $this->input->post();

        //api 사용 추적용
        api_history_add($_SERVER['PATH_INFO'], $input);
		$push_token = trim($input['push_token']);

		//중복된 Push Token 제거
		$this->devices->update_field_by_name('push_token',$push_token,'push_token','');

		//동일한 디바이스에서 등록요청될 경우 나머지 제거
		$device_info = $this->devices->get($auth_info['device_seq']);

		$this->devices->update_field_by_name('device_id',$device_info->device_id,'push_token','');

		$result = $this->devices->update_field($auth_info['device_seq'], 'push_token', $push_token);
		if ($result) {
			$this->response($this->get_result_set(null, 1, 'Push Token 등록 성공'));
		}
		else {
            $message = "Push Token 등록 실패.";
            api_history_add($_SERVER['PATH_INFO']."->error:{$message}",$input);
			$this->response($this->get_result_set(null, -1, 'Push Token 등록 실패'));
		}
	}
}

/* End of file device.php */
/* Location: ./app/controllers/api/device.php */
