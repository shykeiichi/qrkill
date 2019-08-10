<?php
session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';

if(!isset($_SESSION['qr']['id']))
{
    header('Location: login.php');
    die();
}

header('Location: game.php');

$sql = "
SELECT qr_events_id
FROM qr_players 
WHERE qr_events_id = (SELECT id FROM qr_events WHERE end_date > NOW() ORDER BY start_date DESC LIMIT 1) 
AND qr_users_id = ?
";
$id = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetch();
if($id)
{
    die();
}

$secret = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVW'), 0, 5);
$sql = '
INSERT INTO qr_players 
(qr_events_id, qr_users_id, secret) VALUES
((SELECT id FROM qr_events WHERE end_date > NOW() ORDER BY start_date DESC LIMIT 1), ?, ?) 
ON DUPLICATE KEY UPDATE secret = CONCAT(secret, \'X\')
';
DB::prepare($sql)->execute([$_SESSION['qr']['id'], $secret]);
