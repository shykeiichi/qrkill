<?php

function myErrorHandler($errno, $errstr, $errfile, $errline) {
    
	$url = 'https://discordapp.com/api/webhooks/605485741644054613/08JZ2VrLr7NlBr6qBSgYHvOfCiJIUhYFZjBrfa9CNEC1NlSM01pSZi--zp0qij8aPX6h';
	$data = array('content' => "Error: $errno on line $errline in $errfile\r\n$errstr");

	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data)
		)
			);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
}

set_error_handler("myErrorHandler");
