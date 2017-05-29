<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('admin_base.php');

/**
 * 랜딩 관련 클래스
 *
 */
class Main extends Admin_base
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
        $this->_init_twig();
    }

    /**
     * index
     */
    public function index()
    {

        $this->twig->display('admin/auth/login', [

        ]);

    }
}
