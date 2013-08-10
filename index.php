<?php
    if(!file_exists('inc/config.php')) header('location: install.php');
    require_once('inc/config.php');
    require_once('inc/User.class.php');
    require_once('inc/rain.tpl.class.php');
    raintpl::$tpl_dir = 'tpl/';
    raintpl::$cache_dir = 'tmp/';

    $tpl = new raintpl();
    $tpl->assign('instance_title', htmlspecialchars(INSTANCE_TITLE));
    $tpl->assign('connection', false);
    $tpl->assign('notice', '');
    $tpl->assign('error', '');
    
    session_start();
    $current_user = (isset($_SESSION['current_user']) ? unserialize($_SESSION['current_user']) : false);
    $tpl->assign('admin', ($current_user !== false) ? (int) $current_user['admin'] : 0);

    $usersManager = new User();

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
            $tpl->assign('connection', true);
            $tpl->assign('user_post', (!empty($_POST['login'])) ? htmlspecialchars($_POST['login']) : '');
            $tpl->draw('connexion');
            break;

        case 'disconnect':
            $current_user = false;
            session_destroy();
            header('location: index.php?do=connect');
            exit();
            break;

        case 'password':
            if(!empty($_POST['password']) && !empty($_POST['password_confirm'])) {
                if($_POST['password'] == $_POST['password_confirm']) {
                    $user = new User();
                    $user->sessionRestore($current_user, false);
                    $user->setPassword($user->encrypt($_POST['password']));
                    $user->save();

                    header('location: index.php');
                    exit();
                }
                else {
                    $tpl->assign('error', 'The content of the two password fields doesn\'t match.');
                }
            }
            $tpl->draw('edit_users');
            break;

        default:
            $tpl->assign('users', array(0=>array("name"=>"truc")));
            $tpl->assign('bill', array(0=>array()));
            $tpl->draw('index');
            break;
    }
