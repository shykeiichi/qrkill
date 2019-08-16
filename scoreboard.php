<?php

session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';
require_once 'priv/twig.php';

$sql = '
SELECT *
FROM qr_events
WHERE display_date > CURRENT_DATE LIMIT 1
';
$event =  DB::prepare($sql)->execute()->fetch();
$model['event'] = $event;

$sql = '
SELECT 
	SUM(1) AS score, alive, qr_users.name, qr_users.class
FROM qr_kills 
JOIN qr_users 
JOIN qr_players
	ON qr_kills.qr_events_id = qr_players.qr_events_id 
    	AND qr_kills.killer = qr_users.id = qr_players.qr_users_id
WHERE qr_players.qr_events_id = ?
GROUP BY qr_kills.killer
';
$users = DB::prepare($sql)->execute([$event['id']])->fetchAll();
$model['users'] = $users;

if($users[0]['name'] === NULL)
{
    unset($model['users'][0]);
}

echo $twig->render('scoreboard.html', $model);
