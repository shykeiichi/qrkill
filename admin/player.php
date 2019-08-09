<?php

session_start();

require_once '../priv/twig.php';
require_once '../priv/pdo.php';
require_once '../priv/errorhandler.php';

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: index.php');
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'GET')
{
    if(!isset($_GET['userId']) || !isset($_GET['eventId']))
    {
        die('Du måste ange event och spelar ID.');
    }
    
    $sql = '
    SELECT COUNT(qr_kills.id), qr_players.*, qr_users.* 
    FROM qr_players 
    JOIN qr_users LEFT OUTER JOIN qr_kills ON qr_kills.killer = qr_users.id = qr_players.qr_users_id AND qr_kills.qr_events_id = qr_players.qr_events_id 
    WHERE qr_players.qr_users_id = ? AND qr_players.qr_events_id = ?
    ';
    $model['blob'] = DB::prepare($sql)->execute([$_GET['userId'], $_GET['eventId']])->fetch();

    echo $twig->render('admin/blob.html', $model);
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if($_POST['action'] === 'Skapa')
    {
        $sql = 'INSERT INTO qr_events (name, start_date, end_date, display_date) VALUES (?, ?, ?, ?)';
        DB::prepare($sql)->execute([$_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['display_date']]);
        header('Location: event.php?id=' . DB::lastInsertId());
        die();
    }

    if($_POST['action'] == 'Radera')
    {
        $sql = 'DELETE FROM qr_players WHERE qr_events_id = ?';
        DB::prepare($sql)->execute([$_POST['id']]);

        $sql = 'DELETE FROM qr_events WHERE id = ?';
        DB::prepare($sql)->execute([$_POST['id']]);
        
        header('Location: index.php');
        die();
    }

    if($_POST['action'] == 'Uppdatera')
    {
        $sql = 'UPDATE qr_events SET name = ?, start_date = ?, end_date = ?, display_date = ?';
        DB::prepare($sql)->execute([$_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['display_date']]);
        header('Location: event.php?id=' . $_POST['id']);
        die();
    }

    if($_POST['action'] === 'Lägg till användare')
    {
        if($_POST['whitelist'] !== '')
        {
            $classes = explode(',', $_POST['whitelist']);
            $in = str_repeat('?,', count($classes) - 1) . '?';
            $sql = "SELECT id FROM qr_users WHERE class IN ($in)";
            $users = DB::prepare($sql)->execute($classes)->fetchAll();
        }
        else if($_POST['whitelistStudents'] !== '')
        {
            $usernames = explode(',', $_POST['whitelistStudents']);
            $in = str_repeat('?,', count($classes) - 1) . '?';
            $sql = "SELECT id FROM qr_users WHERE username IN ($in)";
            $users = DB::prepare($sql)->execute($usernames)->fetchAll();
        }
        else
        {
            $sql = "SELECT id FROM qr_users";
            $users = DB::prepare($sql)->execute()->fetchAll();
        }

        foreach($users as $key => $user)
        {
            $secret = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVW'), 0, 5);
            $sql = 'INSERT INTO qr_players (qr_events_id, qr_users_id, secret) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE secret = CONCAT(secret, \'X\')';
            DB::prepare($sql)->execute([$_POST['id'], $user['id'], $secret]);
        }
        
        header('Location: event.php?id=' . $_POST['id']);
        die();
    }

    if($_POST['action'] === 'Ta bort')
    {
        $sql = 'DELETE FROM qr_players WHERE qr_events_id = ? AND qr_users_id = ?';
        DB::prepare($sql)->execute([$_POST['eventId'], $_POST['userId']]);
        header('Location: event.php?id=' . $_POST['eventId']);
        die();
    }
    
    if($_POST['action'] === 'Tilldela mål')
    {
        $sql = 'SELECT * FROM qr_players WHERE qr_events_id = ?';
        $users = DB::prepare($sql)->execute([$_POST['id']])->fetchAll();
        shuffle($users);
        
        $sql = 'UPDATE qr_players SET target = ? WHERE qr_users_id = ? AND qr_events_id = ?';
        foreach($users as $key => $user)
        {
            $id = isset($users[$key + 1]) ? $users[$key + 1]['qr_users_id'] : $users[0]['qr_users_id'];
            DB::prepare($sql)->execute([$id, $user['qr_users_id'], $_POST['id']]);
        }
        header('Location: event.php?id=' . $_POST['id']);
        die();
    }
}