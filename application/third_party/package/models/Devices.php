<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

/**
 * 디바이스 데이터 관리
 */
class DEVICES extends Model_base
{
	/**
	 * @var string
	 */
	protected $table       = 'DEVICES';

	/**
	 * @var bool
	 */
	protected $soft_delete = FALSE;

	/**
	 * DEVICES constructor.
	 */
	function __construct()
	{
		parent::__construct();
	}


	/**
	 * 결과 반환값 연관배열로 재정의
	 * @param $data trim_by_seq에서 넘어온 데이터
	 * @param null $account_seq 계정번호
	 * @return array 연관배열로 재정의 된 값
	 */
	function trim($data, $account_seq = null)
	{
		if (!$data)
		{
			$result = array();
			$result['device_seq'] = 0;
			$result['model_name'] = 'unknown';
		} else {
			$result = array();
			$result['device_seq'] = $data->seq + 0;
			$result['model_name'] = $data->model_name . '';
			$result['use_social_push'] = $data->use_social_push . '';
			$result['use_favorite_push'] = $data->use_favorite_push . '';
			$result['use_notice_push'] = $data->use_notice_push . '';
		}

		return $result;
	}

	/**
	 * 고유 ID로 데이터 반환
	 * @param $id 데이터베이스 고유 ID
	 * @param null $account_seq 계정번호
	 * @return array 고유ID SELECT 값
	 */
	function trim_by_seq($id, $account_seq = null)
	{
		return $this->trim($this->get($id), $account_seq);
	}

	/**
	 * 푸시 토큰으로 디바이스 ID 반환
	 * @param $push_token 푸쉬 토큰
	 * @return bool 결과 값
     */
	function get_device_id_by_push_token($push_token)
	{
		if (trim($push_token)=='') return false;

		$where = array();
		$where['search']['push_token'] = $push_token;

		$info = $this->find_one($where);
		if (!$info) return false;

		return $info->device_id;
	}
}
