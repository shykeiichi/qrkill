<?php

session_start();

require_once '../priv/twig.php';
require_once '../priv/pdo.php';

if(!isset($_SESSION['qr']['is_admin']) || $_SESSION['qr']['is_admin'] === '0')
{
    header('Location: index.php');
    die();
}


if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']))
{ 
    $sql = 'SELECT * FROM qr_users WHERE id = ?';
    $model['blob'] = DB::prepare($sql)->execute([$_GET['id']])->fetch(); 
    echo $twig->render('admin/blob.html', $model);
    die();
}

if($_SERVER['REQUEST_METHOD'] === 'GET')
{ 
    $sql = 'SELECT * FROM qr_users';
    $model['users'] = DB::prepare($sql)->execute()->fetchAll(); 
    echo $twig->render('admin/users.html', $model);
    die();
}


if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if($_POST['action'] === 'Skapa')
    {
        $sql = 'INSERT INTO qr_users (username, name, class, is_admin) VALUES (?, ?, ?, ?)';
        DB::prepare($sql)->execute([$_POST['username'], $_POST['name'], $_POST['class'], $_POST['is_admin']]);
        header('Location: users.php?id='.DB::lastInsertId());
        die();
    }

    if($_POST['action'] === 'Importera alla användare')
    {
        $ldap = ldap_connect("ldaps://ad.ssis.nu") or die('ldap_connect failed');
        $bind = ldap_bind($ldap, $_POST['username'] . "@ad.ssis.nu", $_POST['password']) or die('Fel lösenord eller användarnamn.');
    
        $search = ldap_search($ldap, "OU=Elever,DC=ad,DC=ssis,DC=nu", "(cn=*)", array("cn", "givenName", "sn", "memberOf")) or die('ldap_search failed');
        $users = ldap_get_entries($ldap, $search) or die('ldap_get_entries failed');
    
        unset($users['count']);

        foreach($users as $key => $user)
        {
            $name = $user['givenname'][0] . ' ' . $user['sn'][0];
            $username = $user['cn'][0];
            $class = 'Okänd klass';
            
            foreach ($user['memberof'] as $key => $value)
            {
                if(strpos($value, 'OU=Klass') !== false) 
                {
                    $class = substr($value, 3, 5);
                    break;
                }
            }

            $sql = 'INSERT INTO qr_users (username, name, class) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE class = ?';
            DB::prepare($sql)->execute([$username, $name, $class, $class]);
            
        }
        header('Location: users.php');
        die();
    }
}

