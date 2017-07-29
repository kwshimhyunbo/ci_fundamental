<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Admin_base
 */
class AdminBase_Controller extends CI_Controller
{
    protected $layout;
    protected $allowClasses = array();
    protected $allowMethods = array();
    protected $admin_session;
    protected $is_admin_login;
    protected $admin_info;
    protected $session_key;
    protected $is_loaded_twig;

    public function __construct()
    {
        parent::__construct();
        $this->_init_twig();
        header("Content-type: text/html; charset=UTF-8");

        $this->load->library('session');
        $this->load->model('sessions');
        $this->load->model('accounts');

        $this->is_admin_login = $this->session->userdata('is_admin_login');
        $this->admin_info = $this->session->userdata('admin_info');
        $this->session_key = $this->session->userdata('session_key');

        $this->allowClasses = array("auth");

        $this->check_web_sign_in($this->allowMethods, $this->allowClasses);

        $tap = $this->uri->segment(2);

        if ($tap != 'auth') {
//            $this->permission_check();
//            $this->admin_accounts->add_manager_logged_list_data($this->uri->segment(2), $this->uri->segment(3)); /* 관리자 로그 저장 */
        }

    }

    public function check_authorize()
    {
        $tab = $this->uri->segment(2);
        $seg = $this->uri->segment(3);
        // navbar active menu
        $this->active_menu = $this->uri->segment(2);
        // navbar sub active menu
        $this->active_submenu = $this->uri->segment(3);
        // navbar sub active menu
        $this->active_trdmenu = $this->uri->segment(4);
        // flash confirm
//        $this->flash_confirm = $this->session->flashdata(FLASH_CONFIRM);
        $this->login_info = $this->login_admin_info();
//        $this->login_info = $this->login_user_info();
    }
//
    protected function login_admin_info() {

        $login_user_info = $this->session->userdata('admin_info');

        if (!empty($login_user_info)) {

            $result = $login_user_info;

        } else {
            $result = array();
        }

        return $result;
    }

    /**
     * Twig 초기화
     */
    protected function _init_twig()
    {
        // Twig 전역 변수
        $vars = [
            'admin_info' => $this->login_admin_info(),
            'return_url' => urldecode($this->input->get('return_url')),
            'tab' => $this->uri->segment(1),
            'seg' => $this->uri->segment(2),
            'arg' => $this->uri->segment(3),
        ];

        // Twig 전역 함수
        $functions = [
            'active_menu'       => function() { return $this->active_menu; },
            'active_submenu'    => function() { return $this->active_submenu; },
            'active_trdmenu'    => function() { return $this->active_trdmenu; },
            'is_xhr'            => function() { return $this->input->is_ajax_request(); },
            'flash_confirm'     => function() { return $this->flash_confirm; },
            'GET_PROJECT_NAMING_ARRAY'     => function() { return GET_PROJECT_NAMING_ARRAY(); },
            'GET_OPERATION_SYSTEM_NAMING_ARRAY'     => function() { return GET_OPERATION_SYSTEM_NAMING_ARRAY(); },
            'GET_APP_TYPE_ARRAY'     => function() { return GET_APP_TYPE_ARRAY(); }


        ];
        // Twig 전역 필터
        $filters = [
            new \Twig_SimpleFilter('*_path', 'filter_get_path'),
            new \Twig_SimpleFilter('merge_recursive', 'filter_array_merge_recursive'),
            new \Twig_SimpleFilter('url_decode', 'urldecode'),
        ];
        // Twig Extensions
        $extensions = [
            new Twig_Extensions_Extension_Text(),
        ];
        // Load Twig environment
        $config = [
            'functions_safe' => [
                'current_url', 'random_string', // CI functions
                'get_qs_url', 'qs_url', 'get_paging_args', 'get_url_parameter',
            ],
            'auto_reload' => true,
            'cache' => false,
        ];
        $this->load->library('twig', $config);
        $twig = $this->twig->getTwig();

        // Register variables
        foreach ($vars as $k => $var)
            $twig->addGlobal($k, $var);
        // Register filters
        foreach ($filters as $filter)
            $twig->addFilter($filter);
        // Register functions
        foreach ($functions as $k => $function)
            $twig->addFunction(new \Twig_SimpleFunction($k, $function, ['is_safe' => ['html']]));
        // Register extensions
        foreach ($extensions as $extension)
            $twig->addExtension($extension);
    }

    protected function _check_service_registered()
    {
        $user_info = $this->login_admin_info();
        $account_seq = $user_info->seq;
        $sms_info = $this->sms_services->get_one("account_seq = {$account_seq}");
        $is_registered = ($sms_info->confirm_state == 1 or $sms_info->reconfirm_state == 1) ? TRUE : FALSE;
        return $is_registered;
    }

    /**
     * 웹 기업 로그인 체크
     * @param string $allowClass 체크할 클래스
     * @param array $allowMethod 통과시킬 메서드 배열
     */
    protected function check_web_sign_in($allowMethods = array(), $allowClasses = array())
    {
        header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
        header("Pragma: no-cache"); //HTTP 1.0
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Content-type: text/html; charset=utf-8");

        $need_login = true;

        $this->load->library('session');
        $this->load->model('sessions');
        $checkClass = $this->router->class;
        $checkMethod = $this->router->method;

        if(in_array($checkMethod, $allowMethods) || in_array($checkClass, $allowClasses))
        {
            $need_login = false;
        }


        if ($need_login)
        {
            $is_valid_session = true;
            $key_info = $this->sessions->find_by_key($this->session_key);

            if (!$key_info) $is_valid_session = false;
            if ($key_info->account_seq==0)
            {
                $is_valid_session = false;
            }

            if ($key_info->account_seq!=$this->admin_info->seq)
            {
                $is_valid_session = false;
            }

            if(!$this->is_admin_login)
            {
                $is_valid_session = false;
            }

            if (!$is_valid_session)
            {
                $this->session->set_userdata('is_login', false);
                $this->session->set_userdata('admin_info', null);
                $this->session->set_userdata('session_key', null);
                $url = $_SERVER['REQUEST_URI'];
                $url = $_SERVER['QUERY_STRING'] ? $url.'?'.$_SERVER['QUERY_STRING'] : $url;
                print_error_go('로그인 상태가 아닙니다.', "/admin/auth/login");
                return;
            }
        }

    }
}
