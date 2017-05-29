<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

/**
 * 인증 TOKEN 데이터 관리
 */
class AUTH_TOKENS extends Model_base
{
	/**
	 * @var string
	 */
	protected $table       = 'AUTH_TOKENS';

	/**
	 * @var bool
	 */
	protected $soft_delete = FALSE;

	/**
	 * AUTH_TOKENS constructor.
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 결과 반환값 연관배열로 재정의
	 * @param $data trim_by_seq에서 넘어온 데이터
	 * @return array 연관배열로 재정의 된 값
	 */
	function trim($data)
	{
		$auth_token = AlphaID($data->seq) . '_' . $data->token;

		$result = array();
		$result['auth_token'] = $auth_token;

		return $result;
	}

	/**
	 * 고유 ID로 데이터 반환
	 * @param $id 데이터베이스 고유 ID
	 * @return array 고유ID SELECT 값
	 */
	function trim_by_seq($id)
	{
		return $this->trim($this->get($id));
	}

	/**
	 * auth_token의 정보 값 반환
	 * @param $auth_token
	 * @return array 결과 값
     */
	function get_info_by_auth_token($auth_token)
	{
		$seq_alpha = substr($auth_token, 0, strpos($auth_token, '_'));
		$seq = AlphaID($seq_alpha, true);
		$token = substr($auth_token, strpos($auth_token, '_')+1);

		$token_info = $this->get($seq);
		if ($token_info->token !== $token) {
			return null;
		}

		return $token_info;
	}


	/**
	 * 새로운 auth_token 생성 및 기기번호 저장
	 * @param $DeviceSeq 기기 번호
	 * @return array 결과 값
	 * @throws Exception
     */
	function new_authtoken($DeviceSeq)
	{
		$CI =& get_instance();
		$CI->load->model('devices');

		$Token = md5(time().rand().'MA55T35ZI1P!!5325');

		$insert = array();
		$insert['token'] = $Token;
		$insert['device_seq'] = $DeviceSeq;
		$insert['account_seq'] = 0;
		$insert['is_expired'] = 0;

		$c_id = $this->create($insert);

		$CI->devices->update_field($DeviceSeq, 'auth_token_seq', $c_id);

		return $this->get($c_id);
	}

}
