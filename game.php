<?php
session_start();

require_once 'priv/twig.php';
require_once 'priv/pdo.php';

if(!isset($_SESSION['qr']['id']))
{
    header('Location: login.php');
    die();
}

$sql = "
SELECT event.id, event.name, event.start_date, event.end_date, player.feedback_given,
CASE
    WHEN NOW() < event.start_date THEN 1 -- The event is starting and a countdown is shown
    WHEN NOW() < event.end_date AND NOW() > event.start_date THEN 2 -- The event is ongoing
    WHEN NOW() > event.end_date THEN 3 -- The event is over and up for display
END AS status
FROM qr_events AS event RIGHT JOIN qr_players AS player ON event.id = player.qr_events_id 
WHERE player.qr_users_id = ? AND CURRENT_DATE < display_date LIMIT 1
";
$event = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetch();
$model['event'] = $event;

if(!$event)
{
    echo $twig->render('noevents.html');
    die();
}

if($event['status'] == 1)
{
    echo $twig->render('countdown.html', $model);
    die();
}

if($event['status'] == 3)
{
    echo $twig->render('eventover.html', $model);
    die();
}

$sql = '
SELECT player.secret, player.alive, target_user.name AS target_name, target_user.class AS target_class, COUNT(kills.id) AS score
FROM qr_players AS player 
LEFT OUTER JOIN qr_users AS target_user ON player.target = target_user.id
LEFT OUTER JOIN qr_kills AS kills ON player.qr_users_id = kills.killer
WHERE player.qr_users_id = 1 AND player.qr_events_id = 13
';
$player = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetch();
$model['player'] = $player;

if($player['alive'] == 0)
{
    echo $twig->render('dead.html');
    die();
}

$sql = '
SELECT victim.name, victim.class, qr_kills.created_date
FROM qr_kills 
RIGHT JOIN qr_users AS victim ON qr_kills.target = victim.id
WHERE qr_kills.killer = ?
';
$model['victims'] = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetchAll();

echo $twig->render('game.html', $model);