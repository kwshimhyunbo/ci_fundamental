<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

/**
 * 테이블 정보
 */
class Reservation_historys extends Model_base
{
    protected $table = 'RESERVATION_HISTORYS';

    function __construct()
    {
        parent::__construct();
    }


    /** 데이터 삽입
     * @param $data
     */
    function InsertHistorysData($data)
    {
        $this->db->insert('RESERVATION_HISTORYS', $data);
    }

    /*** 데이터 가져오기
     * @param $seq
     */
    function getHistoryData($seq)
    {
        $this->db->select();
        $this->db->from('RESERVATION_HISTORYS');
        $this->db->where("reserve_seq = {$seq}");
        $this->db->order_by("seq", "DESC");
        $result = $this->db->get()->result();
        $this->trim($result);
        return $result;
    }

    function trim(&$infos)
    {
        foreach ($infos as $info) {
            $object = json_decode($info->changed_info);
            $info->from = (String)$object->from;
            $info->to = (String)$object->to;
            unset($info->seq);
            unset($info->admin_seq);
            unset($info->updated_at);
            unset($info->customer_seq);
            unset($info->account_seq);
            unset($info->changed_info);
        }
    }
}
