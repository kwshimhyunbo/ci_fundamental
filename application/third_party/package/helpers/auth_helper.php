<?php  

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 인증 토큰 값 체크
 *
 * @param bool $do_throw    익셉션 사용 여부
 * @return array|null       인증 정보
 */
function check_auth_token($do_throw = true)
{	
	if (!isset($_REQUEST['auth_token']) || is_null($auth_token = $_REQUEST['auth_token'])) {
		if ($do_throw) throw new APIDeviceKeyInvalidException;
		return null;
	}

	$ci =& get_instance();
	$ci->load->model('auth_tokens');

	$token_info = $ci->auth_tokens->get_info_by_auth_token($auth_token);
	if (is_null($token_info)) {
		if ($do_throw) throw new APIDeviceKeyInvalidException;
		return null;
	}

	$result = array(
		'token_seq' => $token_info->seq,
		'account_seq' => $token_info->account_seq,
		'device_seq' => $token_info->device_seq,
		'is_signed' => ($token_info->account_seq > 0)
	);

	return $result;
}

/**
 * 로그인이 필요한지 여부를 체크
 *
 * @param bool $do_throw    익셉션 사용 여부
 * @return array|null       계정 정보
 */
function check_sign_in($do_throw = true)
{
	$auth_info = check_auth_token($do_throw);
	if (is_null($auth_info) || !$auth_info['is_signed']) {
		if ($do_throw) throw new APIException('로그인이 필요합니다.', -5);
		return null;
	}

	$ci =& get_instance();
	$ci->load->model('accounts');	
//	$ci->accounts->update_last_logged_at($auth_info['account_seq']);
	
	return $auth_info;
}

function has_admin_role_type($account_seq, $role_group, $role_type = 1)
{
	$ci =& get_instance();
	$ci->load->model('admin_roles');
	$result = $ci->admin_roles->has_role($account_seq, $role_group,$role_type);

	return $result;
}
