<?php
    require_once('inc/header.php');

init(true,false) {
    global $bdd;

    $bdd = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB, MYSQL_LOGIN, MYSQL_PASSWORD);
    $bdd->query("SET NAMES 'utf8'");

    session_start();

    date_default_timezone_set(TIMEZONE);

    if($protect && empty($_SESSION['login'])) {
        header('location: connexion.php');
        exit();
    }

    if($admin && $_SESSION['admin']) {
        header('location: message.php?id=7');
        exit();
    }
}
