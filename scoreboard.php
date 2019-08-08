<?php

session_start();

require_once 'priv/pdo.php';
require_once 'priv/twig.php';
require_once 'priv/errorhandler.php';

$sql = '
SELECT *
FROM qr_events
WHERE start_date < CURRENT_DATE AND display_date > CURRENT_DATE LIMIT 1';
$event =  DB::prepare($sql)->execute()->fetch();
$model['event'] = $event;

$sql = '
SELECT COUNT(qr_kills.id) AS score, alive, qr_users.name, qr_users.class 
FROM qr_players JOIN qr_kills JOIN qr_users ON qr_kills.killer = qr_users.id = qr_players.qr_users_id 
WHERE qr_players.qr_events_id = ?
';
$users = DB::prepare($sql)->execute([$event['id']])->fetchAll();
$model['users'] = $users;

if($users[0]['name'] === NULL)
{
    unset($model['users'][0]);
}

echo $twig->render('scoreboard.html', $model);
