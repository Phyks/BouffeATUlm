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
        'unauthorized'=>array('fr'=>'Vous n\'avez pas le droit de faire cette action.', 'en'=>'You are not authorized to do that.'),
        'no_users'=>array('fr'=>'Vous devez ajouter au moins un autre utilisateur.', 'en'=>'You must add at least one more user beside you.')
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
    require_once('inc/GlobalPaybacks.class.php');
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
                        ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                        array_map("unlink", $cached_files);

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

            if(checkToken(600, 'password')) {  
                $user = new User();
                $user = $user->load(array('id'=>$user_id), true);
                $user->newJsonToken();
                $user->save();
                $_SESSION['current_user'] = $user->sessionStore();
                
                header('location: index.php?do=password&'.$get_redir);
                exit();
            }
            else {
                $tpl->assign('error', $errors['token_error'][LANG]);
                $tpl->draw('index');
            }
            break;

        case 'delete_user':
            if($_GET['user_id'] != $current_user->getId()) {
                if(checkToken(600, 'edit_users')) {
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
                    $tpl->assign('error', $errors['token_error'][LANG]);
                    $tpl->draw('index');
                }
            }
            break;

        case 'edit_notice':
            if(isset($_POST['notice'])) {
                if(checkToken(600, 'settings')) {
                    setNotice($_POST['notice']);

                    // Clear the cache
                    ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                    array_map("unlink", $cached_files);
        
                    header('location: index.php?'.$get_redir);
                    exit();
                }
                else {
                    $tpl->assign('error', $errors['token_error'][LANG]);
                }
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
                                if(strpos(trim($line), "MYSQL_HOST") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_HOST', '".$_POST['mysql_host']."');";
                                elseif(strpos(trim($line), "MYSQL_LOGIN") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_LOGIN', '".$_POST['mysql_login']."');";
                                elseif(strpos(trim($line), "MYSQL_PASSWORD") !== false && !empty($_POST['mysql_password']))
                                    $config[$line_number] = "\tdefine('MYSQL_PASSWORD', '".$_POST['mysql_password']."');";
                                elseif(strpos(trim($line), "MYSQL_DB") !== false)
                                    $config[$line_number] = "\tdefine('MYSQL_DB', '".$_POST['mysql_db']."');";
                                elseif(strpos(trim($line), "MYSQL_PREFIX") !== false && !empty($_POST['mysql_prefix']))
                                    $config[$line_number] = "\tdefine('MYSQL_PREFIX', '".$_POST['mysql_prefix']."');";
                                elseif(strpos(trim($line), "INSTANCE_TITLE") !== false)
                                    $config[$line_number] = "\tdefine('INSTANCE_TITLE', '".$_POST['instance_title']."');";
                                elseif(strpos(trim($line), "BASE_URL") !== false)
                                    $config[$line_number] = "\tdefine('BASE_URL', '".$_POST['base_url']."');";
                                elseif(strpos(trim($line), "CURRENCY") !== false)
                                    $config[$line_number] = "\tdefine('CURRENCY', '".$_POST['currency']."');";
                                elseif(strpos(trim($line), "EMAIL_WEBMASTER") !== false)
                                    $config[$line_number] = "\tdefine('EMAIL_WEBMASTER', '".$_POST['email_webmaster']."');";
                                elseif(strpos(trim($line), "TEMPLATE_DIR") !== false)
                                    $config[$line_number] = "\tdefine('TEMPLATE_DIR', 'tpl/".$_POST['template']."/');";
                                elseif(strpos(trim($line), "LANG") !== false)
                                    $config[$line_number] = "\tdefine('LANG', '".substr($_POST['template'], -2)."');";
                                elseif(strpos(trim($line), 'date_default_timezone_set') !== false)
                                    $config[$line_number] = "\tdate_default_timezone_set('".$_POST['timezone']."');";
                            }

                            if(file_put_contents("data/config.php", $config)) {
                                // Clear the cache
                                ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                                array_map("unlink", $cached_files);

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
                        if(array_keys($users_in) == array($current_user->getId())) {
                            $tpl->assign('error', $errors['no_users'][LANG]);
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
                            ($cached_files = glob(raintpl::$cache_dir."*.rtpl.php")) or ($cached_files = array());
                            array_map("unlink", $cached_files);

                            header('location: index.php?'.$get_redir);
                            exit();
                        }
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
                if(checkToken(600, 'invoice')) {
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
                        $tpl->assign('error', $errors['unauthorized'][LANG]);
                        $tpl->draw('index');
                    }
                }
                else {
                    $tpl->assign('error', $errors['token_error'][LANG]);
                    $tpl->draw('index');
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
                    if(checkToken(600, 'invoice')) {
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
                        $tpl->assign('error', $errors['token_error'][LANG]);
                        $tpl->draw('index');
                    }

                }
                else {
                    $tpl->assign('error', $errors['unauthorized'][LANG]);
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
                    if(checkToken(600, 'invoice')) {
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
                        $tpl->assign('error', $errors['token_error'][LANG]);
                        $tpl->draw('index');
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
                    if(checkToken(600, 'invoice')) {
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
                        $tpl->assign('error', $errors['token_error'][LANG]);
                        $tpl->draw('index');
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
            $tpl->assign('payback', generateToken('global_payback'));

            $tpl->draw('see_paybacks');
            break;

        case "confirm_global_paybacks":
            if(!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['payback_id']) && $_GET['from'] != $_GET['to']) {
                if($_GET['to'] == $current_user->getId() || $current_user->getAdmin()) {
                    if(checkToken(600, 'global_payback')) {
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
                    $tpl->assign('error', $errors['token_error'][LANG]);
                    $tpl->draw('index');
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
                if(!empty($_POST['users_in'])) {
                    if(checkToken(600, 'global_payback')) {
                        $global_payback = new GlobalPayback();

                        // Backup database
                        if(!is_dir('db_backups')) {
                            mkdir('db_backups');
                        }
                        system("mysqldump -h ".MYSQL_HOST." -u ".MYSQL_LOGIN." -p ".MYSQL_PASSWORD." ".MYSQL_DB." > db_backups/".date('d-m-Y_H:i'));

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

                                    // Confirm all paybacks when from is buyer
                                    $invoices = new Invoice();
                                    $invoices = $invoices->load(array('buyer'=>$user1_id));

                                    if($invoices !== false) {
                                        foreach($invoices as $invoice) {
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

                        $global_payback->setUsersIn($users_in);

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
                    else {
                        $tpl->assign('error', $errors['token_error'][LANG]);
                        $tpl->draw('index');
                    }
                }
                
                $users_list = new User();
                $users_list = $users_list->load();

                $tpl->assign('users', $users_list);
            }
            $tpl->assign('payback', generateToken('global_payback'));
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

                if($invoices_list === false) $invoices_list = array();

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
                                        $balances[$user1->getId()][$user2->getId()] += $invoice->getAmountPerPerson($user1->getId());

                                        $payback_balance = new Payback();
                                        $payback_balance = $payback_balance->load(array('invoice_id'=>$invoice->getId(), 'from_user'=>$user1->getId(), 'to_user'=>$user2->getId()), true);
                                        if($payback_balance !== false)
                                            $balances[$user1->getId()][$user2->getId()] -= $payback_balance->getAmount();
                                    }
                                }
                            }

                            // Then search for all invoices paid by 1 and check if user2 was in 
                            $invoices_list_balances = new Invoice();
                            $invoices_list_balances = $invoices_list_balances->load(array('buyer'=>$user1->getId()));
                            if($invoices_list_balances !== false) {
                                foreach($invoices_list_balances as $invoice) {
                                    if($invoice->getUsersIn()->inUsersIn($user2->getId())) {
                                        $balances[$user1->getId()][$user2->getId()] -= $invoice->getAmountPerPerson($user2->getId());

                                        $payback_balance = new Payback();
                                        $payback_balance = $payback_balance->load(array('invoice_id'=>$invoice->getId(), 'from_user'=>$user2->getId(), 'to_user'=>$user1->getId()), true);
                                        if($payback_balance !== false)
                                            $balances[$user1->getId()][$user2->getId()] += $payback_balance->getAmount();
                                    }
                                }
                            }

                            if($balances[$user1->getId()][$user2->getId()] == 0) {
                                $balances[$user1->getId()][$user2->getId()] = '-';
                                $balances[$user2->getId()][$user1->getId()] = '-';
                            }
                        }
                    }
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
