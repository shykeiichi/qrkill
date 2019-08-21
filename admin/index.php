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
    $conf['webhook'] = $_POST['webhook'];
    file_put_contents(__DIR__ . "/../priv/config.json", json_encode($conf));
    header('Location: index.php');
    die();
}

$model['database'] = DB::query('select database()')->fetchColumn();
$model['config'] = json_decode(file_get_contents(__DIR__ . "/../priv/config.json"), true);
echo $twig->render('admin/index.html', $model);