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
SELECT 
    id, 
    name, 
    start_date, 
    end_date, 
    DATEDIFF(end_date, NOW()) AS days_left, 
    (COUNT(qr_players.target) != 0) AS targets_assigned,
    (COUNT(has_won_check.target) != 0) AS someone_has_won,
CASE
    WHEN NOW() < start_date THEN 1 -- The event hasen't started yet
    WHEN NOW() > end_date THEN 2  -- The event has ended
    WHEN NOW() < end_date AND NOW() > start_date THEN 3 -- The event is ongoing
END AS status
FROM qr_events AS event
LEFT JOIN qr_players ON event.id = qr_players.qr_events_id AND qr_players.target IS NOT NULL
LEFT OUTER JOIN qr_players AS has_won_check ON qr_players.target = qr_players.qr_users_id
WHERE display_date > NOW() ORDER BY start_date DESC LIMIT 1
";
$event = DB::prepare($sql)->execute()->fetch();
$model['event'] = $event;

if(!isset($event['id']))
{
    echo $twig->render('noevents.html');
    die();
}

if($event['status'] == 3 && $event['targets_assigned'] == 0)
{
    $sql = 'SELECT * FROM qr_players WHERE qr_events_id = ?';
    $users = DB::prepare($sql)->execute([$event['id']])->fetchAll();
    shuffle($users);
    
    $sql = 'UPDATE qr_players SET target = ? WHERE qr_users_id = ? AND qr_events_id = ?';
    foreach($users as $key => $user)
    {
        $id = isset($users[$key + 1]) ? $users[$key + 1]['qr_users_id'] : $users[0]['qr_users_id'];
        DB::prepare($sql)->execute([$id, $user['qr_users_id'], $event['id']]);
    }
}

$sql = '
SELECT 
    player.secret, 
    player.target, 
    player.feedback_given, 
    player.alive, 
    target_user.name AS target_name, 
    target_user.class AS target_class
FROM qr_players AS player 
LEFT OUTER JOIN qr_users AS target_user ON player.target = target_user.id
WHERE player.qr_users_id = ? AND player.qr_events_id = ?
';
$player = DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']])->fetch();
$model['player'] = $player;

if(!$player)
{
    if($event['days_left'] < 4 && $event['status'] == 3 || $event['someone_has_won'] == 1)
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