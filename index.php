<?php
session_start();
require_once 'priv/twig.php';

echo $twig->render('index.html');
var_dump($_SESSION);