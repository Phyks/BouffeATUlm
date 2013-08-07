<?php
    if(!file_exists('config.php')) header('location: install.php');
    
    session_start();

    require_once('config.php');
