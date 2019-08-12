<?php

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';

session_start();

if(!isset($_SESSION['qr']['id'])) 
{
    echo json_encode(['alive' => "-1"]);
    die();
}

$sql = 'SELECT alive FROM qr_players WHERE qr_users_id = ?';
$alive = DB::prepare($sql)->execute([$_SESSION['qr']['id']])->fetchColumn();

echo json_encode(['alive' => $alive]);