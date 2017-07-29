<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once ('web/Web_base.php');
/**
 * 랜딩 관련 클래스
 *
 */
class My404 extends Web_base
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->output->set_status_header('404');

        // Make sure you actually have some view file named 404.php
        $this->twig->display('errors/404');
    }
}
