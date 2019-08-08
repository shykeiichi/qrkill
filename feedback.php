<?php

session_start();

require_once 'priv/pdo.php';
require_once 'priv/errorhandler.php';

header('Location: index.php');

if($_SERVER['REQUEST_METHOD'] != 'POST')
{
    die();
}

if(!isset($_POST['rate']) || !isset($_POST['feedback']) || !intval(isset($_POST['rate'])) > 4 || intval(isset($_POST['rate'])) < 1 || strlen(isset($_POST['feedback'])) > 510)
{
    die('Ogiltigt svar');
}


$sql = "
SELECT qr_events.id, qr_players.feedback_given
FROM qr_events RIGHT JOIN qr_players ON qr_events.id = qr_players.qr_events_id 
WHERE qr_players.qr_users_id = ? AND CURRENT_DATE < display_date 
";
$event = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetch();

if($event['feedback_given'] == '1')
{
    die();
}

$sql = 'INSERT INTO qr_feedback (rate, feedback, qr_events_id) VALUES (?, ?, ?)';
DB::prepare($sql)->execute([$_POST['rate'], $_POST['feedback'], $event['id']]);

$sql = 'UPDATE qr_players SET feedback_given = 1 WHERE qr_users_id = ? AND qr_events_id = ?';
DB::prepare($sql)->execute([$_SESSION['qr']['id'], $event['id']]);
