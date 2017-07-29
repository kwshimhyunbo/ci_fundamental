<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('Admin_base.php');

/**
 * 랜딩 관련 클래스
 *
 */
class Main extends AdminBase_Controller
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->check_web_sign_in($this->allowMethods, $this->allowClasses);
    }

    /**
     * index
     */
    public function index()
    {

        $this->twig->display('admin/main/projects',[
            'project_lists' => ''
        ]);
    }
}
