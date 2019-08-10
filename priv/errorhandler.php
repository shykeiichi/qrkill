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

function discordErrorHandler($errno, $errstr, $errfile, $errline) {
	sendMessage("Error: \r\n$errno on line $errline in $errfile\r\n$errstr");
}

function discordExceptionHandler($exception) {
	while($exception)
	{
		sendMessage("Exception: \n\r " . $exception->getMessage() . "\r\n" . json_encode($exception->getTrace(), JSON_PRETTY_PRINT) ."\n\rIn " . $exception->getFile() . " on line " . $exception->getLine());
		$exception = $exception->getPrevious();
	}
}

if(isset($webhook))
{
	set_error_handler("discordErrorHandler");
	set_exception_handler("discordExceptionHandler");
	#error_reporting(0);
}