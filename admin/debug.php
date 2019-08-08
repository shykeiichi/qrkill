<?php

session_start();

include_once('../priv/pdo.php');
include_once('../priv/twig.php');

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: ../index.php');
    die();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    echo $twig->render('admin/debug.html');
    die();
}

$ldap = ldap_connect("ldaps://ad.ssis.nu") or die('LDAP failed');
$bind = ldap_bind($ldap, $_SESSION['qr']['username'] . "@ad.ssis.nu", $_POST['password']);

if(!$bind)
{
	echo $twig->render('admin/debug.html', ['error' => 'Ditt lösenord var fel.']);
	die();
}

session_destroy();
session_start();

$sql = 'SELECT * FROM qr_users WHERE username = ? AND is_admin = 0';
$user = DB::prepare($sql)->execute([$_POST['username']])->fetch();

if(!$user)
{
	echo $twig->render('admin/debug.html', ['error' => 'Kunde inte hitta användaren, eller så är användaren admin.']);
	die();
}

$_SESSION['qr']['id'] = $user['id'];
$_SESSION['qr']['is_admin'] = $user['is_admin'];
$_SESSION['qr']['username'] = $_POST['username'];
$_SESSION['qr']['name'] = $user['name'];
$_SESSION['qr']['class'] = $user['class'];
$_SESSION['qr']['csrf'] = bin2hex(random_bytes(16));

header('Location: ../index.php');


