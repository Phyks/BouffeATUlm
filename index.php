<?php
    // Include necessary files
    if(!file_exists('data/config.php')) header('location: install.php');
    require_once('data/config.php');
    require_once('inc/User.class.php');
    require_once('inc/Invoices.class.php');
    require_once('inc/rain.tpl.class.php');
    require_once('inc/functions.php');
    raintpl::$tpl_dir = 'tpl/';
    raintpl::$cache_dir = 'tmp/';

    // Define raintpl instance
    $tpl = new raintpl();
    $tpl->assign('instance_title', htmlspecialchars(INSTANCE_TITLE));
    $tpl->assign('connection', false);
    $tpl->assign('notice', nl2br(getNotice()));
    $tpl->assign('error', '');
    $tpl->assign('base_url', htmlspecialchars(BASE_URL));
    $tpl->assign('currency', htmlspecialchars(CURRENCY));

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
                $user = new User();
                $user->setLogin($_POST['login']);
                if($user->exists($_POST['login']) && $user->checkPassword($_POST['password'])) {
                    $_SESSION['current_user'] = $user->sessionStore();
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

            if(!empty($_POST['login']) && !empty($_POST['display_name']) && (!empty($_POST['password']) || !empty($_POST['user_id'])) && isset($_POST['admin'])) {
                $user = new User();
                if(!empty($_POST['user_id'])) {
                    $user->setId($_POST['user_id']);
                }
                $user->setLogin($_POST['login']);
                $user->setDisplayName($_POST['login']);
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
                $tpl->assign('user_id', (!empty($user_id) ? (int) $user_id : -1));
                $tpl->assign('view', 'edit_user');
            }
            else {
                $users_list = new User();
                $users_list = $users_list->load_users();

                $tpl->assign('users', $users_list);
                $tpl->assign('view', 'list_users');
            }
            $tpl->assign('login_post', (!empty($_POST['login']) ? htmlspecialchars($_POST['login']) : ''));
            $tpl->assign('display_name_post', (!empty($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''));
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

        case 'edit_notice':
            if(isset($_POST['notice'])) {
                setNotice($_POST['notice']);
    
                header('location: index.php');
                exit();
            }

            $tpl->assign('notice', getNotice());
            $tpl->assign('show_settings', false);
            $tpl->draw('settings');
            break;

        case 'settings':
            if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db']) && !empty($_POST['currency']) && !empty($_POST['instance_title']) && !empty($_POST['base_url']) && !empty($_POST['timezone'])) {
                if(!is_writable('data/')) {
                    $tpl>assign('error', 'The script can\'t write in data/ dir, check permissions set on this folder.');
                }
                $config = file('data/config.php');

                foreach($config as $line_number=>$line) {
                    if(strpos($line, "MYSQL_HOST") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['mysql_host']."');\n";
                    elseif(strpos($line, "MYSQL_LOGIN") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['mysql_login']."');\n";
                    elseif(strpos($line, "MYSQL_PASSWORD") !== FALSE && !empty($_POST['mysql_password']))
                        $config[$line_number] = "\tdefine('".$_POST['mysql_password']."');\n";
                    elseif(strpos($line, "MYSQL_DB") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['mysql_db']."');\n";
                    elseif(strpos($line, "MYSQL_PREFIX") !== FALSE && !empty($_POST['mysql_prefix']))
                        $config[$line_number] = "\tdefine('".$_POST['mysql_prefix']."');\n";
                    elseif(strpos($line, "INSTANCE_TITLE") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['instance_title']."');\n";
                    elseif(strpos($line, "BASE_URL") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['base_url']."');\n";
                    elseif(strpos($line, "CURRENCY") !== FALSE)
                        $config[$line_number] = "\tdefine('".$_POST['currency']."');\n";
                    elseif(strpos($line_number, 'date_default_timezone_set') !== FALSE)
                        $config[$line_number] = "\tdate_default_timezone_set('".$_POST['timezone']."');\n";
                }

                if(file_put_contents("data/config.php", $config)) {
                    header('location: index.php');
                    exit();
                }
                else {
                    $tpl->assign('error', 'Unable to write data/config.php file.');
                }
            }

            $tpl->assign('mysql_host', MYSQL_HOST);
            $tpl->assign('mysql_login', MYSQL_LOGIN);
            $tpl->assign('mysql_db', MYSQL_DB);
            $tpl->assign('mysql_prefix', MYSQL_PREFIX);
            $tpl->assign('timezone', '');
            $tpl->assign('show_settings', true);
            $tpl->draw('settings');
            break;

        case 'new_invoice':
            if(!empty($_POST['what']) && !empty($_POST['amount']) && (float) $_POST['amount'] != 0 && !empty($_POST['date_day']) && !empty($_POST['date_month']) && !empty($_POST['date_year']) && !empty($_POST['users_in'])) {
                $invoice = new Invoice();
                $invoice->setWhat($_POST['what']);
                $invoice->setAmount($_POST['amount']);
                $invoice->setBuyer($current_user->getId());
                $invoice->setDate(time());

                $users_in = '';
                $guests = array();
                foreach($_POST['users_in'] as $user) {
                    $users_in .= ($users_in != '') ? ', ' : '';
                    $users_in .= $user.'('.(!empty($_POST['guest_user_'.$user]) ? (int) $_POST['guest_user_'.$user] : '0').')';
                    $guests[$user] = (int) $_POST['guest_user_'.$user];
                }
                $invoice->setUsersIn($users_in);

                //$invoice->save();
//                header('location: index.php');
  //              exit();
            }

            $users_list = new User();
            $users_list = $users_list->load_users();

            $tpl->assign('days', range(1,31)); // TODO : Improve it
            $tpl->assign('months', range(1, 12));
            $tpl->assign('years', range(date('Y') - 1, date('Y') + 1));

            $tpl->assign('day_post', (!empty($_POST['date_day']) ? (int) $_POST['date_day'] : (int) date('d')));
            $tpl->assign('month_post', (!empty($_POST['date_month']) ? (int) $_POST['date_month'] : (int) date('m')));
            $tpl->assign('year_post', (!empty($_POST['date_year']) ? (int) $_POST['date_year'] : (int) date('Y')));
            $tpl->assign('amount_post', (!empty($_POST['amount']) ? (float) $_POST['amount'] : 0));
            $tpl->assign('what_post', (!empty($_POST['what']) ? htmlspecialchars($_POST['what']) : ''));
            $tpl->assign('users', $users_list);
            $tpl->assign('users_in', (!empty($_POST['users_in']) ? $_POST['users_in'] : array()));
            $tpl->assign('guests', (!empty($guests) ? $guests : array()));
            $tpl->draw('new_invoice');
            break;

        default:
            $users_list = new User();
            $users_list = $users_list->load_users();

            $invoices_list = new Invoices();
            $invoices_list = $invoices_list->load_invoices();

            $tpl->assign('users', $users_list);
            $tpl->assign('invoices', $invoices_list);

            $tpl->draw('index');
            break;
    }
