<?php
    // Include necessary files
    if(!file_exists('data/config.php')) { header('location: install.php'); exit(); }
    require_once('data/config.php');
    require_once('inc/User.class.php');
    require_once('inc/Invoices.class.php');
    require_once('inc/Paybacks.class.php');
    require_once('inc/GlobalPaybacks.class.php');
    require_once('inc/rain.tpl.class.php');
    require_once('inc/functions.php');
    require_once('inc/Ban.inc.php');
    require_once('inc/CSRF.inc.php');

    session_start();
    $i18n = array();
    require_once(LANG);

    // Long lasting session inspired by the work from sbgodin for shaarli
    define('WEB_PATH', substr($_SERVER["REQUEST_URI"], 0, 1+strrpos($_SERVER["REQUEST_URI"], '/', 0)));

    if(!empty($_GET['json'])) {
        raintpl::$tpl_dir = 'tpl/json/';
        $get_redir = 'json=1';
    }
    else {
        raintpl::$tpl_dir = TEMPLATE_DIR;
        $get_redir = '';
    }
    raintpl::$cache_dir = 'tmp/';

    // Define raintpl instance
    $tpl = new raintpl();
    $tpl->assign('instance_title', htmlspecialchars(INSTANCE_TITLE));
    $tpl->assign('connection', false);
    $tpl->assign('notice', nl2br(getNotice()));
    $tpl->assign('error', '');
    $tpl->assign('base_url', htmlspecialchars(BASE_URL));
    $tpl->assign('currency', htmlspecialchars(CURRENCY));
    $tpl->assign('email_webmaster', htmlspecialchars(EMAIL_WEBMASTER));
    $tpl->assign('i18n', $i18n);

    $current_user = new User();
    if(isset($_SESSION['current_user'])) {
        $current_user->sessionRestore($_SESSION['current_user'], true);
    }
    else {
        if(!empty($_COOKIE['bouffeatulm_staySignedIn']) && !empty($_COOKIE['bouffeatulm_login'])) {
            // Connect back
            $user = new User();
            $user->setLogin($_COOKIE['bouffeatulm_login']);

            if(ban_canLogin() == false) {
                setcookie('bouffeatulm_login', 0, 0, WEB_PATH);
                setcookie('bouffeatulm_staySignedIn', 0, 0, WEB_PATH);
                exit($errors['unknown_username_password']);
            }
            else {
                $user = $user->exists($_COOKIE['bouffeatulm_login']);
                if($_COOKIE['bouffeatulm_staySignedIn'] === md5($user->getStaySignedInToken().$_SERVER['REMOTE_ADDR'])) {
                    ban_loginOk();
                    $_SESSION['current_user'] = $user->sessionStore();
                    $_SESSION['ip'] = user_ip();
                    setcookie('bouffeatulm_login', $_COOKIE['bouffeatulm_login'], time()+31536000, WEB_PATH);
                    setcookie('bouffeatulm_staySignedIn', $_COOKIE['bouffeatulm_staySignedIn'], time()+31536000, WEB_PATH);
                    header('location: index.php?'.$get_redir);
                    exit();
                }
                else {
                    ban_loginFailed();
                    setcookie('bouffeatulm_login', 0, 0, WEB_PATH);
                    setcookie('bouffeatulm_staySignedIn', 0, 0, WEB_PATH);
                    exit($errors['unknown_username_password']);
                }
            }
        }
        else {
            $current_user = false;
        }
    }

    $tpl->assign('current_user', secureDisplay($current_user));

    if(!empty($_GET['json_token'])) {
        $current_user = new User();

        if($current_user->load(array('json_token'=>$_GET['json_token'], true)) === false) {
            header('location: index.php?do=connect'.$get_redir);
            exit();
        }
        else {
            if(!empty($get_redir))
                $get_redir .= '&';

            $get_redir .= 'json_token='.$_GET['json_token'];
        }
    }
    else {
        //If json token not available

        // If not connected, redirect to connection page
        if($current_user === false && (empty($_GET['do']) OR $_GET['do'] != 'connect')) {
            header('location: index.php?do=connect&'.$get_redir);
            exit();
        }

        // If IP has changed, logout
        if($current_user !== false && user_ip() != $_SESSION['ip']) {
            logout();
            header('location: index.php?do=connect&'.$get_redir);
            exit();
        }
    }

    // Initialize empty $_GET['do'] if required to avoid error
    if(empty($_GET['do'])) {
        $_GET['do'] = '';
    }

    // Check what to do
    switch($_GET['do']) {
        case 'connect':
            if($current_user !== false) {
                header('location: index.php?'.$get_redir);
                exit();
            }
            if(!empty($_POST['login']) && !empty($_POST['password']) && check_token(600, 'connection')) {
                $user = new User();
                $user->setLogin($_POST['login']);
                if(ban_canLogin() == false) {
                    $error = $errors['unknown_username_password'];
                }
                else {
                    $user = $user->exists($_POST['login']);
                    if($user !== false && $user->checkPassword($_POST['password'])) {
                        ban_loginOk();
                        $_SESSION['current_user'] = $user->sessionStore();
                        $_SESSION['ip'] = user_ip();

                        if(!empty($_POST['remember_me'])) { // Handle remember me cookie
                            $token = md5(uniqid(mt_rand(), true));
                            $user->setStaySignedInToken($token);
                            $user->save();
                            setcookie('bouffeatulm_login', $_POST['login'], time()+31536000, WEB_PATH);
                            setcookie('bouffeatulm_staySignedIn', md5($token.$_SERVER['REMOTE_ADDR']), time()+31536000, WEB_PATH);
                        }

                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                    else {
                        ban_loginFailed();
                        $error = $errors['unknown_username_password'];
                    }
                }
            }
            $tpl->assign('connection', true);
            $tpl->assign('user_post', (!empty($_POST['login'])) ? htmlspecialchars($_POST['login']) : '');
            if(!empty($error))
                $tpl->assign('error', $error);
            $tpl->assign('token', generate_token('connection'));
            $tpl->draw('connection');
            break;

        case 'disconnect':
            $current_user = false;
            logout();
            header('location: index.php?do=connect&'.$get_redir);
            exit();
            break;

        case 'password':
            if(!empty($_POST['email']) && !empty($_POST['notifications'])) {
                if(check_token(600, 'password')) {
                    if(!empty($_POST['password']) && !empty($_POST['password_confirm'])) {
                        if($_POST['password'] == $_POST['password_confirm']) {
                            $current_user->setPassword($current_user->encrypt($_POST['password']));
                        }
                        else {
                            $error = true;
                            $tpl->assign('error', $errors['password_mismatch']);
                        }
                    }

                    if($current_user->setEmail($_POST['email']) === false) {
                        $error = true;
                        $tpl->assign('error', $errors['email_invalid']);
                    }

                    $current_user->setNotifications($_POST['notifications']);
                    $current_user->save();

                    if(!empty($error)) {
                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                }
            }

            $tpl->assign('view', 'password');
            $tpl->assign('json_token', htmlspecialchars($current_user->getJsonToken()));
            $tpl->assign('token', generate_token('password'));
            $tpl->draw('edit_users');
            break;

        case 'edit_users':
        case 'add_user':
            if(!$current_user->getAdmin()) {
                header('location: index.php?'.$get_redir);
                exit();
            }

            if(!empty($_POST['login']) && (!empty($_POST['password'])  || !empty($_POST['user_id'])) && !empty($_POST['notifications']) && isset($_POST['admin'])) {
                if(check_token(600, 'edit_users')) {
                    $user = new User();
                    if(!empty($_POST['user_id'])) {
                        $user = $user->load(array('id' => $_POST['user_id']), true);
                    }
                    else {
                        $user->newJsonToken();
                    }
                    $user->setLogin($_POST['login']);
                    $user->setDisplayName(!empty($_POST['display_name']) ? $_POST['display_name'] : '');
                    if(!empty($_POST['password'])) {
                        $user->setPassword($user->encrypt($_POST['password']));
                    }
                    $user->setAdmin($_POST['admin']);
                    $user->setStaySignedInToken(NULL);

                    if($user->setEmail($_POST['email']) !== false) {
                        if(!empty($_POST['user_id']) || $user->isUnique()) {
                            $user->setNotifications($_POST['notifications']);

                            $user->save();

                            // Clear the cache
                            ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                            array_map("unlink", $cached_files);

                            header('location: index.php?do=edit_users&'.$get_redir);
                            exit();
                        }
                        else {
                            $tpl->assign('error', $errors['user_already_exists']);
                        }
                    }
                    else {
                        $tpl->assign('error', $errors['email_invalid']);
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                }
            }

            if(!empty($_GET['user_id']) || $_GET['do'] == 'add_user') {
                if(!empty($_GET['user_id'])) {
                    $user_id = (int) $_GET['user_id'];
                    $user = new User();
                    $user = $user->load(array('id'=>$user_id), true);
                    $tpl->assign('user_data', $user->secureDisplay());
                }
                $tpl->assign('user_id', (!empty($user_id) ? (int) $user_id : -1));
                $tpl->assign('view', 'edit_user');
            }
            else {
                $users_list = new User();
                $users_list = $users_list->load();

                $tpl->assign('users', secureDisplay($users_list));
                $tpl->assign('view', 'list_users');
            }
            $tpl->assign('login_post', (!empty($_POST['login']) ? htmlspecialchars($_POST['login']) : ''));
            $tpl->assign('email_post', (!empty($_POST['email']) ? htmlspecialchars($_POST['email']) : ''));
            $tpl->assign('display_name_post', (!empty($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''));
            $tpl->assign('admin_post', (isset($_POST['admin']) ? (int) $_POST['admin'] : -1));
            $tpl->assign('token', generate_token('edit_users'));
            $tpl->draw('edit_users');
            break;

        case 'new_token':
            if(!empty($_GET['user_id']) && $current_user->getAdmin()) {
                $user_id = (int) $_GET['user_id'];
            }
            else {
                $user_id = $current_user->getId();
            }

            if(check_token(600, 'password') || check_token(600, 'edit_users')) {
                $user = new User();
                $user = $user->load(array('id'=>$user_id), true);
                $user->newJsonToken();
                $user->save();

                if(empty($_GET['user_id']))
                    $_SESSION['current_user'] = $user->sessionStore();

                if(!empty($_GET['user_id']))
                    header('location: index.php?do=edit_users&user_id='.$user_id);
                else
                    header('location: index.php?do=password&'.$get_redir);
                exit();
            }
            else {
                $tpl->assign('error', $errors['token_error']);
                $tpl->assign('block_error', true);
                $tpl->draw('index');
                exit();
            }
            break;

        case 'delete_user':
            if($_GET['user_id'] != $current_user->getId()) {
                if(check_token(600, 'edit_users')) {
                    $user = new User();
                    $user->setId($_GET['user_id']);
                    $user->delete();

                    // Update concerned invoices
                    $invoices = new Invoice();
                    $invoices = $invoices->load();
                    if($invoices !== FALSE) {
                        foreach($invoices as $invoice) {
                            if($invoice->getBuyer() == $_GET['user_id']) {
                                $invoice->delete();
                            }
                            if($invoice->getUsersIn()->inUsersIn($_GET['user_id'])) {
                                $users_in = $invoice->getUsersIn()->get();
                                unset($users_in[$_GET['user_id']]);

                                if(empty($users_in) || array_keys($users_in) == array($invoice->getBuyer()))
                                    $invoice->delete();
                                else {
                                    $invoice->setUsersIn($users_in);
                                    $invoice->save();
                                }
                            }
                        }
                    }

                    // Update paybacks
                    $paybacks = new Payback();
                    $paybacks = $paybacks->load(array('from_user'=>(int) $_GET['user_id']));
                    if($paybacks !== FALSE) {
                        foreach($paybacks as $payback) {
                            $payback->delete();
                        }
                    }
                    $paybacks = new Payback();
                    $paybacks = $paybacks->load(array('to_user'=>(int) $_GET['user_id']));
                    if($paybacks !== FALSE) {
                        foreach($paybacks as $payback) {
                            $payback->delete();
                        }
                    }


                    // Clear the cache
                    ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                    array_map("unlink", $cached_files);

                    header('location: index.php?do=edit_users&'.$get_redir);
                    exit();
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                    $tpl->assign('block_error', 'true');
                    $tpl->draw('index');
                    exit();
                }
            }
            break;

        case 'edit_notice':
            if(isset($_POST['notice'])) {
                $tpl->assign('notice', htmlspecialchars($_POST['notice']));
                if(check_token(600, 'settings')) {
                    setNotice($_POST['notice']);

                    // Clear the cache
                    ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                    array_map("unlink", $cached_files);

                    header('location: index.php?'.$get_redir);
                    exit();
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                }
            }

            $tpl->assign('show_settings', false);
            $tpl->assign('token', generate_token('settings'));
            $tpl->draw('settings');
            break;

        case 'settings':
            if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db']) && !empty($_POST['instance_title']) && !empty($_POST['base_url']) && !empty($_POST['currency']) && !empty($_POST['timezone']) && !empty($_POST['template'])) {
                if(check_token(600, 'settings')) {
                    if(!is_writable('data/')) {
                        $tpl>assign('error', $errors['write_error_data']);
                    }
                    else {
                        if(!is_dir('tpl/'.$_POST['template'])) {
                            $tpl->assign('error', $errors['template_error']);
                        }
                        else {
                            $config = file('data/config.php');

                            foreach($config as $line_number=>$line) {
                                if(strpos(trim($line), "MYSQL_HOST") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_HOST', '".$_POST['mysql_host']."');\n";
                                elseif(strpos(trim($line), "MYSQL_LOGIN") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_LOGIN', '".$_POST['mysql_login']."');\n";
                                elseif(strpos(trim($line), "MYSQL_PASSWORD") !== false && !empty($_POST['mysql_password']))
                                    $config[$line_number] = "\tdefine('MYSQL_PASSWORD', '".$_POST['mysql_password']."');\n";
                                elseif(strpos(trim($line), "MYSQL_DB") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_DB', '".$_POST['mysql_db']."');\n";
                                elseif(strpos(trim($line), "MYSQL_PREFIX") !== false && !empty($_POST['mysql_prefix']))
                                    $config[$line_number] = "\tdefine('MYSQL_PREFIX', '".$_POST['mysql_prefix']."');\n";
                                elseif(strpos(trim($line), "INSTANCE_TITLE") !== false)
                                    $config[$line_number] = "\tdefine('INSTANCE_TITLE', '".$_POST['instance_title']."');\n";
                                elseif(strpos(trim($line), "BASE_URL") !== false)
                                    $config[$line_number] = "\tdefine('BASE_URL', '".$_POST['base_url']."');\n";
                                elseif(strpos(trim($line), "CURRENCY") !== false)
                                    $config[$line_number] = "\tdefine('CURRENCY', '".$_POST['currency']."');\n";
                                elseif(strpos(trim($line), "EMAIL_WEBMASTER") !== false)
                                    $config[$line_number] = "\tdefine('EMAIL_WEBMASTER', '".$_POST['email_webmaster']."');\n";
                                elseif(strpos(trim($line), "TEMPLATE_DIR") !== false)
                                    $config[$line_number] = "\tdefine('TEMPLATE_DIR', 'tpl/".$_POST['template']."/');\n";
                                elseif(strpos(trim($line), "LANG") !== false)
                                    $config[$line_number] = "\tdefine('LANG', 'i18n/".$_POST['lang']."');\n";
                                elseif(strpos(trim($line), 'date_default_timezone_set') !== false)
                                    $config[$line_number] = "\tdate_default_timezone_set('".$_POST['timezone']."');\n";
                            }

                            if(file_put_contents("data/config.php", $config)) {
                                // Clear the cache
                                ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                                array_map("unlink", $cached_files);

                                header('location: index.php?'.$get_redir);
                                exit();
                            }
                            else {
                                $tpl->assign('error', $errors['unable_write_config']);
                            }
                        }
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                }
            }

            $tpl->assign('mysql_host', htmlspecialchars(MYSQL_HOST));
            $tpl->assign('mysql_login', htmlspecialchars(MYSQL_LOGIN));
            $tpl->assign('mysql_db', htmlspecialchars(MYSQL_DB));
            $tpl->assign('mysql_prefix', htmlspecialchars(MYSQL_PREFIX));
            $tpl->assign('timezone', @date_default_timezone_get());
            $tpl->assign('show_settings', true);
            $tpl->assign('token', generate_token('settings'));
            $tpl->assign('templates', secureDisplay(listTemplates('tpl/')));
            $tpl->assign('current_template', htmlspecialchars(trim(substr(TEMPLATE_DIR, 4), '/')));
            $tpl->assign('current_lang', htmlspecialchars(LANG));
            $tpl->assign('available_lang', secureDisplay(listLangs()));
            $tpl->draw('settings');
            break;

        case 'new_invoice':
        case 'edit_invoice':
            $users_list = new User();
            $users_list = $users_list->load();

            if(!empty($_GET['id'])) {
                $invoice = new Invoice();
                $invoice = $invoice->load(array('id'=>(int) $_GET['id']), true);

                $date_hour = $invoice->getDate('a');
                $date_day = $invoice->getDate('d');
                $date_month = $invoice->getDate('m');
                $date_year = $invoice->getDate('Y');
                $amount = $invoice->getAmount();
                $what = $invoice->getWhat();
                $users_in = $invoice->getUsersIn()->get();
            }

            if(!empty($_POST['what'])) $what = $_POST['what'];
            if(!empty($_POST['amount'])) $amount = $_POST['amount'];
            if(!empty($_POST['date_day'])) $date_day = $_POST['date_day'];
            if(!empty($_POST['date_month'])) $date_month = $_POST['date_month'];
            if(!empty($_POST['date_year'])) $date_year = $_POST['date_year'];
            if(!empty($_POST['users_in'])) {
                $users_in = array();
                foreach($_POST['users_in'] as $user) {
                    $users_in[(int) $user] = (int) $_POST['guest_user_'.$user];
                }
            }

            if(!empty($_POST['what']) && !empty($_POST['amount']) && (float) $_POST['amount'] != 0 && isset($_POST['date_hour']) && !empty($_POST['date_day']) && !empty($_POST['date_month']) && !empty($_POST['date_year']) && !empty($_POST['users_in'])) {
                if(check_token(600, 'new_invoice')) {
                    if($_POST['amount'] <= 0) {
                        $tpl->assign('error', $errors['negative_amount']);
                    }
                    else {
                        if(array_keys($users_in) == array($current_user->getId())) {
                            $tpl->assign('error', $errors['no_users']);
                        }
                        else {
                            $invoice = new Invoice();

                            if(!empty($_POST['id'])) {
                                $invoice->setId($_POST['id']);
                            }

                            $invoice->setWhat($_POST['what']);
                            $invoice->setAmount($_POST['amount']);

                            if(empty($_POST['id'])) {
                                $invoice->setBuyer($current_user->getId());
                            }

                            $invoice->setDate(0, int2ampm($_POST['date_hour']), $_POST['date_day'], $_POST['date_month'], $_POST['date_year']);


                            $invoice->setUsersIn($users_in);

                            $invoice->save();

                            // Send notifications
                            if (!empty($_POST['id'])) {
                                $invoice = new Invoice();
                                $invoice = $invoice->load(array('id'=>$_POST['id']), true);
                                $buyer = $invoice->getBuyer();
                            }
                            else {
                                $buyer = $current_user->getId();
                            }
                            foreach ($users_in as $user_in=>$guest) {
                                if (empty($_POST['id']) && $user_in == $buyer) {
                                    continue;
                                }
                                $user_in_details = new User();
                                $user_in_details = $user_in_details->load(array('id'=>$user_in), true);
                                if (!empty($user_in_details->getEmail()) && $user_in_details->getNotifications() === 3) {
                                    sendmail($user_in_details, $subject, $msg, $from); // TODO notifs
                                }
                            }

                            // Clear the cache
                            ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                            array_map("unlink", $cached_files);

                            header('location: index.php?'.$get_redir);
                            exit();
                        }
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                }
            }

            $tpl->assign('days', range(1,31));
            $tpl->assign('months', range(1, 12));
            $tpl->assign('years', range(date('Y') - 1, date('Y') + 1));

            $tpl->assign('hour_post', (!empty($date_hour) ? (int) ampm2int($date_hour) : (int) ampm2int(date('a'))));
            $tpl->assign('day_post', (!empty($date_day) ? (int) $date_day : (int) date('d')));
            $tpl->assign('month_post', (!empty($date_month) ? (int) $date_month : (int) date('m')));
            $tpl->assign('year_post', (!empty($date_year) ? (int) $date_year : (int) date('Y')));
            $tpl->assign('amount_post', (!empty($amount) ? (float) $amount : 0));
            $tpl->assign('what_post', (!empty($what) ? htmlspecialchars($what) : ''));
            $tpl->assign('users', secureDisplay($users_list));

            if(isset($_POST['what']) && empty($_POST['what']))
                $tpl->assign('error', $errors['what_unknown']);
            if(!empty($_POST['amount']) && (float) $_POST['amount'] == 0)
                $tpl->assign('error', $errors['incorrect_amount']);

            $tpl->assign('users_in', (!empty($users_in) ? $users_in : array()));
            $tpl->assign('id', (!empty($_GET['id']) ? (int) $_GET['id'] : 0));
            $tpl->assign('token', generate_token('new_invoice'));
            $tpl->draw('new_invoice');
            break;

        case 'delete_invoice':
            if(!empty($_GET['id'])) {
                if(check_token(600, 'invoice')) {
                    $invoice = new Invoice();
                    $invoice = $invoice->load(array('id'=>(int) $_GET['id']), true);

                    if($current_user->getAdmin() || $invoice->getBuyer() == $current_user->getId()) {
                        $invoice->delete();

                        // Delete related paybacks
                        $paybacks = new Payback();
                        $paybacks = $paybacks->load(array('invoice_id'=>(int) $_GET['id']));

                        if($paybacks !== false) {
                            foreach($paybacks as $payback) {
                                $payback->delete();
                            }
                        }

                        // Clear the cache
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['unauthorized']);
                        $tpl->assign('block_error', true);
                        $tpl->draw('index');
                        exit();
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                    $tpl->assign('block_error', true);
                    $tpl->draw('index');
                    exit();
                }
            }
            else {
                header('location: index.php?'.$get_redir);
                exit();
            }
            break;

        case 'confirm_payback':
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['invoice_id']) && $_GET['from'] != $_GET['to']) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    if(check_token(600, 'invoice')) {
                        $invoice = new Invoice();
                        $invoice = $invoice->load(array('id'=>(int) $_GET['invoice_id']), true);

                        $payback = new Payback();

                        if(!empty($_GET['payback_id'])) {
                            $payback = $payback->load(array('id'=>(int) $_GET['payback_id']), true);

                            if($payback->getFrom() != $_GET['from'] || $payback->getTo() != $_GET['to']) {
                                $payback = new Payback();
                            }
                        }
                        else {
                            $payback = $payback->load(array('invoice_id'=>(int) $_GET['invoice_id'], 'to_user'=>(int) $_GET['to'], 'from_user'=>(int) $_GET['from']), true);

                            if($payback == false)
                                $payback = new Payback();
                        }

                        $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
                        $payback->setInvoice($_GET['invoice_id']);
                        $payback->setAmount($invoice->getAmountPerPerson($_GET['from']));
                        $payback->setFrom($_GET['from']);
                        $payback->setTo($_GET['to']);

                        $payback->save();

                        // Clear the cache
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

                        header('location: index.php');
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['token_error']);
                        $tpl->assign('block_error', true);
                        $tpl->draw('index');
                        exit();
                    }

                }
                else {
                    $tpl->assign('error', $errors['unauthorized']);
                    $tpl->assign('block_error', true);
                    $tpl->draw('index');
                    exit();
                }
            }
            else {
                header('location: index.php?'.$get_redir);
            }
            break;

        case 'delete_payback':
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['invoice_id'])) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    if(check_token(600, 'invoice')) {
                        $paybacks = new Payback();

                        $paybacks = $paybacks->load(array('to_user'=>(int) $_GET['to'], 'from_user'=> (int) $_GET['from'], 'invoice_id'=> (int) $_GET['invoice_id']));

                        if($paybacks !== false) {
                            foreach($paybacks as $payback) {
                                $payback->delete();
                            }
                        }

                        // Clear the cache
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

                        header('location: index.php');
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['token_error']);
                        $tpl->assign('block_error', true);
                        $tpl->draw('index');
                        exit();
                    }

                }
                else {
                    header('location: index.php');
                    exit();
                }
            }
            else {
                header('location: index.php');
                exit();
            }
            break;

        case 'payall':
            if(!empty($_GET['from']) && !empty($_GET['to'])) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    if(check_token(600, 'invoice')) {
                        // Confirm all paybacks when to is buyer
                        $invoices = new Invoice();
                        $invoices = $invoices->load(array('buyer'=>(int) $_GET['to']));

                        if($invoices !== false) {
                            foreach($invoices as $invoice) {
                                $paybacks = new Payback();
                                $paybacks = $paybacks->load(array('invoice_id'=>$invoice->getId(), 'to_user'=>(int) $_GET['to'], 'from_user'=>(int) $_GET['from']));

                                if($paybacks === false) {
                                    $payback = new Payback();
                                    $payback->setTo($_GET['to']);
                                    $payback->setFrom($_GET['from']);
                                    $payback->setAmount($invoice->getAmountPerPerson($_GET['from']));
                                    $payback->setInvoice($invoice->getId());
                                    $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
                                    $payback->save();
                                }
                            }
                        }

                        // Confirm all paybacks when from is buyer
                        $invoices = new Invoice();
                        $invoices = $invoices->load(array('buyer'=>(int) $_GET['from']));

                        if($invoices !== false) {
                            foreach($invoices as $invoice) {
                                $paybacks = new Payback();
                                $paybacks = $paybacks->load(array('invoice_id'=>$invoice->getId(), 'to_user'=>(int) $_GET['from'], 'from_user'=>(int) $_GET['to']));

                                if($paybacks === false) {
                                    $payback = new Payback();
                                    $payback->setTo($_GET['from']);
                                    $payback->setFrom($_GET['to']);
                                    $payback->setAmount($invoice->getAmountPerPerson($_GET['to']));
                                    $payback->setInvoice($invoice->getId());
                                    $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
                                    $payback->save();
                                }
                            }
                        }

                        // Clear the cache
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

                        header('location: index.php');
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['token_error']);
                        $tpl->assign('block_error', true);
                        $tpl->draw('index');
                        exit();
                    }

                }
                else {
                    header('location: index.php');
                    exit();
                }

            }
            else {
                header('location: index.php');
                exit();
            }
            break;

        case "see_paybacks":
            $global_paybacks = new GlobalPayback();

            if(empty($_GET['id'])) {
                $global_paybacks = $global_paybacks->load();

                if($global_paybacks !== false) {
                    $sort_keys = array();
                    foreach($global_paybacks as $key=>$entry) {
                        $sort_keys[$key] = $entry->getId();
                    }
                    array_multisort($sort_keys, SORT_DESC, $global_paybacks);
                }
            }
            else {
                $global_paybacks = $global_paybacks->load(array('id'=>(int) $_GET['id']), true);
                $tpl->assign('id', (int) $_GET['id']);

                $users_list = new User();
                $users_list = $users_list->load();

                $tpl->assign('users', $users_list);
            }

            $tpl->assign('list', true);
            $tpl->assign('global_paybacks', $global_paybacks);
            $tpl->assign('token', generate_token('global_payback'));

            $tpl->draw('see_paybacks');
            break;

        case "confirm_global_paybacks":
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['payback_id']) && $_GET['from'] != $_GET['to']) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    if(check_token(600, 'global_payback')) {
                        $global_payback = new GlobalPayback();
                        $global_payback = $global_payback->load(array('id'=>(int) $_GET['payback_id']), true);

                        $users_in = $global_payback->getUsersIn()->get();

                        $users_in[(int) $_GET['from']][(int) $_GET['to']] = 0;
                        $users_in[(int) $_GET['to']][(int) $_GET['from']] = 0;

                        $global_payback->setUsersIn($users_in);

                        if($global_payback->getUsersIn()->isEmpty()) {
                            $global_payback->setClosed(true);
                        }
                        else {
                            $global_payback->setClosed(false);
                        }

                        $global_payback->save();

                        // Clear the cache
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

                        header('location: ?do=see_paybacks&id='.(int)$_GET['payback_id']);
                        exit();
                    }
                    else {
                        header('location: index.php');
                        exit();
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error']);
                    $tpl->assign('block_error', true);
                    $tpl->draw('index');
                    exit();
                }
            }
            else {
                header('location: index.php?'.$get_redir);
            }
            break;

        case "manage_paybacks":
            if(empty($_GET['new'])) {
                $global_paybacks = new GlobalPayback();
                $global_paybacks = $global_paybacks->load();

                // Sort paybacks by id DESC
                if($global_paybacks !== false) {
                    $sort_keys = array();
                    foreach($global_paybacks as $key=>$entry) {
                        $sort_keys[$key] = $entry->getId();
                    }
                    array_multisort($sort_keys, SORT_DESC, $global_paybacks);
                }

                $tpl->assign('list', true);
                $tpl->assign('global_paybacks', $global_paybacks);
            }
            else {
                if(!empty($_POST['users_in']) && count($_POST['users_in']) > 1) {
                    if(check_token(600, 'global_payback')) {
                        $global_payback = new GlobalPayback();

                        // Backup database
                        if(!is_dir('db_backups')) {
                            mkdir('db_backups');
                        }
                        if(!is_writeable('db_backups')) {
                            $tpl->assign('error', $errors['write_error_db_backups']);
                            $tpl->assign('block_error', true);
                            $tpl->draw('index');
                            exit();
                        }
                        else {
                            if(system(escapeshellcmd("mysqldump -q -h \"".MYSQL_HOST."\" -u \"".MYSQL_LOGIN."\" -p\"".MYSQL_PASSWORD."\" \"".MYSQL_DB."\" > db_backups/".date('d-m-Y_H:i'))) === FALSE) {
		                        $tpl->assign('error', $errors['db_dump_failed']);
		                        $tpl->assign('block_error', true);
		                        $tpl->draw('index');
		                        exit();
                            }
                            else {
		                        $users_in = array();
		                        foreach($_POST['users_in'] as $user1_id) {
		                            $user1_id = intval($user1_id);
		                            foreach($_POST['users_in'] as $user2_id) {
		                                $user2_id = intval($user2_id);
		                                if($user1_id == $user2_id) {
		                                    $users_in[$user1_id][$user2_id] = 0;
		                                }
		                                elseif(!empty($users_in[$user2_id][$user1_id])) {
		                                    if($users_in[$user2_id][$user1_id] > 0) {
		                                        $users_in[$user1_id][$user2_id] = 0;
		                                    }
		                                    else {
		                                        $users_in[$user1_id][$user2_id] = -$users_in[$user2_id][$user1_id];
		                                        $users_in[$user2_id][$user1_id] = 0;
		                                    }
		                                }
		                                else {
		                                    // Get the amount user1 owes to user2
		                                    $users_in[$user1_id][$user2_id] = 0;

		                                    // Confirm all paybacks when user2 is buyer
		                                    $invoices = new Invoice();
		                                    $invoices = $invoices->load(array('buyer'=>$user2_id));

		                                    if($invoices !== false) {
		                                        foreach($invoices as $invoice) {
		                                            if($invoice->getAmountPerPerson($user1_id) !== false) {
		                                                $paybacks = new Payback();
		                                                $paybacks = $paybacks->load(array('invoice_id'=>$invoice->getId(), 'to_user'=>$user2_id, 'from_user'=>$user1_id));

		                                                if($paybacks === false) {
		                                                    $payback = new Payback();
		                                                    $payback->setTo($user2_id);
		                                                    $payback->setFrom($user1_id);
		                                                    $payback->setAmount($invoice->getAmountPerPerson($user1_id));
		                                                    $payback->setInvoice($invoice->getId());
		                                                    $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
		                                                    $payback->save();

		                                                    // Add the amount to what user1 owes to user2
		                                                    $users_in[$user1_id][$user2_id] += $payback->getAmount();
		                                                }
		                                            }
		                                        }
		                                    }

		                                    // Confirm all paybacks when from is buyer
		                                    $invoices = new Invoice();
		                                    $invoices = $invoices->load(array('buyer'=>$user1_id));

		                                    if($invoices !== false) {
		                                        foreach($invoices as $invoice) {
		                                            if($invoice->getAmountPerPerson($user2_id) !== false) {
		                                                $paybacks = new Payback();
		                                                $paybacks = $paybacks->load(array('invoice_id'=>$invoice->getId(), 'to_user'=>$user1_id, 'from_user'=>$user2_id));

		                                                if($paybacks === false) {
		                                                    $payback = new Payback();
		                                                    $payback->setTo($user1_id);
		                                                    $payback->setFrom($user2_id);
		                                                    $payback->setAmount($invoice->getAmountPerPerson($user2_id));
		                                                    $payback->setInvoice($invoice->getId());
		                                                    $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
		                                                    $payback->save();

		                                                    // Substract the amount to what user1 owes to user2
		                                                    $users_in[$user1_id][$user2_id] -= $payback->getAmount();
		                                                }
		                                            }
		                                        }
		                                    }
		                                }
		                            }
		                        }

		                        // Now, let's simplify the matrix ! :)
		                        // First, get the total balance by user (gains - debts)
		                        $balances = array();
		                        $simplified_balances = array();
		                        foreach($_POST['users_in'] as $user) {
		                            $balances[$user] = 0;
		                            foreach($_POST['users_in'] as $user2) {
		                                if(!empty($users_in[$user][$user2])) {
		                                    $balances[$user] -= $users_in[$user][$user2];
		                                }
		                                if(!empty($users_in[$user2][$user])) {
		                                    $balances[$user] += $users_in[$user2][$user];
		                                }
		                                $simplified_balances[$user][$user2] = 0;
		                            }
		                        }

		                        // Round at 0.01 currency
		                        foreach($balances as $key=>$balance) {
		                            $balances[$key] = round($balance, 2);
		                        }

		                        // Do while $balances is not identically filled with zeros
		                        $i = 0;
		                        while(count(array_unique($balances)) != 1 or $balances[key($balances)] != 0) {
		                            // Sort balances in abs values, desc
		                            uasort($balances, "sort_array_abs");

		                            // Get the largest one in abs
		                            // The following largest with opposite sign must pay him back the max
		                            reset($balances);
		                            $user1 = key($balances);

		                            foreach($balances as $user2=>$value) {
		                                if($value * $balances[$user1] < 0) {
		                                    if($balances[$user1] > 0) {
		                                        $simplified_balances[$user2][$user1] = round(abs($value), 2);
		                                        $balances[$user1] = round($balances[$user1] - abs($value), 2);
		                                        $balances[$user2] = round($balances[$user2] + abs($value), 2);
		                                    }
		                                    else {
		                                        $simplified_balances[$user1][$user2] = round(abs($value), 2);
		                                        $balances[$user1] = round($balances[$user1] + abs($value), 2);
		                                        $balances[$user2] = round($balances[$user2] - abs($value), 2);
		                                    }
		                                    break;
		                                }
		                            }
		                        }

		                        $global_payback->setUsersIn($simplified_balances);

		                        if($global_payback->getUsersIn()->isEmpty()) {
		                            $global_payback->setClosed(true);
		                        }
		                        else {
		                            $global_payback->setClosed(false);
		                        }

		                        $global_payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
		                        $global_payback->save();

		                        // Clear the cache
		                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
		                        array_map("unlink", $cached_files);

		                        header('location: index.php?do=manage_paybacks&'.$get_redir);
		                        exit();
		                    }
		              	}
                    }
                    else {
                        $tpl->assign('error', $errors['token_error']);
                        $tpl->assign('block_error', true);
                        $tpl->draw('index');
                        exit();
                    }
                }

                $users_list = new User();
                $users_list = $users_list->load();

                $tpl->assign('users', $users_list);
            }
            $tpl->assign('token', generate_token('global_payback'));
            $tpl->draw('manage_paybacks');
            break;


        default:
            if(empty($_GET['all']))
                $_GET['all'] = 0;

            // Display cached page in priority
            if($cache = $tpl->cache('index', $expire_time = 600, $cache_id = $current_user->getLogin().$_GET['all'])) {
                echo $cache;
            }
            else {
                $users_list = new User();
                $users_list = $users_list->load();

                $invoices_list = new Invoice();
                if(empty($_GET['all'])) {
                    $invoices_list = $invoices_list->load(array('date'=>array('>='.date('Y-m').'-01 00:00:00', 'AND', '<='.date('Y-m').'-31 23:59:59')));
                    $tpl->assign('all', 0);
                }
                else {
                    $invoices_list = $invoices_list->load();
                    $tpl->assign('all', 1);
                }

                // Only keep the invoices which concern the user (as buyer or user in) (only if user != admin)
                // TODO : Optimize ?
                if(!$current_user->getAdmin() && $invoices_list !== false) {
                    foreach($invoices_list as $key=>$invoice) {
                        if($invoice->getBuyer() != $current_user->getId() && !$invoice->getUsersIn()->inUsersIn($current_user->getId())) {
                            unset($invoices_list[$key]);
                        }
                    }
                }


                if($invoices_list === false) $invoices_list = array();
                else {
                    $sort_keys = array();
                    foreach($invoices_list as $key=>$entry) {
                        $sort_keys[$key] = $entry->getDate();
                    }
                    array_multisort($sort_keys, SORT_DESC, $invoices_list);
                }

                $paybacks = array();
                foreach($invoices_list as $invoice) {
                    $paybacks[$invoice->getId()] = new Payback();
                    $paybacks[$invoice->getId()] = $paybacks[$invoice->getId()]->load(array('invoice_id'=>$invoice->getId()), false, 'from_user');
                }

                $balances = array();
                foreach($users_list as $user1) {
                    foreach($users_list as $user2) {
                        if($user1->getId() == $user2->getId()) {
                            $balances[$user1->getId()][$user2->getId()] = 'X';
                        }
                        if(!empty($balances[$user2->getId()][$user1->getId()])) {
                            // If the opposed element in the matrix exists
                            if(is_float($balances[$user2->getId()][$user1->getId()])) {
                                if($balances[$user2->getId()][$user1->getId()] >= 0) {
                                    $balances[$user1->getId()][$user2->getId()] = '-';
                                }
                                else {
                                    $balances[$user1->getId()][$user2->getId()] = -$balances[$user2->getId()][$user1->getId()];
                                    $balances[$user2->getId()][$user1->getId()] = '-';
                                }
                            }
                        }
                        else {
                            // TODO : Optimize ?
                            $balances[$user1->getId()][$user2->getId()] = 0;

                            // First, get a list of all invoices paid by user2 and check if user1 was in
                            $invoices_list_balances = new Invoice();
                            $invoices_list_balances = $invoices_list_balances->load(array('buyer'=>$user2->getId()));
                            if($invoices_list_balances !== false) {
                                foreach($invoices_list_balances as $invoice) {
                                    if($invoice->getUsersIn()->inUsersIn($user1->getId())) {
                                        $balances[$user1->getId()][$user2->getId()] = $balances[$user1->getId()][$user2->getId()] + $invoice->getAmountPerPerson($user1->getId(), false);

                                        $payback_balance = new Payback();
                                        $payback_balance = $payback_balance->load(array('invoice_id'=>$invoice->getId(), 'from_user'=>$user1->getId(), 'to_user'=>$user2->getId()), true);
                                        if($payback_balance !== false)
                                            $balances[$user1->getId()][$user2->getId()] = $balances[$user1->getId()][$user2->getId()] - $payback_balance->getAmount();
                                    }
                                }
                            }

                            // Then search for all invoices paid by 1 and check if user2 was in
                            $invoices_list_balances = new Invoice();
                            $invoices_list_balances = $invoices_list_balances->load(array('buyer'=>$user1->getId()));
                            if($invoices_list_balances !== false) {
                                foreach($invoices_list_balances as $invoice) {
                                    if($invoice->getUsersIn()->inUsersIn($user2->getId())) {
                                        $balances[$user1->getId()][$user2->getId()] = $balances[$user1->getId()][$user2->getId()] - $invoice->getAmountPerPerson($user2->getId(), false);

                                        $payback_balance = new Payback();
                                        $payback_balance = $payback_balance->load(array('invoice_id'=>$invoice->getId(), 'from_user'=>$user2->getId(), 'to_user'=>$user1->getId()), true);
                                        if($payback_balance !== false)
                                            $balances[$user1->getId()][$user2->getId()] = $balances[$user1->getId()][$user2->getId()] + $payback_balance->getAmount();
                                    }
                                }
                            }

                            if(abs($balances[$user1->getId()][$user2->getId()]) < 0.01) {
                                $balances[$user1->getId()][$user2->getId()] = '-';
                                $balances[$user2->getId()][$user1->getId()] = '-';
                            }
                        }
                    }
                }
                foreach($users_list as $user1) {
                    foreach($users_list as $user2) {
                    	if($balances[$user1->getId()][$user2->getId()] != '-' && $balances[$user1->getId()][$user2->getId()] != 'X')
                    		$balances[$user1->getId()][$user2->getId()] = round($balances[$user1->getId()][$user2->getId()], 2);
                   	}
              	}

                if(!$current_user->getAdmin()) {
                    $user_balance = 0;
                    foreach($users_list as $user1) {
                        $user_balance = $user_balance - $balances[$current_user->getId()][$user1->getId()];
                        $user_balance = $user_balance + $balances[$user1->getId()][$current_user->getId()];
                    }

                    $tpl->assign('user_balance', round($user_balance, 2));
                }

                $tpl->assign('users', secureDisplay($users_list));
                $tpl->assign('invoices', secureDisplay($invoices_list));
                $tpl->assign('paybacks', secureDisplay($paybacks));
                $tpl->assign('balances', secureDisplay($balances));

                $tpl->assign('token', generate_token('invoice'));


                // Cache the page (1 month to make it almost permanent and only regenerate it upon new invoice)
                $tpl->cache('index', 108000, $current_user->getLogin().$_GET['all']);

                $tpl->draw('index');
                break;
            }
    }
