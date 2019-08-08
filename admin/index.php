<?php

session_start();

require_once '../priv/twig.php';
require_once '../priv/pdo.php';

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: ../index.php');
    die();
}

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $conf['ga_track_id'] = $_POST['ga_track_id'];
    file_put_contents(__DIR__ . "/../priv/config.json", json_encode($conf));
    header('Location: index.php');
    die();
}

$model['ga_track_id'] = json_decode(file_get_contents(__DIR__ . "/../priv/config.json"), true)['ga_track_id'];
echo $twig->render('admin/index.html', $model);