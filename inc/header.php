<?php
    if(!file_exists('inc/config.php')) header('location: install.php');
    
    session_start();

    require_once('inc/config.php');
