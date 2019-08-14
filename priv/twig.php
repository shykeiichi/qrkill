<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors',1); # uncomment if you need debugging

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    #'cache' => 'priv/cache',
]);
$twig->addGlobal('session', $_SESSION);

$config = json_decode(file_get_contents(__DIR__ . '/config.json'));

if(isset($config->ga_track_id))
{
    $twig->addGlobal('ga_track_id', $config->ga_track_id);
}