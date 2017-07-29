<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('web_base.php');

/**
 * 랜딩 관련 클래스
 *
 */
class Main extends Web_base
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
//        $this->load->model('projects');
    }

    /**
     * index
     */
    public function index()
    {
       echo 'index pages';
    }
}
