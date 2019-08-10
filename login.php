<?php

session_start();

require_once 'priv/pdo.php';
require_once 'priv/twig.php';
require_once 'priv/errorhandler.php';

if(isset($_SESSION['qr']['id']))
{
	die("Redan inloggad. <a href='logout.php'>Logga ut</a>");
}
 
if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	header('Location: index.php');
	die();
}

$ldap = ldap_connect("ldaps://ad.ssis.nu") or die('Något gick fel. Vänligen kontakta Movitz.');
$bind = ldap_bind($ldap, $_POST['username'] . "@ad.ssis.nu", $_POST['password']);

$sql = 'SELECT * FROM qr_users WHERE username = ?';
$user = DB::prepare($sql)->execute([$_POST['username']])->fetch();

if(!$bind)
{
	echo $twig->render('login.html', ['error' => 'Ditt användarnamn eller lösenord var fel.']);
	if(isset($user['id']))
	{
		$sql = "INSERT INTO qr_logins (success, qr_users_id) VALUES (0, ?)";
		DB::prepare($sql)->execute([$user['id']]);
	}
	die();
}

if(!isset($user['id']))
{
	$search = ldap_search($ldap, "OU=Elever,DC=ad,DC=ssis,DC=nu", "(cn=" . $_POST['username'] . ")", array("cn", "givenName", "sn", "memberOf")) or die('ldap_search failed');
	$userInfo = ldap_get_entries($ldap, $search);
	if($userInfo['count'] == 0)
	{
		echo $twig->render('login.html', ['error' => 'Kunde inte hitta dig i AD:t. Är du inte en elev? Kontakta Movitz om du vill ha tillgång.']);
		die();
	}
	$userInfo = $userInfo[0];

	$name = $userInfo['givenname'][0] . ' ' . $userInfo['sn'][0];
	$username = $userInfo['cn'][0];
	$class = 'Okänd klass';
	
	foreach ($userInfo['memberof'] as $value)
	{
		if(strpos($value, 'OU=Klass') !== false) 
		{
			$class = substr($value, 3, 5);
			break;
		}
	}
	
	$sql = 'SELECT (COUNT(*) = 0) FROM qr_users';
	$isAdmin = DB::prepare($sql)->execute()->fetchColumn();

	$sql = 'INSERT INTO qr_users (username, name, class, is_admin) VALUES (?, ?, ?, ?)';
	DB::prepare($sql)->execute([$username, $name, $class, $isAdmin]);

}

$sql = 'SELECT * FROM qr_users WHERE username = ?';# jag vet att det onödigt, men jag är lat
$user = DB::prepare($sql)->execute([$_POST['username']])->fetch();

$sql = "INSERT INTO qr_logins (success, qr_users_id) VALUES (1, ?)"; 
DB::prepare($sql)->execute([$user['id']]);

$_SESSION['qr']['id'] = $user['id'];
$_SESSION['qr']['is_admin'] = $user['is_admin'];
$_SESSION['qr']['username'] = $_POST['username'];
$_SESSION['qr']['name'] = $user['name'];
$_SESSION['qr']['class'] = $user['class'];
$_SESSION['qr']['csrf'] = bin2hex(random_bytes(16));

header('Location: index.php');


