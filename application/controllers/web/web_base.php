<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Web_base
 */
class Web_base extends CI_Controller
{
    protected $active_menu = '';
    protected $active_submenu = '';
    protected $flash_confirm = null;
    protected $return_url = null;
    protected $login_info = null;
    protected $get_week_day = null;

    public function __construct()
    {
        parent::__construct();

        $this->_init_twig();
        $this->check_authorize();
        $this->return_url = urldecode($this->input->get('return_url'));
    }

    public function check_authorize()
    {
        $tab = $this->uri->segment(1);
        $seg = $this->uri->segment(2);
        // navbar active menu
        $this->active_menu = $this->uri->segment(1);
        // navbar sub active menu
        $this->active_submenu = $this->uri->segment(2);
        // navbar sub active menu
        $this->active_trdmenu = $this->uri->segment(3);

//        if ($tab != 'auth') {
//            $user_check = $this->session->userdata('user_info');
//            if (!$user_check) {
//                print_error_go('로그인이 필요한 메뉴입니다.', 'auth/login');
//            }
//        }
    }

    protected function login_user_info() {

        $login_user_info = $this->session->userdata('user_info');
        if (!empty($login_user_info)) {

            $result = $this->erl_accounts->get_one($login_user_info->seq);

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
            'login_info' => $this->login_user_info(),
            'return_url' => urldecode($this->input->get('return_url')),
            'tab' => $this->uri->segment(1),
            'seg' => $this->uri->segment(2),
            'arg' => $this->uri->segment(3),
        ];


        // Twig 전역 함수
        $functions = [
            'active_menu'       => function() { return $this->active_menu; },
            'active_submenu'    => function() { return $this->active_submenu; },
            'is_xhr'            => function() { return $this->input->is_ajax_request(); },
            'flash_confirm'     => function() { return $this->flash_confirm; },
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
        $user_info = $this->login_user_info();
        $account_seq = $user_info->seq;
        $sms_info = $this->sms_services->get_one("account_seq = {$account_seq}");
        $is_registered = ($sms_info->confirm_state == 1 or $sms_info->reconfirm_state == 1) ? TRUE : FALSE;
        return $is_registered;
    }
}
