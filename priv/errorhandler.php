<?php

$webhook = json_decode(file_get_contents(__DIR__ . '/config.json'), true)['webhook'];

function sendMessage($message)
{
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query(array('content' => $message))
		)
	);
	$context  = stream_context_create($options);
	global $webhook;
	$result = file_get_contents($webhook, false, $context);
}

if(isset($webhook))
{
	set_error_handler(function ($exception) {
		sendMessage("Exception: \n\r $exception");
	});
	set_exception_handler(function ($errno, $errstr, $errfile, $errline) {
		sendMessage("Error: \r\n$errno on line $errline in $errfile\r\n$errstr");
	});
	error_reporting(0);
}