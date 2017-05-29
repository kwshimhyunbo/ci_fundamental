<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * JSON 데이터 가독성 향상
 * @param $json json 데이터
 * @return string
 */
function pretty_json($json)
{
	$result      = '';
	$pos         = 0;
	$strLen      = strlen($json);
	$indentStr   = '  ';
	$newLine     = "\n";
	$prevChar    = '';
	$outOfQuotes = true;


	for ($i=0; $i<=$strLen; ++$i)
	{

		// Grab the next character in the string.
		$char = substr($json, $i, 1);

		// Are we inside a quoted string?
		if ($char == '"' && $prevChar != '\\')
		{
			$outOfQuotes = !$outOfQuotes;

			// If this character is the end of an element,
			// output a new line and indent the next line.
		} else if (($char == '}' || $char == ']') && $outOfQuotes) {
			$result .= $newLine;
			--$pos;

			for ($j=0; $j<$pos; ++$j)
			{
				$result .= $indentStr;
			}
		}

		// Add the character to the result string.
		$result .= $char;

		// If the last character was the beginning of an element,
		// output a new line and indent the next line.
		if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes)
		{
			$result .= $newLine;
			if ($char == '{' || $char == '[')
			{
				++$pos;
			}

			for ($j = 0; $j < $pos; ++$j)
			{
				$result .= $indentStr;
			}
		}

		$prevChar = $char;

	}

	return $result;
}


/**
 * json 결과 반환
 *
 * @param array  $data 반환 데이터
 * @param string  $retcode 반환 코드
 * @param string  $message 메시지
 * @return array json 데이터
 */
function get_result_set($data = null, $retcode = '1', $message = '')
{
    $trace = debug_backtrace();
    $function_name = $trace[1]["function"];

    if($retcode == "1") {
        $result = array('return_code' => $retcode+0, 'return_message' => $message . '', 'function_name' => $function_name, 'result' => $data);
        return $result;
    } else {
        $result = array('return_code' => $retcode+0, 'return_message' => $message.'', 'function_name' => $function_name);
        return $result;
    }
}

/**
 * json 결과 출력
 * @param $array
 */
function json_response($array)
{
    header("Content-Type: application/json; charset=utf-8");
    if (!isset($_REQUEST['callback'])) {
        echo pretty_json(json_encode($array));
    } else {
        echo $_REQUEST['callback'] . '(' . pretty_json(json_encode($array)) . ')';
    }
    exit;
}
