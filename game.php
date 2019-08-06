<?php
session_start();

require_once 'priv/twig.php';
require_once 'priv/pdo.php';

$sql = 'SELECT qr_players.secret, qr_events.*, (CURRENT_DATE > qr_events.start_date) AS ongoing FROM qr_events RIGHT JOIN qr_players on qr_events.id = qr_players.qr_events_id WHERE qr_players.qr_users_id = ? AND CURRENT_DATE < end_date ORDER BY ongoing = 1 DESC, start_date ASC LIMIT 1';
$event =  DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetch();

if(!$event)
{
    echo $twig->render('noevents.html');
}

$model['event'] = $event;

if($event['ongoing'] != 1)
{
    echo $twig->render('countdown.html', $model);
    die(); 
}

echo $twig->render('game.html', $model);