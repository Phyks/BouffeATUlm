<?php
    if(!file_exists('inc/config.php')) header('location: install.php');
    require_once('inc/config.php');
    require_once('inc/User.class.php');
    require_once('inc/rain.tpl.class.php');
    raintpl::$tpl_dir = 'tpl/';
    raintpl::$cache_dir = 'tmp/';

    $tpl = new raintpl();
    $tpl->assign('instance_title', INSTANCE_TITLE);
    
    session_start();
    $current_user = (isset($_SESSION['current_user']) ? unserialize($_SESSION['current_user']) : false);

    if($current_user === false && (empty($_GET['do']) OR $_GET['do'] != 'connect')) { //If not connected, go to connection page
        header('location: index.php?do=connect');
    }
    
    if(empty($_GET['do'])) {
        $_GET['do'] = '';
    }

    switch($_GET['do']) {
        case 'connect':
            if($current_user !== false) header('location: index.php');
            if(!empty($_POST['login']) && !empty($_POST['password'])) {
                $current_user = new User();
                $current_user->setLogin($_POST['login']);
                if($current_user->exists($_POST['login']) && $current_user->checkPassword($_POST['password'])) {
                    $_SESSION['current_user'] = $current_user->sessionStore();
                    header('location: index.php');
                    exit();
                }
                else {
                   $error = "Unknown username/password.";
                }
            }
            $tpl->draw('connexion');
            break;

        case 'disconnect':
            $current_user = false;
            session_destroy();
            header('location: index.php?do=connect');
            exit();

        default:

            break;
    }
