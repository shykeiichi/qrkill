<?php
session_start();
require_once 'priv/errorhandler.php';
$_SESSION = [];
header('Location: index.php');