<?php

session_start();

require_once 'priv/pdo.php';

$secret = json_decode(file_get_contents('php://input'), true)['secret'];

if(!isset($_SESSION['qr']['id']))
{
    echo json_encode(['error' => 'Din session har gått ut. Vänligen logga in igen.']);
    die();
}

$sql = 'SELECT (qr_players.target = (SELECT qr_users_id FROM qr_players WHERE secret = ?)) as x FROM qr_events RIGHT JOIN qr_players on qr_events.id = qr_players.qr_events_id WHERE qr_players.qr_users_id = ? AND CURRENT_DATE < end_date AND CURRENT_DATE > start_date';
$killed =  DB::prepare($sql)->execute([$secret, $_SESSION['qr']['id']])->fetchColumn();

if($killed === '1')
{
    echo json_encode(['message' => 'Du dödade din fiende! Ger dig en ny.']);
}
else
{
    echo json_encode(['message' => 'Koden du angav var inte korrekt']);
}