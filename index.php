<?php
    // Include necessary files
    if(!file_exists('inc/config.php')) header('location: install.php');
    require_once('inc/config.php');
    require_once('inc/User.class.php');
    require_once('inc/rain.tpl.class.php');
    raintpl::$tpl_dir = 'tpl/';
    raintpl::$cache_dir = 'tmp/';

    // Define raintpl instance
    $tpl = new raintpl();
    $tpl->assign('instance_title', htmlspecialchars(INSTANCE_TITLE));
    $tpl->assign('connection', false);
    $tpl->assign('notice', '');
    $tpl->assign('error', '');

    // Handle current user status
    session_start();
    $current_user = new User();
    if(isset($_SESSION['current_user'])) {
        $current_user->sessionRestore($_SESSION['current_user'], true);
    }
    else {
        $current_user = false;
    }
    $tpl->assign('current_user', $current_user);

    // If not connected, redirect to connection page
    if($current_user === false && (empty($_GET['do']) OR $_GET['do'] != 'connect')) {
        header('location: index.php?do=connect');
    }
    
    // Initialize empty $_GET['do'] if required to avoid error
    if(empty($_GET['do'])) {
        $_GET['do'] = '';
    }

    // Check what to do
    switch($_GET['do']) {
        case 'connect':
            if($current_user !== false) {
                header('location: index.php');
            }
            if(!empty($_POST['login']) && !empty($_POST['password'])) {
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
                    $current_user->setPassword($user->encrypt($_POST['password']));
                    $current_user->save();

                    header('location: index.php');
                    exit();
                }
                else {
                    $tpl->assign('error', 'The content of the two password fields doesn\'t match.');
                }
            }
            $tpl->assign('view', 'password');
            $tpl->draw('edit_users');
            break;

        case 'edit_users':
        case 'add_user':
            if(!$current_user->getAdmin()) {
                header('location: index.php');
            }

            if(!empty($_POST['login']) &&  (!empty($_POST['password']) || !empty($_POST['user_id'])) && isset($_POST['admin'])) {
                $user = new User();
                if(!empty($_POST['user_id'])) {
                    $user->setId($_POST['user_id']);
                }
                $user->setLogin($_POST['login']);
                if(!empty($_POST['password'])) {
                    $user->setPassword($user->encrypt($_POST['password']));
                }
                $user->setAdmin($_POST['admin']);
                $user->save();

                header('location: index.php?do=edit_users');
                exit();
            }
 
            if(!empty($_GET['user_id']) || $_GET['do'] == 'add_user') {
                if(!empty($_GET['user_id'])) {
                    $user_id = (int) $_GET['user_id'];
                    $user = new User();
                    $user->load_user(array('id'=>$user_id));
                    $tpl->assign('user_data', $user);
                }
                $tpl->assign('user_id', (!empty($user_id) ? $user_id : -1));
                $tpl->assign('view', 'edit_user');
            }
            else {
                $users_list = new User();
                $users_list = $users_list->load_users();

                $tpl->assign('users', $users_list);
                $tpl->assign('view', 'list_users');
            }
            $tpl->assign('login_post', (!empty($_POST['login']) ? htmlspecialchars($_POST['login']) : ''));
            $tpl->assign('admin_post', (isset($_POST['admin']) ? (int) $_POST['admin'] : -1));
            $tpl->draw('edit_users');
            break;

        case 'delete_user':
            if($_GET['user_id'] != $current_user->getId()) {
                $user = new User();
                $user->setId($_GET['user_id']);
                $user->delete();

                header('location: index.php?do=edit_users');
                exit();
            }
            break;

        default:
            $users_list = new User();
            $users_list = $users_list->load_users();
            $tpl->assign('users', $users_list);
            $tpl->assign('bill', array(0=>array()));
            $tpl->draw('index');
            break;
    }
