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
SELECT id, name, start_date, end_date, (end_date - NOW()) AS time_left,
CASE
    WHEN NOW() < start_date THEN 1
    WHEN NOW() > end_date THEN 2 
    WHEN NOW() > start_date AND NOW() < end_date THEN 3
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

$sql = "
SELECT qr_users_id, feedback_given
FROM qr_players as player
WHERE qr_events_id = ? AND qr_users_id = ?
";
$player = DB::prepare($sql)->execute([$event['id'], $_SESSION['qr']['id']])->fetch();
$model['feedback_given'] = $player['feedback_given'];

if(!$player)
{
    if($event['time_left'] > 1000000)
    {
        echo $twig->render('register.html', $model);
    }
    else
    {
        echo $twig->render('noevents.html', $model);
    }
    die();
}

if($event['status'] == 1 && $player) 
{
    echo $twig->render('countdown.html', $model);
    die();    
}

if($event['status'] == 2)
{
    echo $twig->render('eventover.html', $model);
    die();
}

$sql = '
SELECT player.qr_users_id, player.feedback_given, player.secret, player.alive, player.target, target_user.name AS target_name, target_user.class AS target_class, COUNT(kills.id) AS score
FROM qr_players AS player 
LEFT OUTER JOIN qr_users AS target_user ON player.target = target_user.id
LEFT OUTER JOIN qr_kills AS kills ON player.qr_users_id = kills.killer AND player.qr_events_id = kills.qr_events_id
WHERE player.qr_users_id = ? AND player.qr_events_id = ?
';
$player = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetch();
$model['player'] = $player;

if($player['alive'] == 0)
{
    echo $twig->render('dead.html');
    die();
}

if($player['qr_users_id'] == $player['target'])
{
    echo $twig->render('win.html', $model);
    die();
}

$sql = '
SELECT victim.name, victim.class, qr_kills.created_date
FROM qr_kills 
RIGHT JOIN qr_users AS victim ON qr_kills.target = victim.id
WHERE qr_kills.killer = ? AND qr_kills.qr_events_id = ?
';
$model['victims'] = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetchAll();

echo $twig->render('game.html', $model);