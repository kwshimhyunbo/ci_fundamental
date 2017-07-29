<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('Admin_base.php');

/**
 * 로그인
 *
 */
class Auth extends AdminBase_Controller
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->load->model('accounts');
    }

    public function index()
    {

    }

    public function login()
    {
        if($_SERVER['REQUEST_METHOD'] == "POST") {
            $input = $this->input->post(NULL, TRUE);
            $email = trim($input['email']); // 유저 ID
            $password = trim($input['password']); // 비밀번호
            $info = $this->accounts->get_one("email = '{$email}'");

            if(empty($info)) {
                print_error_back(LOGIN_FAILED); return;
            }

            if (PASSWORD($password)!=$info->password) {
                print_error_back(LOGIN_FAILED);
                return;
            }

            $session_key = $this->sessions->add($info->seq);
            $this->session->set_userdata('is_admin_login', TRUE);
            $this->session->set_userdata('admin_info', $info);
            $this->session->set_userdata('session_key', $session_key);
            $return_url = "admin/main";

            redirect($return_url); return;
        } else {
            $data = array();

            $data['data']['return_url'] = $this->input->get('return_url', TRUE);

            $this->twig->display('admin/auth/login', $data);
        }
    }

    /**
     * 로그아웃
     */
    public function logout()
    {

//        $this->admin_accounts->add_manager_logged_list_data($this->uri->segment(2), $this->uri->segment(3)); /* 관리자 로그 저장 */

        $this->session->set_userdata('admin_info', null);
        $this->session->set_userdata('session_key', null);
        //$this->session->sess_destroy();
        redirect('admin/auth/login');
    }

}
