<?php
/**
 * Created by PhpStorm.
 * User: Hyeonbo
 * Date: 2017-05-19
 * Time: 오후 2:26
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('api_base.php');

class Test extends APIBase_Controller
{
    function __construct()
    {
        parent::__construct();
        header("Content-Type: text/json; charset=utf-8");
    }

    /***
     *  비밀번호를 업데이트한다.
     */
    function info()
    {
//        $auth_info = check_auth_token();
//        $account_seq = $auth_info['account_seq'];

        $post = $this->input->post(NULL, TRUE);

//        $new_plain_password = trim($post['password']);

        $post= [
            'test' => '1234'
        ];
        $this->response($this->get_result_set($post, 1, "Test"));

    }
}