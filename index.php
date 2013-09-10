<?php
    // Error translation
    $errors = array(
        'unknown_username_password'=>array('fr'=>'Nom d\'utilisateur ou mot de passe inconnu.', 'en'=>'Unknown username or password.'),
        'token_error'=>array('fr'=>'Erreur de token. Veuillez réessayer.', 'en'=>'Token error. Please resubmit the form.'),
        'password_mismatch'=>array('fr'=>'Les deux mots de passe ne correspondent pas.', 'en'=>'The content of the two passwords fields doesn\'t match.'),
        'user_already_exists'=>array('fr'=>'Un utilisateur avec le même login ou nom d\'affichage existe déjà. Choisissez un login ou un nom d\'affichage différent.', 'en'=>'A user with the same login or display name already exists. Choose a different login or display name.'),
        'write_error_data'=>array('fr'=>'Le script ne peut pas écrire dans le dossier data/, vérifiez les permissions sur ce dossier.', 'en'=>'The script can\'t write in data/ dir, check permissions set on this folder.'),
        'unable_write_config'=>array('fr'=>'Impossible d\'écrire le fichier data/config.php. Vérifiez les permissions.', 'en'=>'Unable to write data/config.php file. Check permissions.'),
        'negative_amount'=>array('fr'=>'Montant négatif non autorisé.', 'en'=>'Negative amount not allowed.'),
        'template_error'=>array('fr'=>'Template non disponible.', 'en'=>'Template not available.'),
        'unauthorized'=>array('fr'=>'Vous n\'avez pas le droit de faire cette action.', 'en'=>'You are not authorized to do that.')
    );

    $localized = array(
        'guest'=>array('fr'=>'invité', 'en'=>'guest')
    );

    // Include necessary files
    if(!file_exists('data/config.php')) { header('location: install.php'); exit(); }
    require_once('data/config.php');
    require_once('inc/User.class.php');
    require_once('inc/Invoices.class.php');
    require_once('inc/Paybacks.class.php');
    require_once('inc/rain.tpl.class.php');
    require_once('inc/functions.php');
    require_once('inc/Ban.inc.php');
    require_once('inc/CSRF.inc.php');
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
    
    // Set sessions parameters
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_trans_sid', false);
    session_name('bouffeatulm');

    // Regenerate session if needed
    $cookie = session_get_cookie_params();
    $cookie_dir = ''; if(dirname($_SERVER['SCRIPT_NAME']) != '/') $cookie_dir = dirname($_SERVER['SCRIPT_NAME']);
    session_set_cookie_params($cookie['lifetime'], $cookie_dir, $_SERVER['HTTP_HOST']);
    session_regenerate_id(true);

    // Handle current user status
    if(session_id() == '') session_start();

    $current_user = new User();
    if(isset($_SESSION['current_user'])) {
        $current_user->sessionRestore($_SESSION['current_user'], true);
    }
    else {
        $current_user = false;
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
            session_destroy();
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
                    $error = $errors['unknown_username_password'][LANG];
                }
                else {
                    $user = $user->exists($_POST['login']);
                    if($user !== false && $user->checkPassword($_POST['password'])) {
                        ban_loginOk();
                        $_SESSION['current_user'] = $user->sessionStore();
                        $_SESSION['ip'] = user_ip();

                        if(!empty($_POST['remember_me'])) { // Handle remember me cookie
                            $_SESSION['remember_me'] = 31536000;
                        }
                        else {
                            $_SESSION['remember_me'] = 0;
                        }

                        $cookie_dir = ''; if(dirname($_SERVER['SCRIPT_NAME']) != '/') $cookie_dir = dirname($_SERVER['SCRIPT_NAME']);
                        session_set_cookie_params($_SESSION['remember_me'], $cookie_dir, $_SERVER['HTTP_HOST']);
                        session_regenerate_id(true);

                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                    else {
                        ban_loginFailed();
                        $error = $errors['unknown_username_password'][LANG];
                    }
                }
            }
            $tpl->assign('connection', true);
            $tpl->assign('user_post', (!empty($_POST['login'])) ? htmlspecialchars($_POST['login']) : '');
            $tpl->assign('token', generate_token('connection'));
            $tpl->draw('connection');
            break;

        case 'disconnect':
            $current_user = false;
            session_destroy();
            header('location: index.php?do=connect&'.$get_redir);
            exit();
            break;

        case 'password':
            if(!empty($_POST['password']) && !empty($_POST['password_confirm'])) {
                if($_POST['password'] == $_POST['password_confirm']) {
                    if(check_token(600, 'password')) {
                        $current_user->setPassword($current_user->encrypt($_POST['password']));
                        $current_user->save();

                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['token_error'][LANG]);
                    }
                }
                else {
                    $tpl->assign('error', $errors['password_mismatch'][LANG]);
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

            if(!empty($_POST['login']) && !empty($_POST['display_name']) && (!empty($_POST['password']) || !empty($_POST['user_id'])) && isset($_POST['admin'])) {
                if(check_token(600, 'edit_users')) {
                    $user = new User();
                    if(!empty($_POST['user_id'])) {
                        $user->setId($_POST['user_id']);
                    }
                    else {
                        $user->newJsonToken();
                    }
                    $user->setLogin($_POST['login']);
                    $user->setDisplayName($_POST['display_name']);
                    if(!empty($_POST['password'])) {
                        $user->setPassword($user->encrypt($_POST['password']));
                    }
                    $user->setAdmin($_POST['admin']);

                    if(!empty($_POST['user_id']) || $user->isUnique()) {
                        $user->save();

                        // Clear the cache
                        array_map("unlink", glob(raintpl::$cache_dir."*.rtpl.php"));

                        header('location: index.php?do=edit_users&'.$get_redir);
                        exit();
                    }
                    else {
                        $tpl->assign('error', $errors['user_already_exists'][LANG]);
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error'][LANG]);
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

            $user = new User();
            $user = $user->load(array('id'=>$user_id), true);
            $user->newJsonToken();
            $user->save();
            $_SESSION['current_user'] = $user->sessionStore();

            header('location: index.php'.$get_redir);
            exit();
            break;

        case 'delete_user':
            if($_GET['user_id'] != $current_user->getId()) {
                $user = new User();
                $user->setId($_GET['user_id']);
                $user->delete();

                // Clear the cache
                array_map("unlink", glob(raintpl::$cache_dir."*.rtpl.php"));

                header('location: index.php?do=edit_users&'.$get_redir);
                exit();
            }
            break;

        case 'edit_notice':
            if(isset($_POST['notice'])) {
                setNotice($_POST['notice']);

                // Clear the cache
                array_map("unlink", glob(raintpl::$cache_dir."*.rtpl.php"));
    
                header('location: index.php?'.$get_redir);
                exit();
            }

            $tpl->assign('show_settings', false);
            $tpl->draw('settings');
            break;

        case 'settings':
            if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db']) && !empty($_POST['currency']) && !empty($_POST['instance_title']) && !empty($_POST['base_url']) && !empty($_POST['timezone']) && !empty($_POST['email_webmaster']) && !empty($_POST['template'])) {
                if(check_token(600, 'settings')) {
                    if(!is_writable('data/')) {
                        $tpl>assign('error', $errors['write_error_data'][LANG]);
                    }
                    else {
                        if(!is_dir('tpl/'.$_POST['template'])) {
                            $tpl->assign('error', $errors['template_error'][LANG]);
                        }
                        else {
                            $config = file('data/config.php');

                            foreach($config as $line_number=>$line) {
                                if(strpos($line, "MYSQL_HOST") !== FALSE)
                                    $config[$line_number] = "\tdefine('MYSQL_HOST', '".$_POST['mysql_host']."');\n";
                                elseif(strpos($line, "MYSQL_LOGIN") !== FALSE)
                                    $config[$line_number] = "\tdefine('MYSQL_LOGIN', '".$_POST['mysql_login']."');\n";
                                elseif(strpos($line, "MYSQL_PASSWORD") !== FALSE && !empty($_POST['mysql_password']))
                                    $config[$line_number] = "\tdefine('MYSQL_PASSWORD', '".$_POST['mysql_password']."');\n";
                                elseif(strpos($line, "MYSQL_DB") !== FALSE)
                                    $config[$line_number] = "\tdefine('MYSQL_DB', '".$_POST['mysql_db']."');\n";
                                elseif(strpos($line, "MYSQL_PREFIX") !== FALSE && !empty($_POST['mysql_prefix']))
                                    $config[$line_number] = "\tdefine('MYSQL_PREFIX', '".$_POST['mysql_prefix']."');\n";
                                elseif(strpos($line, "INSTANCE_TITLE") !== FALSE)
                                    $config[$line_number] = "\tdefine('INSTANCE_TITLE', '".$_POST['instance_title']."');\n";
                                elseif(strpos($line, "BASE_URL") !== FALSE)
                                    $config[$line_number] = "\tdefine('BASE_URL', '".$_POST['base_url']."');\n";
                                elseif(strpos($line, "CURRENCY") !== FALSE)
                                    $config[$line_number] = "\tdefine('CURRENCY', '".$_POST['currency']."');\n";
                                elseif(strpos($line, "EMAIL_WEBMASTER") !== FALSE)
                                    $config[$line_number] = "\tdefine('EMAIL_WEBMASTER', '".$_POST['email_webmaster']."');\n";
                                elseif(strpos($line, "TEMPLATE_DIR") !== FALSE)
                                    $config[$line_number] = "\tdefine('TEMPLATE_DIR', 'tpl/".$_POST['template']."/');\n";
                                elseif(strpos($line, "LANG") !== FALSE)
                                    $config[$line_number] = "\tdefine('LANG', '".substr($_POST['template'], -2)."');\n";
                                elseif(strpos($line_number, 'date_default_timezone_set') !== FALSE)
                                    $config[$line_number] = "\tdate_default_timezone_set('".$_POST['timezone']."');\n";
                            }

                            if(file_put_contents("data/config.php", $config)) {
                                // Clear the cache
                                array_map("unlink", glob(raintpl::$cache_dir."*.rtpl.php"));

                                header('location: index.php?'.$get_redir);
                                exit();
                            }
                            else {
                                $tpl->assign('error', $errors['unable_write_config'][LANG]);
                            }
                        }
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error'][LANG]);
                }
            }

            $tpl->assign('mysql_host', htmlspecialchars(MYSQL_HOST));
            $tpl->assign('mysql_login', htmlspecialchars(MYSQL_LOGIN));
            $tpl->assign('mysql_db', htmlspecialchars(MYSQL_DB));
            $tpl->assign('mysql_prefix', htmlspecialchars(MYSQL_PREFIX));
            $tpl->assign('timezone', @date_default_timezone_get());
            $tpl->assign('show_settings', true);
            $tpl->assign('token', generate_token('settings'));
            $tpl->assign('templates', listTemplates('tpl/'));
            $tpl->assign('current_template', trim(substr(TEMPLATE_DIR, 4), '/'));
            $tpl->assign('lang', LANG);
            $tpl->draw('settings');
            break;

        case 'new_invoice':
        case 'edit_invoice':
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

            if(!empty($_POST['what']) && !empty($_POST['amount']) && (float) $_POST['amount'] != 0 && !empty($_POST['date_hour']) && !empty($_POST['date_day']) && !empty($_POST['date_month']) && !empty($_POST['date_year']) && !empty($_POST['users_in'])) {
                if(check_token(600, 'new_invoice')) {
                    if($_POST['amount'] <= 0) {
                        $tpl->assign('error', $errors['negative_amount'][LANG]);
                    }
                    else {
                        $invoice = new Invoice();

                        if(!empty($_POST['id']))
                            $invoice->setId($_POST['id']);

                        $invoice->setWhat($_POST['what']);
                        $invoice->setAmount($_POST['amount']);
                        $invoice->setBuyer($current_user->getId());
                        $invoice->setDate(0, int2ampm($_POST['date_hour']), $_POST['date_day'], $_POST['date_month'], $_POST['date_year']);

                        
                        $invoice->setUsersIn($users_in);

                        $invoice->save();

                        // Clear the cache
                        $tmp_files = glob(raintpl::$cache_dir."*.rtpl.php");
                        if(is_array($tmp_files)) {
                            array_map("unlink", $tmp_files);
                        }

                        header('location: index.php?'.$get_redir);
                        exit();
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error'][LANG]);
                }
            }

            $users_list = new User();
            $users_list = $users_list->load();

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
            $tpl->assign('users_in', (!empty($users_in) ? $users_in : array()));
            $tpl->assign('id', (!empty($_GET['id']) ? (int) $_GET['id'] : 0));
            $tpl->assign('token', generate_token('new_invoice'));
            $tpl->draw('new_invoice');
            break;

        case 'delete_invoice':
            if(!empty($_GET['id'])) {
                $invoice = new Invoice();
                $invoice = $invoice->load(array('id'=>(int) $_GET['id']), true);

                if($current_user->getAdmin() || $invoice->getBuyer() == $current_user->getId()) {
                    $invoice->delete();

                    // Clear the cache
                    array_map("unlink", glob(raintpl::$cache_dir."*.rtpl.php"));

                    header('location: index.php?'.$get_redir);
                    exit();
                }
                else {
                    $tpl->assign('error', $errors['unauthorized']);
                    $tpl->draw('index');
                }
            }
            else {
                header('location: index.php?'.$get_redir);
                exit();
            }
            break;

        case 'confirm_payback':
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['invoice_id'])) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    $invoice = new Invoice();
                    $invoice = $invoice->load(array('id'=>(int) $_GET['invoice_id']), true);

                    $payback = new Payback();

                    if(!empty($_GET['payback_id'])) {
                        $payback = $payback->load(array('id'=>(int) $_GET['payback_id']), true);

                        if($payback->getFrom() != $_GET['from'] || $payback->getTo() != $_GET['to']) {
                            $payback = new Payback();
                        }
                    }

                    $payback->setDate(date('i'), date('G'), date('j'), date('n'), date('Y'));
                    $payback->setInvoice($_GET['invoice_id']);
                    $payback->setAmount($invoice->getAmount());
                    $payback->setFrom($_GET['from']);
                    $payback->setTo($_GET['to']);

                    $payback->save();
                    
                    // Clear the cache
                    $tmp_files = glob(raintpl::$cache_dir."*.rtpl.php");
                    if(is_array($tmp_files)) {
                        array_map("unlink", $tmp_files);
                    }

                    header('location: index.php');
                    exit();

                }
                else {
                    $tpl->assign('error', $errors['unauthorized']);
                    $tpl->draw('index');
                }
            }
            else {
                header('location: index.php?'.$get_redir);
            }
            break;

        case 'delete_payback':
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['invoice_id'])) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    $paybacks = new Payback();

                    $paybacks = $paybacks->load(array('to_user'=>(int) $_GET['to'], 'from_user'=> (int) $_GET['from'], 'invoice_id'=> (int) $_GET['invoice_id']));

                    foreach($paybacks as $payback) {
                        $payback->delete();
                    }

                    // Clear the cache
                    $tmp_files = glob(raintpl::$cache_dir."*.rtpl.php");
                    if(is_array($tmp_files)) {
                        array_map("unlink", $tmp_files);
                    }

                    header('location: index.php');
                    exit();
                }
                else {
                    $tpl->assign('error', $errors['unauthorized']);
                    $tpl->draw('index');
                }
            }
            else {
                header('location: index.php');
                exit();
            }


        default:
            // Display cached page in priority
            if($cache = $tpl->cache('index', $expire_time = 600, $cache_id = $current_user->getLogin())) {
                echo $cache;
            }
            else {
                $users_list = new User();
                $users_list = $users_list->load();

                $invoices_list = new Invoice();
                $invoices_list = $invoices_list->load();

                if($invoices_list === false) $invoices_list = array();

                $paybacks = array();
                foreach($invoices_list as $invoice) {
                    $paybacks[$invoice->getId()] = new Payback();
                    $paybacks[$invoice->getId()] = $paybacks[$invoice->getId()]->load(array('invoice_id'=>$invoice->getId()), false, 'from_user');
                }

                $tpl->assign('users', secureDisplay($users_list));
                $tpl->assign('invoices', secureDisplay($invoices_list));
                $tpl->assign('paybacks', secureDisplay($paybacks));

                // Cache the page (1 month to make it almost permanent and only regenerate it upon new invoice)
                $tpl->cache('index', 108000, $current_user->getLogin());

                $tpl->draw('index');
                break;
            }
    }
