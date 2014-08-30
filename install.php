<?php
    require_once('inc/CSRF.inc.php');
    require_once('inc/functions.php');

    if(file_exists('data/config.php')) exit('<p>Your Bouffe@Ulm instance is already configured. You should either delete data/config.php to access this page or delete the install.php for security reasons if you are ok with the configuration.<br/><a href="index.php">Go to your instance</a>.</p>');

    if(!function_exists("file_get_contents") && !function_exists("file_put_contents")) {
        $error = "Functions <em>file_get_contents</em> and <em>file_put_contents</em> seems to not be available on your PHP installation. You should enable them first.";
        $block_form = true;
    }

    if(!is_writable('data/')) {
        $error = "The script seems to be unable to write to <em>data/</em> folder (to write the <em>data/config.php</em> configuration file). You should give write access during install and disable them after (chmod 777 -R data/ to install and chmod 755 -R data/ after installation for example). You'll need right access on this folder each time you will want to edit settings.";
        $block_form = true;
    }
    if(!is_writable('tmp/')) {
        $error = "The script seems to be unable to write to <em>tmp/</em> folder (to store the cached files for templates). You should give write access to this folder.";
        $block_form = true;
    }
    if(!is_writable('db_backups/')) {
        $error = "The script seems to be unable to write to <em>db_backups/</em> folder (to write the database backups). You should give write access to this folder.";
        $block_form = true;
    }

    if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_password']) && !empty($_POST['mysql_db']) && !empty($_POST['instance_title']) && !empty($_POST['base_url']) && !empty($_POST['currency']) && !empty($_POST['timezone']) && !empty($_POST['lang']) && !empty($_POST['template']) && !empty($_POST['admin_login']) && !empty($_POST['admin_password']) && check_token(600, 'install')) {
        $mysql_prefix = (!empty($_POST['mysql_prefix'])) ? $_POST['mysql_prefix'] : '';
        $current_template = $_POST['template'];

        try {
            $db = new PDO('mysql:host='.$_POST['mysql_host'].';dbname='.$_POST['mysql_db'], $_POST['mysql_login'], $_POST['mysql_password']);

            //Create table "Users"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Users (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                login VARCHAR(255),
                email VARCHAR(255),
                display_name VARCHAR(255),
                password VARCHAR(130),
                admin TINYINT(1),
                json_token VARCHAR(32),
                notifications TINYINT(1),
                stay_signed_in_token VARCHAR(32),
                UNIQUE (login),
                UNIQUE (display_name),
                UNIQUE (json_token),
                UNIQUE (stay_signed_in_token)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            //Create table "Invoices"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Invoices (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                date DATETIME,
                buyer INT(11),
                FOREIGN KEY (buyer) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
                amount INT(11),
                what TEXT
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            //Create table "Users_in_invoices"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Users_in_invoices (
                invoice_id INT(11) NOT NULL,
                FOREIGN KEY (invoice_id) REFERENCES '.$mysql_prefix.'Invoices(id) ON DELETE CASCADE,
                user_id INT(11),
                FOREIGN KEY (user_id) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
                guests INT(11)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            //Create table "Paybacks"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Paybacks (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                date DATETIME,
                invoice_id INT(11),
                FOREIGN KEY (invoice_id) REFERENCES '.$mysql_prefix.'Invoices(id) ON DELETE CASCADE,
                amount INT(11),
                from_user INT(11),
                FOREIGN KEY (from_user) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
                to_user INT(11)
                FOREIGN KEY (to_user) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            // Create table "GlobalPaybacks"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'GlobalPaybacks (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                date DATETIME,
                closed TINYINT(1)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            // Create table "Users_in_GlobalPaybacks"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Users_in_GlobalPaybacks (
                global_payback_id INT(11) NOT NULL,
                FOREIGN KEY (global_payback_id) REFERENCES '.$mysql_prefix.'GlobalPaybacks(id) ON DELETE CASCADE,
                user1_id INT(11),
                FOREIGN KEY (user1_id) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
                user2_id INT(11),
                FOREIGN KEY (user2_id) REFERENCES '.$mysql_prefix.'Users(id) ON DELETE CASCADE,
                amount INT(11)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

        } catch (PDOException $e) {
            $error = 'Unable to connect to database and create database, check your credentials and config.<br/>Error message: '.$e->getMessage().'.';
        }

        if(!empty($_POST['email_webmaster']) && !filter_var($_POST['email_webmaster'], FILTER_VALIDATE_EMAIL)) {
            $error = "Webmaster's email address is invalid.";
        }

        if(empty($error)) {
            if(function_exists('mcrypt_create_iv')) {
                $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
            }
            else {
                mt_srand(microtime(true)*100000 + memory_get_usage(true));
                $salt = md5(uniqid(mt_rand(), true));
            }
            $salt = sprintf("$2a$%02d$", 10) . $salt; //prefix for blowfish

            $config = "<?php
    define('VERSION_NUMBER', '0.1beta');
    define('MYSQL_HOST', '".$_POST['mysql_host']."');
    define('MYSQL_LOGIN', '".$_POST['mysql_login']."');
    define('MYSQL_PASSWORD', '".$_POST['mysql_password']."');
    define('MYSQL_DB', '".$_POST['mysql_db']."');
    define('MYSQL_PREFIX', '".$mysql_prefix."');
    define('INSTANCE_TITLE', '".$_POST['instance_title']."');
    define('BASE_URL', '".$_POST['base_url']."');
    define('SALT', '".$salt."');
    define('CURRENCY', '".$_POST['currency']."');
    define('EMAIL_WEBMASTER', '".$_POST['email_webmaster']."');
    define('TEMPLATE_DIR', 'tpl/".$_POST['template']."');
    define('LANG', '".$_POST['lang']."');

    date_default_timezone_set('".$_POST['timezone']."');
    ";

            if(file_put_contents("data/config.php", $config) !== false && file_put_contents("data/notice", '') !== false) {
                try {
                    require_once('inc/User.class.php');
                    $admin = new User();
                    $admin->setLogin($_POST['admin_login']);
                    $admin->setDisplayName(!empty($_POST['admin_display_name']) ? $_POST['admin_display_name'] : '');
                    $admin->setPassword($admin->encrypt($_POST['admin_password']));
                    $admin->setAdmin(true);
                    $admin->setEmail($email_webmaster);
                    $admin->setStaySignedInToken("");
                    $admin->setNotifications(3);
                    $admin->newJsonToken();
                    $admin->save();

                    header('location: index.php');
                    exit();
                } catch (Exception $e) {
                    $error = 'An error occurred when inserting user in the database.<br/> Error message: '.$e->getMessage().'.';
                }
            }
            else {
                $error = 'Unable to write configuration to config file data/config.php.';
            }
        }
    }
    else {
        $current_template = 'default';
    }

    $token = generate_token('install');
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Bouffe@Ulm - Installation</title>
        <link rel="stylesheet" media="screen" type="text/css" href="tpl/default/css/style.css" />
        <script type="text/javascript" src="tpl/default/js/main.js"></script>
    </head>
    <body id="install">
        <h1 class="center">Bouffe@Ulm - Installation</h1>

        <?php
            if(!empty($error)) {
                echo '<p class="error">'.$error.'</p>';
            }
        ?>

        <p class="center">This small form will guide you through the installation of Bouffe@Ulm. You must fill in all the fields.</p>

        <form action="install.php" method="post">
            <fieldset>
                <legend>Database</legend>
                <p><label for="mysql_host">MySQL host: </label><input type="text" name="mysql_host" id="mysql_host" value="<?php echo (!empty($_POST['mysql_host'])) ? htmlspecialchars($_POST['mysql_host']) : 'localhost';?>"/></p>

                <p><label for="mysql_login">MySQL login: </label><input type="text" name="mysql_login" id="mysql_login" value="<?php echo (!empty($_POST['mysql_login'])) ? htmlspecialchars($_POST['mysql_login']) : '';?>"/></p>
                <p><label for="mysql_password">MySQL password: </label><input type="password" name="mysql_password" id="mysql_password"/> <a title="Toggle visible" href="#" onclick="toggle_password('mysql_password'); return false;"><img src="tpl/default/img/toggleVisible.png" alt="Toggle visible"/></a></p>
                <p>
                <label for="mysql_db">Name of the MySQL database to use: </label><input type="text" name="mysql_db" id="mysql_db" value="<?php echo (!empty($_POST['mysql_db'])) ? htmlspecialchars($_POST['mysql_db']) : 'BouffeATUlm';?>"/><br/>
                    <em>Note:</em> You <em>must</em> create this database first.
                </p>
                <p><label for="mysql_prefix">Prefix for the created tables: </label><input type="text" name="mysql_prefix" id="mysql_prefix" value="<?php echo (!empty($_POST['mysql_prefix'])) ? htmlspecialchars($_POST['mysql_prefix']) : 'bouffeatulm_';?>"/><br/>
                    <em>Note:</em> Leave the field blank to not use any.</p>
            </fieldset>
            <fieldset>
                <legend>General options</legend>
                <p><label for="instance_title">Title to display in pages: </label><input type="text" name="instance_title" id="instance_title" value="<?php echo (!empty($_POST['instance_title'])) ? htmlspecialchars($_POST['instance_title']) : 'Bouffe@Ulm';?>"/></p>
                <p>
                    <label for="base_url">Base URL: </label><input type="text" size="30" name="base_url" id="base_url" value="<?php echo (!empty($_POST['base_url'])) ? htmlspecialchars($_POST['base_url']) : htmlspecialchars('http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'].str_replace("install.php", "", $_SERVER['REQUEST_URI'])); ?>"/><br/>
                    <em>Note:</em> This is the base URL from which you access this page. You must keep the trailing "/" in the above address.
                </p>
                <p><label for="currency">Currency: </label><input type="text" name="currency" id="currency" size="3" value="<?php echo (!empty($_POST['currency']) ? htmlspecialchars($_POST['currency']) : '€');?>"/></p>
                <p>
                    <label for="timezone">Timezone: </label><input type="text" name="timezone" id="timezone" value="<?php echo htmlspecialchars(@date_default_timezone_get());?>"/><br/>
                    <em>For example:</em> Europe/Paris. See the doc for more info.
                </p>
                <p><label for="email_webmaster">Webmaster's email (optionnal): </label><input type="text" name="email_webmaster" id="email_webmaster" <?php echo (!empty($_POST['currency']) ? 'value="'.htmlspecialchars($_POST['email_webmaster']).'"' : '');?>/></p>
                <p><label for="lang">Lang: </label><select name="lang" id="lang"><option value="en">English</option><option value="fr">French</option></select></p>
                <p>
                    <label for="template">Template : </label>
                    <select name="template" id="template">
                        <?php
                            foreach (listTemplates('tpl/') as $tpl) {
                        ?>
                                <option value="<?php echo $tpl['value'];?>" <?php if ($tpl['value'] == $current_template) { echo 'selected="selected"'; }?>><?php echo $tpl['option']; ?></option>
                        <?php
                            }
                        ?>
                    </select>
            </fieldset>
            <fieldset>
                <legend>Administrator</legend>
                <p><label for="admin_login">Admin username: </label><input type="text" name="admin_login" id="admin_login" <?php echo (!empty($_POST['admin_login'])) ? 'value="'.htmlspecialchars($_POST['admin_login']).'"' : '';?>/></p>
                <p><label for="admin_display_name">Admin displayed name: </label><input type="text" name="admin_display_name" id="admin_display_name" <?php echo (!empty($_POST['admin_display_name']) ? 'value="'.htmlspecialchars($_POST['admin_display_name']).'"' : '');?>/> (Leave empty to use the login)</p>
                <p><label for="admin_password">Admin password: </label><input type="password" name="admin_password" id="admin_password"/> <a href="#" title="Toggle visible" onclick="toggle_password('admin_password'); return false;"><img src="tpl/default/img/toggleVisible.png" alt="Toggle visible"/></a></p>
            </fieldset>
            <p class="center"><input <?php echo (!empty($block_form)) ? 'disabled ' : '';?>type="submit" value="Install"><input type="hidden" name="token" value="<?php echo $token;?>"/></p>
        </form>
    </body>
</html>
