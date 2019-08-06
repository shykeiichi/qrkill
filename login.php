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
	echo $twig->render('login.html');
	die();
}

function errorDie($error)
{
	global $twig;
	echo $twig->render('login.html', ['error' => $error]);
	die();
}

$ldap = ldap_connect("ldaps://ad.ssis.nu") or errorDie('Något gick fel. Vänligen kontakta Movitz.');
$bind = ldap_bind($ldap, $_POST['username'] . "@ad.ssis.nu", $_POST['password']) or errorDie('Fel lösenord eller användarnamn.');

$sql = 'SELECT * FROM qr_users WHERE username = ?';
$userInfo = DB::prepare($sql)->execute([$_POST['username']])->fetch();

if ($userInfo === false)
{
	$search = ldap_search($ldap, "OU=Elever,DC=ad,DC=ssis,DC=nu", "(cn=" . $_POST['username'] . ")", array("cn", "givenName", "sn", "memberOf")) or errorDie('Något gick fel. Vänligen kontakta Movitz.');
	$info = ldap_get_entries($ldap, $search) or errorDie('Något gick fel. Vänligen kontakta Movitz.');

	$name = $info[0]['givenname'][0] . ' ' . $info[0]['sn'][0];
	$class = 'Okänd klass';
	
	foreach ($info[0]['memberof'] as $key => $value)
	{
		if(strpos($value, 'OU=Klass') !== false) 
		{
			$class = substr($value, 3, 5);
			break;
		}
	}

	$sql = 'SELECT count(id) FROM qr_users';
    $isAdmin = DB::prepare($sql)->execute()->fetchColumn() === '0';
    $secret = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVW'), 10);
	
	$sql = 'INSERT INTO qr_users (username, is_admin, name, class, secret) VALUES (?, ?, ?, ?)';
    DB::prepare($sql)->execute([$_POST['username'], $isAdmin, $name, $class]);
    
    $_SESSION['qr']['id'] = DB::lastInsertId();
    $_SESSION['qr']['is_admin'] = $isAdmin;
    $_SESSION['qr']['username'] = $_POST['username'];
    $_SESSION['qr']['name'] = $name;
    $_SESSION['qr']['secret'] = $secret;
    $_SESSION['qr']['class'] = $class;

} else 
{
	$_SESSION['qr']['id'] = $userInfo['id'];
    $_SESSION['qr']['is_admin'] = $userInfo['is_admin'];
    $_SESSION['qr']['username'] = $_POST['username'];
    $_SESSION['qr']['name'] = $userInfo['name'];
    $_SESSION['qr']['secret'] = $userInfo['secret'];
    $_SESSION['qr']['class'] = $userInfo['class'];
}

$_SESSION['qr']['csrf'] = bin2hex(random_bytes(16));

header('Location: index.php');
