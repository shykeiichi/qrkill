<?php
session_start();
require_once 'priv/twig.php';
require_once 'priv/pdo.php';

if(isset($_SESSION['qr']['id']))
{
    header('Location: game.php');
    die();
}

echo $twig->render('index.html');