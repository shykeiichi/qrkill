<?php

session_start();

require_once 'priv/pdo.php';

$secret = json_decode(file_get_contents('php://input'), true)['secret'];

if(!isset($_SESSION['qr']['id']))
{
    echo json_encode(['error' => 'Din session har gått ut. Vänligen logga in igen.']);
    die();
}

$sql = '
SELECT qr_players.alive, (qr_players.target = (SELECT qr_users_id FROM qr_players WHERE secret = ?)) as killed 
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

    $sql = 'INSERT INTO qr_kills (target, killer) VALUES ((SELECT qr_users_id FROM qr_players WHERE secret = ?), ?)';
    DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id']]);

    $sql = 'UPDATE qr_players as q1 JOIN (SELECT target FROM qr_players WHERE secret = ?) as q2 SET q1.target = q2.target WHERE qr_users_id = ?';
    DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id']]);

    echo json_encode(['code' => 3]); # SUCCESS
}
else
{
    echo json_encode(['error' => 'Koden du angav var inte korrekt']);
}