<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('model_base.php');

/**
 */
class Accounts extends Model_base
{
	/**
	 * @var string
	 */
	protected $table       = 'ACCOUNTS';
	function __construct()
	{
		parent::__construct();
	}
}
