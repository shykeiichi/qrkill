<?php
session_start();

require_once 'priv/twig.php';
require_once 'priv/pdo.php';

if(!isset($_SESSION['qr']['id']))
{
    header('Location: login.php');
    die();
}

$sql = 'SELECT qr_players.alive, qr_players.secret, qr_players.target, qr_events.*, (CURRENT_DATE > qr_events.start_date) AS ongoing FROM qr_events RIGHT JOIN qr_players on qr_events.id = qr_players.qr_events_id WHERE qr_players.qr_users_id = ? AND CURRENT_DATE < end_date ORDER BY ongoing = 1 DESC, start_date ASC LIMIT 1';
$event =  DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetch();

if(!$event)
{
    echo $twig->render('noevents.html');
    die();
}

if($event['alive'] == 0)
{
    echo $twig->render('dead.html');
    die();
}

$model['event'] = $event;
if($event['ongoing'] != 1)
{
    echo $twig->render('countdown.html', $model);
    die();
}

$sql = 'SELECT u.name, u.class, p.alive FROM qr_players AS p RIGHT JOIN qr_users AS u ON p.qr_users_id = u.id WHERE p.qr_users_id = ? AND p.qr_events_id = ?';
$model['target'] = DB::prepare($sql)->execute([$event['target'], $event['id']])->fetch();

echo $twig->render('game.html', $model);