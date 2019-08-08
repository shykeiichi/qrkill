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
    if(!isset($_GET['id']))
    {
        $sql = 'SELECT * FROM qr_events';
        $model['events'] = DB::prepare($sql)->execute()->fetchAll();
        echo $twig->render('admin/events.html', $model);
        die();
    }

    
    $sql = 'SELECT * FROM qr_events WHERE id = ?';
    $model['event'] = DB::prepare($sql)->execute([$_GET['id']])->fetch();


    if(empty($model['event']))
    {
        http_response_code(404);
        die('Kunde inte hitta eventet');
    }

    $sql = 'SELECT qr_users.*, qr_players.secret, qr_players.target, qr_players.alive FROM qr_players RIGHT JOIN qr_users ON qr_players.qr_users_id = qr_users.id WHERE qr_players.qr_events_id = ?';
    $users = DB::prepare($sql)->execute([$_GET['id']])->fetchAll();
    $model['users'] = $users;

    foreach($users as $user)
    {
        $model['userMap'][$user['id']] = $user['name'];
    }
    
    echo $twig->render('admin/event.html', $model);
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if($_POST['action'] === 'Skapa')
    {
        $sql = 'INSERT INTO qr_events (name, start_date, end_date, display_date) VALUES (?, ?, ?, ?)';
        DB::prepare($sql)->execute([$_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['display_date']]);
        header('Location: events.php?id=' . DB::lastInsertId());
        die();
    }

    if($_POST['action'] == 'Radera')
    {
        $sql = 'DELETE FROM qr_events WHERE id = ?';
        DB::prepare($sql)->execute([$_POST['id']]);
        
        header('Location: index.php');
        die();
    }

    if($_POST['action'] == 'Uppdatera')
    {
        $sql = 'UPDATE qr_events SET name = ?, start_date = ?, end_date = ?, display_date = ?';
        DB::prepare($sql)->execute([$_POST['name'], $_POST['start_date'], $_POST['end_date'], $_POST['display_date']]);
        header('Location: events.php?id=' . $_POST['id']);
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
            $in = str_repeat('?,', count($usernames) - 1) . '?';
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
        
        header('Location: events.php?id=' . $_POST['id']);
        die();
    }

    if($_POST['action'] === 'Ta bort')
    {
        $sql = '
        UPDATE qr_players AS del_user
        INNER JOIN qr_players AS target ON target.qr_users_id = del_user.target
        INNER JOIN qr_players AS hunter ON hunter.target = del_user.qr_users_id
        SET hunter.target = target.qr_users_id
        WHERE del_user.qr_users_id = ? AND del_user.qr_events_id = ?
        ';
        DB::prepare($sql)->execute([$_POST['userId'], $_POST['eventId']]);

        $sql = 'DELETE FROM qr_players WHERE qr_events_id = ? AND qr_users_id = ?';
        DB::prepare($sql)->execute([$_POST['eventId'], $_POST['userId']]);
        header('Location: events.php?id=' . $_POST['eventId']);
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
        header('Location: events.php?id=' . $_POST['id']);
        die();
    }
}