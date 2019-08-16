<?php
session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/twig.php';
require_once 'priv/pdo.php';

if(!isset($_SESSION['qr']['id']))
{
    header('Location: index.php');
    die();
}

$sql = "
SELECT id, name, start_date, end_date, DATEDIFF(end_date, NOW()) AS days_left,
CASE
    WHEN NOW() < start_date THEN 1 -- The event hasen't started yet
    WHEN NOW() > end_date THEN 2  -- The event has ended
    WHEN NOW() < end_date AND NOW() > start_date THEN 3 -- The event is ongoing
END AS status
FROM qr_events AS event
WHERE display_date > NOW() ORDER BY start_date DESC LIMIT 1
";
$event = DB::prepare($sql)->execute()->fetch();
$model['event'] = $event;

if(!$event)
{
    echo $twig->render('noevents.html');
    die();
}

$sql = '
SELECT player.secret, player.target, player.feedback_given, player.alive, target_user.name AS target_name, target_user.class AS target_class
FROM qr_players AS player 
LEFT OUTER JOIN qr_users AS target_user ON player.target = target_user.id
WHERE player.qr_users_id = ? AND player.qr_events_id = ?
';
$player = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetch();
$model['player'] = $player;

if(!$player)
{
    if($event['days_left'] < 3 && $event['status'] == 3)
    {
        echo $twig->render('noevents.html', $model);
    }
    else
    {
        echo $twig->render('register.html', $model);
    }
    die();
}

if($event['status'] == 1) 
{
    echo $twig->render('countdown.html', $model);
    die();    
}

if($event['status'] == 2)
{
    echo $twig->render('eventover.html', $model);
    die();
}

if($player['alive'] == 0)
{
    echo $twig->render('dead.html', $model);
    die();
}

if($_SESSION['qr']['id'] == $player['target'])
{
    echo $twig->render('win.html', $model);
    die();
}

$sql = '
SELECT victim.name, victim.class, kills.created_date
FROM qr_kills AS kills
RIGHT JOIN qr_users AS victim ON kills.target = victim.id
WHERE kills.killer = ? AND kills.qr_events_id = ?
';
$model['victims'] = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetchAll();

echo $twig->render('game.html', $model);