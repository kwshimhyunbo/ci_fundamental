<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('api_base.php');
/**
 * 데몬 관련 함수
 *
 */
class Daemon extends APIBase_Controller
{

    /**
     * Constructor
     *
     */

    public $fb_tokens = array();

    /**
     * __construct
     */
    function __construct()
    {
        parent::__construct(TRUE);
        $this->load->model('sms_services');
        $this->load->model('sms_logs');
        $this->check_daemon();
    }

    /**
     * 데몬 실행 방지
     * @return bool
     */
    function check_daemon()
    {
        //var_dump($_SERVER);
        //TODO:데몬 실행 방지책


        return true;
    }


    function iterate_sms_log($begin_time = null)
    {
        $now = getNow();
        $infos = $this->sms_logs->get_many("is_sent = 0 AND reserved_date <= '{$now}' ");
        
        foreach ($infos as $info) {

            if ($begin_time)
            {
                $current_time = time();
                $gap_time = $current_time - $begin_time;
                if ($gap_time>60) return;
            }

            $result = send_sms_apistore($info->phone_number, "[예약확인]",$info->message);
            $this->sms_logs->save([
                $info->seq,
                'is_sent' => 1,
                'return_data' => json_encode($result),
                'updated_at' => $now
            ]);
        }
    }








    /**
     * 보낼 문자가 있는지 체크
     *
     */
    public function check_sms()
    {
        $begin_time = time();
        $i = 0;
        while (true)
        {
            ++$i;

            if ($i>50) return;

            if ($begin_time)
            {
                $current_time = time();
                $gap_time = $current_time - $begin_time;
                if ($gap_time>60) return;
            }

            $this->iterate_sms_log($begin_time);

            sleep(1);
        }
    }

    /**
     * GCM 전송
     *
     */
    public function iterate_gcm_log($begin_time = null)
    {
        $where = array();
        $where['search']['is_sent'] = 0;
        $where['search']['created_at <='] = getNow();
        $where['limit'] = 100;
        $rows = $this->push_gcm_logs->finds($where);

        foreach ($rows as $row)
        {
            if ($begin_time)
            {
                $current_time = time();
                $gap_time = $current_time - $begin_time;
                if ($gap_time>60) return;
            }

            $this->push_gcm_logs->update_field_by_name('seq',$row->seq,'is_sent',1);

            $where = array();
            $where['search']['seq'] = $row->seq;
            $gcm_log_info = $this->push_gcm_logs->find_one($where);

            if ($gcm_log_info->is_sent==1)
            {
                $data = new stdClass;
                $data->message = $row->message;
                $data->activity_type = $row->activity_type;
                $data->target_seq = $row->target_seq;
                $data->target_type = $row->target_type;
                $data->hash_key = $row->hash_key;
                $return_data = send_gcm($row->gcm_registration_id, $data);
            } else {
                $return_data = 'is_sent set failed';
            }

            $this->push_gcm_logs->update_field_by_name('seq',$row->seq,'return_data',$return_data);
        }
    }

    /**
     * push_logs에 등록된 푸시그룹 등록
     */
    public function regist_group_push()
    {
        $results = $this->push_logs->get_many(
            [
                'is_registed = 0',
                'order' => 'created_at asc',
                'limit' => 1
            ]
        );

        if (count($results) > 0) {
            $index = 0;
            foreach ($results as $result) {
                $index++;
                $this->push_logs->tran_resigt($result, $index);
            }
        } else {
            echo 'none!';
        }
    }
}

/* End of file deamon.php */
/* Location: ./app/controllers/deamon.php */
?>
