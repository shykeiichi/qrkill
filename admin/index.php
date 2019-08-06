<?php

session_start();

require_once '../priv/twig.php';
require_once '../priv/pdo.php';

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: index.php');
    die();
}

$sql = 'SELECT * FROM qr_events';
$model['events'] = DB::prepare($sql)->execute()->fetchAll();

echo $twig->render('admin/index.html', $model);