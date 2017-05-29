<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Validator
{
	protected static $defined_rules = array(
		'email' => array(
			'label' => '이메일',
			'rule' => 'trim|required|valid_email'
		),
		'nickname' => array(
			'label' => '닉네임',
			'rule' => 'trim|required|min_length[2]|max_length[20]'
		),
		'original_password' => array(
			'label' => '현재 비밀번호',
			'rule' => 'required|min_length[4]'
		),
		'password' => array(
			'label' => '비밀번호',
			'rule' => 'required|min_length[4]'
		),
		'birthday' => array(
			'label' => '생년월일',
			'rule' => 'trim|required|regex_match[([0-1][0,9][0-9]{2}\-[0-1][0-9]\-[0-1][0-9])]'
		),
		'identity_seq' => array(
			'label' => '아이덴티티',
			'rule' => 'required|greater_than[0]|less_than[5]'
		),
		'use_push' => array(
			'label' => '푸시 상태',
			'rule' => 'required|less_than[2]|is_natural'
		),
		'seq' => array(
			'label' => 'seq',
			'rule' => 'required|is_natural'
		),
		'query' => array(
			'label' => '검색어',
			'rule' => 'trim|required|min_length[2]'
		),
		'title' => array(
			'label' => '제목',
			'rule' => 'required'
		),
		'contents' => array(
			'label' => '내용',
			'rule' => 'required'
		)
	);

	protected static $defined_messages = array(
		'required' => '%s 항목을 입력해주세요.',
		'valid_email' => '유효한 이메일을 입력해 주세요.',
		'valid_date' => '유효한 날짜형식을 입력해 주세요.',
		'min_length' => '%s 항목을 %s자 이상 입력해 주세요.',
		'greater_than' => '%s 항목의 값은 %s 이상이어야 합니다.',
		'less_than' => '%s 항목의 값은 %s 미만이어야 합니다.',
		'is_natural' => '%s 항목의 값이 올바르지 않습니다.',
		'max_words' => '%s 단어 수는 %s개 이하여야 합니다.',
		'min_word_length' => '%s 단어는 %s자 이상 입력해야 합니다.'
	);

	protected $rules = array();

	function set_rules($field, $label = null, $rule = null)
	{
		$this->rules[] = array(
			'field' => $field,
			'label' => $label,
			'rule' => $rule
		);

		return $this;
	}

	function set_rules_if_exist($field, $label = null, $rule = null)
	{
		$ci =& get_instance();
		$input = $ci->input->post();

		if (strlen($input[$field]) != 0) {
			$this->set_rules($field, $label, $rule);
		}
	}

	function run($type = 'api')
	{
		if (empty($this->rules)) {
			return;
		}

		$ci =& get_instance();
		$ci->load->helper('form');
		$ci->load->library('form_validation');

		$validation = $ci->form_validation;
		$validation->set_error_delimiters('', '');
		$validation->set_message(self::$defined_messages);

		foreach ($this->rules as $r) {
			$field = $r['field'];
			$label = $r['label'];
			$rule = $r['rule'];

			if(isset(self::$defined_rules[$field])){
				$defined_rule = self::$defined_rules[$field];
				if(!is_null($defined_rule)){
					if (is_null($label)) $label = $defined_rule['label'];
					if (is_null($rule)) $rule = $defined_rule['rule'];
				}
			}
			$validation->set_rules($field, $label, $rule);
		}

		if (!$validation->run()) {
			$errors = explode("\n", validation_errors());
			switch ($type) {
                case 'api':
                    throw new APIException($errors[0], -2);
                    break;
                default:
                    return $errors;
            }
		}

		$this->rules = array();
	}

}

