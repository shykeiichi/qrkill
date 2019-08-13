<?php
session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/twig.php';

if(isset($_SESSION['qr']['id']))
{
    header('Location: game.php');
    die();
}

echo $twig->render('login.html');