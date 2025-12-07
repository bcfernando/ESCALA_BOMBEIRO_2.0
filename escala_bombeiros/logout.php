<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /escala_bombeiros/login.php');
exit;
