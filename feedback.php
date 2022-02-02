<?php

session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';

header('Location: index.php');

if($_SERVER['REQUEST_METHOD'] != 'POST')
{
    die();
}

if(
   !isset($_POST['rate']) 
|| !isset($_POST['feedback']) 
|| intval($_POST['rate']) > 4 
|| intval($_POST['rate']) < 1 
|| strlen($_POST['feedback']) > 310)
{
    die('Ogiltigt svar');
}

$sql = "
SELECT event.id, player.feedback_given
FROM qr_events AS event 
RIGHT JOIN qr_players AS player ON event.id = player.qr_events_id 
WHERE player.qr_users_id = ? AND NOW() < display_date AND NOW() > start_date
";
$event = DB::prepare($sql)->texecute([$_SESSION['qr']['id']])->fetch();

if($event['feedback_given'] == 1)
{
    die();
}

$sql = 'INSERT INTO qr_feedback (rate, feedback, qr_events_id) VALUES (?, ?, ?)';
DB::prepare($sql)->texecute([$_POST['rate'], $_POST['feedback'], $event['id']]);

$sql = 'UPDATE qr_players SET feedback_given = 1 WHERE qr_users_id = ? AND qr_events_id = ?';
DB::prepare($sql)->texecute([$_SESSION['qr']['id'], $event['id']]);
