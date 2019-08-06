<?php

session_start();

if(!isset($_SESSION['qr']['id']))
{
    echo json_encode(['message' => 'Din session har gått ut. Vänligen logga in igen.', 'id' => 1]);
    die();
}

if(rand(0,1))
{
    echo json_encode(['message' => 'Du dödade din fiende! Ger dig en ny.', 'id' => 2]);
    die();
}
else
{
    echo json_encode(['message' => 'Koden du angav var inte korrekt', 'id' => 3]);
    die();
}