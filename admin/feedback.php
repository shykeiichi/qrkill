<?php

session_start();

require_once '../priv/twig.php';
require_once '../priv/pdo.php';
require_once '../priv/errorhandler.php';

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: index.php');
    die();
}

if(isset($_GET['id']))
{
    $sql = 'SELECT feedback, rate FROM qr_feedback WHERE qr_events_id = ?';
    $feedback = DB::prepare($sql)->texecute([$_GET['id']])->fetchAll();
    $model['feedback'] = $feedback;
    $model['average'] = array_sum(array_column($feedback, 'rate')) / count($feedback);
    echo $twig->render('admin/feedback.html', $model);
    die();
}

$sql = 'SELECT id, name FROM qr_events';
$events = DB::prepare($sql)->texecute()->fetchAll();
echo $twig->render('admin/feedback.html', ['events' => $events]);
die();