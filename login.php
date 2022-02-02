<?php

session_start();

require_once 'priv/errorhandler.php';
require_once 'priv/pdo.php';
require_once 'priv/twig.php';

#error_reporting(0);

if(isset($_SESSION['qr']['id']))
{
	die("Redan inloggad. <a href='logout.php'>Logga ut</a>");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	header('Location: index.php');
	die();
}

#$url = 'https://api.ssis.nu/login/';
#$user=explode('@', $_POST['username'])[0];
#$pass=$_POST['password'];
#$params=sprintf('{"user":"%s","pass":"%s"}',$user,$pass);
#$data = array('user' => $user,'pass'=>$pass);
#$query=http_build_query($data);
// use key 'http' even if you send the request to https://...
#$options = array(
#    'http' => array(
#        'method'  => 'POST',
#		'header' =>"Connection: close\r\n".
#                        "Content-Length: ".strlen($query)."\r\n",
#        'content' => $query
#    )
#);
#$context  = stream_context_create($options);
#$result = file_get_contents($url, false, $context);
#if ($result === FALSE) { /* Handle error */ }
#$dat=json_decode($result);
#$bind=($dat->{'result'})=='OK';
#echo $result;
#echo $_POST['username'];
$username = explode('@', $_POST['username'])[0];
echo "hi";
$ldap = ldap_connect("ldaps://ad.ssis.nu") or die('Något gick fel. Vänligen kontakta Movitz.');
$bind = ldap_bind($ldap, $username . "@ad.ssis.nu", $_POST['password']);
echo "hi";
$sql = 'SELECT id, name, is_admin, class FROM qr_users WHERE username = ?';
$user = DB::prepare($sql)->texecute([$username])->fetch();

if(!$bind)
{
	echo $twig->render('login.html', ['error' => 'Ditt användarnamn eller lösenord var fel.']);
	if($user)
	{
		$sql = "INSERT INTO qr_logins (success, qr_users_id) VALUES (0, ?)";
		DB::prepare($sql)->texecute([$user['id']]);
	}
	echo "hi";
	die();
}

if($user)
{
	$sql = "INSERT INTO qr_logins (success, qr_users_id) VALUES (1, ?)"; 
	DB::prepare($sql)->texecute([$user['id']]);

	$_SESSION['qr']['username'] = $username;
	$_SESSION['qr']['id'] = $user['id'];
	$_SESSION['qr']['is_admin'] = $user['is_admin'];
	$_SESSION['qr']['name'] = $user['name'];
	$_SESSION['qr']['class'] = $user['class'];
	echo "hi";
	header('Location: index.php');
	die();
}

$search = ldap_search($ldap, "DC=ad,DC=ssis,DC=nu", "(sAMAccountName=" . $username . ")", array("cn", "givenName", "sn", "memberOf")) or die('ldap_search failed');
$userInfo = ldap_get_entries($ldap, $search);
if($userInfo['count'] == 0)
{
	echo $twig->render('login.html', ['error' => 'Kunde inte hitta dig i AD:t. Är du inte en elev? Kontakta Movitz om du vill ha tillgång.']);
	die();
}
$userInfo = $userInfo[0];

$name = $userInfo['givenname'][0] . ' ' . $userInfo['sn'][0];
$class = 'Lärare';

foreach($userInfo['memberof'] as $sg)
{
	if(strpos($sg, 'OU=Klass') !== false) 
	{
		$class = substr($sg, 3, 5);
		break;
	}
}

$sql = 'SELECT (COUNT(*) = 0) FROM qr_users';
$isAdmin = DB::prepare($sql)->texecute()->fetchColumn();

$sql = 'INSERT INTO qr_users (username, name, class, is_admin) VALUES (?, ?, ?, ?)';
DB::prepare($sql)->texecute([$username, $name, $class, $isAdmin]);

$userId = DB::lastInsertId();
$_SESSION['qr']['id'] = $userId;
$_SESSION['qr']['username'] = $username;
$_SESSION['qr']['is_admin'] = $isAdmin;
$_SESSION['qr']['name'] = $name;
$_SESSION['qr']['class'] = $class;

$sql = "INSERT INTO qr_logins (success, qr_users_id) VALUES (1, ?)"; 
DB::prepare($sql)->texecute([$userId]);
echo "hi";
header('Location: index.php');

