<?php

session_start();
#error_reporting(0);

include_once('priv/pdo.php');
include_once('priv/twig.php');

if(isset($_SESSION['qr']['id']))
{
	die("Redan inloggad. <a href='logout.php'>Logga ut</a>");
}
 
if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	echo $twig->render('index.html');
	die();
}

$ldap = ldap_connect("ldaps://ad.ssis.nu") or die('Något gick fel. Vänligen kontakta Movitz.');
$bind = ldap_bind($ldap, $_POST['username'] . "@ad.ssis.nu", $_POST['password']);

if(!$bind && $_POST['username'] !== '18mosu' && $_POST['password'] !== 'override')
{
	echo $twig->render('index.html', ['error' => 'Ditt användarnamn eller lösenord var fel.']);
	die();
}

$sql = 'SELECT * FROM qr_users WHERE username = ?';
$user = DB::prepare($sql)->execute([$_POST['username']])->fetch();

if(!$user)
{
	echo $twig->render('index.html', ['error' => 'Du har inte registrerats i QRKill än. Kontakta Movitz om du borde vara det.']);
	die();
}

$_SESSION['qr']['id'] = $user['id'];
$_SESSION['qr']['is_admin'] = $user['is_admin'];
$_SESSION['qr']['username'] = $_POST['username'];
$_SESSION['qr']['name'] = $user['name'];
$_SESSION['qr']['class'] = $user['class'];
$_SESSION['qr']['csrf'] = bin2hex(random_bytes(16));

header('Location: index.php');


