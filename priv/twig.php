<?php

require_once __DIR__ . '/../vendor/autoload.php';

#ini_set('display_errors',1); # uncomment if you need debugging

spl_autoload_register(function ($classname) {
    $dirs = array (
        'priv/Twig-2.x/'
    );

    foreach ($dirs as $dir) {
        $filename = $dir . str_replace('\\', '/', $classname) . '.php';
        if (file_exists($filename)) {
            require_once $filename;
            break;
        }
    }

});

$loader = new \Twig\Loader\FilesystemLoader('/home/18mosu/public_html/qr/priv/templates');
$twig = new \Twig\Environment($loader, [
    #'cache' => 'cache',
]);
$twig->addGlobal('session', $_SESSION);