<?php

session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';

$secret = json_decode(file_get_contents('php://input'), true)['secret'];

if(!isset($_SESSION['qr']['id']))
{
    echo json_encode(['error' => 'Din session har gått ut. Vänligen logga in igen.']);
    die();
}

$sql = 'SELECT alive FROM qr_players WHERE qr_users_id = ?';
$alive = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetchColumn();

if($alive != '1')
{
    echo json_encode(['error' => 'Du är tyvärr död och kan inte mörda någon.']);
    die();
}

$sql = '
SELECT qr_events.id, qr_players.alive, (qr_players.target = (SELECT qr_users_id FROM qr_players WHERE secret = ?)) as killed 
FROM qr_events RIGHT JOIN qr_players on qr_events.id = qr_players.qr_events_id 
WHERE qr_players.qr_users_id = ? AND CURRENT_DATE < end_date AND CURRENT_DATE > start_date
';
$info =  DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id']])->fetch();

if($info['alive'] === '0')
{
    echo json_encode(['error' => 'Denna person är redan död. Ta det lungt.']);
    die();
}

if($info['killed'] === '1')
{

    $sql = 'UPDATE qr_players SET alive = 0 WHERE secret = ?';
    DB::prepare($sql)->execute([$secret]);

    $sql = 'INSERT INTO qr_kills (target, killer, qr_events_id) VALUES ((SELECT qr_users_id FROM qr_players WHERE secret = ?), ?, ?)';
    DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id'], $info['id']]);

    $sql = "
    SELECT qr_users_id
    FROM qr_players
    WHERE target IS NULL AND qr_events_id = ?
    ORDER BY created_date ASC LIMIT 1
    ";
    $id = DB::prepare($sql)->execute([$info['id']])->fetchColumn();
    
    if($id)
    {
        $sql = '
        UPDATE qr_players as killer
        JOIN qr_players AS victim ON victim.secret = ?
        JOIN qr_players AS new_player ON new_player.qr_users_id = ?
        SET new_player.target = victim.target, killer.target = new_player.qr_users_id
        WHERE killer.qr_users_id = ? AND killer.qr_events_id = ?
        ';
        DB::prepare($sql)->execute([$secret, $id, $_SESSION['qr']['id'], $info['id']]);
    }
    else
    {
        $sql = '
        UPDATE qr_players as killer
        JOIN (SELECT target FROM qr_players WHERE secret = ?) as victim
        SET killer.target = victim.target 
        WHERE qr_users_id = ? AND qr_events_id = ?
        ';
        DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id'], $info['id']]);
    }

    echo json_encode(['code' => 3]); # SUCCESS
}
else
{
    echo json_encode(['error' => 'Koden du angav var inte korrekt']);
}