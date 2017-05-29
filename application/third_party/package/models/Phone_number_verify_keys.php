<?php
/**
 */
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

class Phone_number_verify_keys extends Model_base
{
	/**
	 * @var string
	 */
	protected $table       = 'PHONE_NUMBER_VERIFY_KEYS';

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
     * 특정 디바이스에서 요청된 마지막 인증키 정보
     *
     * @param $device_id
     * @param $device_authkey
     * @return null
     */
    function get_lastest_by_device_id($device_seq)
    {
        $where = array();
        $where['search']['device_seq'] = $device_seq;
        $where['order_by'] = 'seq DESC';
        $where['limit'] = 1;

        $infos = $this->finds($where);

        if (!$infos) return NULL;
        if (count($infos)==0) return NULL;

        $info = $infos[0];

        return $info;
    }

    /**
     * 웹에서 요청된 마지막 인증키정보
     *
     * @param $device_id
     * @param $device_authkey
     * @return null
     */
    function get_lastest_by_phone_number($phone_number)
    {
        $where = array();
        $where['search']['PHONE_NUMBER'] = $phone_number;
        $where['order_by'] = 'seq DESC';
        $where['limit'] = 1;

        $infos = $this->finds($where);

        if (!$infos) return NULL;
        if (count($infos)==0) return NULL;

        $info = $infos[0];

        return $info;
    }
}
