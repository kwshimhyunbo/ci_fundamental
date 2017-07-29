<?php
/**
 * everyshot-api
 *
 */
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

/**
 * 세션 데이터 관리
 */
class Sessions extends Model_base
{
	/**
	 * @var string
	 */
	protected $table       = 'SESSIONS';

	/**
	 * @var bool
	 */
	protected $soft_delete = FALSE;

	/**
	 * Constructor
	 *
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 세션 생성
	 * @param $account_seq 회원번호
	 * @return string 결과 값
	 * @throws Exception
	 */
	function add($account_seq)
	{
		$key = $account_seq + time();
		$key = $key . 'saltkey@#^$&@235';
		$key = sha1($key);

		$insert = array();
		$insert['key'] = $key;
		$insert['account_seq'] = $account_seq;

		$this->create($insert);

		return $key;

	}

	/**
	 * KEY 값으로 데이터 반환
	 * @param $key 키값
	 * @return array session 정보
	 */
	function find_by_key($key)
	{
		$where = array();
		$where['search']['key'] = $key;

		$info = $this->find_one($where);

		return $info;
	}


}
