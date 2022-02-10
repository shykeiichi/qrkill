<?php
if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: /qrkill/index.php');
    die();
}
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username']))
{ 
    $sql = '
    SELECT 
        player.secret 
    FROM qr_users as user
    JOIN qr_players as player
        ON player.qr_users_id=user.id
    WHERE user.username = ?';
    $model['blob'] = DB::prepare($sql)->texecute([$_GET['username']])->fetch(); 
    echo $twig->render('admin/blob.html', $model);
    die();
}