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


if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']))
{ 
    $sql = 'SELECT * FROM qr_users WHERE id = ?';
    $model['blob'] = DB::prepare($sql)->texecute([$_GET['id']])->fetch(); 
    echo $twig->render('admin/blob.html', $model);
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'GET')
{ 
    $sql = 'SELECT * FROM qr_users';
    $model['users'] = DB::prepare($sql)->texecute()->fetchAll(); 
    echo $twig->render('admin/users.html', $model);
    die();
}


if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if($_POST['action'] === 'Skapa')
    {
        $sql = 'INSERT INTO qr_users (username, name, class, is_admin) VALUES (?, ?, ?, ?)';
        DB::prepare($sql)->texecute([$_POST['username'], $_POST['name'], $_POST['class'], $_POST['is_admin']]);
        header('Location: users.php?id='.DB::lastInsertId());
        die();
    }
}

