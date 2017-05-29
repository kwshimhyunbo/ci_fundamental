<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 랜딩 관련 클래스
 *
 */
class Main extends CI_Controller
{
    /**
     * Constructor
     *
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Home 리스트
     */
    public function index()
    {
        redirect('main');
    }
}
