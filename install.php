<?php
    if(file_exists('data/config.php')) exit("Your Bouffe@Ulm instance is already configured. You should either delete data/config.php to access this page or delete the install.php for security reasons if you are ok with the configuration.");

    if(!function_exists("file_get_contents") && !function_exists("file_put_contents")) {
        $error = "Functions <em>file_get_contents</em> and <em>file_put_contents</em> seems to not be available on your PHP installation. You should enable them first.";
        $block_form = true;
    }

    if(!is_writable('data/')) {
        $error = "The script seems to be unable to write to <em>data/</em> folder (to write the <em>data/config.php</em> configuration file). You should give write access during install and disable them after (chmod 777 -R data/ to install and chmod 755 -R data/ after installation for example).";
        $block_form = true;
    }

    if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db']) && !empty($_POST['admin_login']) && !empty($_POST['admin_password']) && !empty($_POST['currency']) && !empty($_POST['instance_title']) && !empty($_POST['base_url']) && !empty($_POST['timezone'])) {
        $mysql_host = $_POST['mysql_host'];
        $mysql_login = $_POST['mysql_login'];
        $mysql_db = $_POST['mysql_db'];
        $mysql_password = $_POST['mysql_password'];
        $mysql_prefix = (!empty($_POST['mysql_prefix'])) ? $_POST['mysql_prefix'] : '';
        $instance_title = (!empty($_POST['instance_title'])) ? $_POST['instance_title'] : 'Bouffe@Ulm';

        try {
            $db = new PDO('mysql:host='.$mysql_host.';dbname='.$mysql_db, $mysql_login, $mysql_password);

            //Create table "Users"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Users (id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, login VARCHAR(255), display_name VARCHAR(255), password VARCHAR(130), admin TINYINT(1)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
 
            //Create table "Invoices"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Invoices (id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, date INT(11), users_in VARCHAR(255), buyer INT(11), amount FLOAT, what TEXT) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');

            //Create table "Users_in_invoice"
            $db->query('CREATE TABLE IF NOT EXISTS '.$mysql_prefix.'Users_in_invoices (invoice_id INT(11) NOT NULL, KEY invoice_id (invoice_id), user_id INT(11), KEY user_id (user_id), guests INT(11)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
            
            //Create table "Payback" - TODO
        } catch (PDOException $e) {
            $error = 'Unable to connect to database, check your credentials and config.<br/>Error message : '.$e->getMessage().'.';
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
    define('VERSION_NUMBER', '2.0');
    define('MYSQL_HOST', '".$mysql_host."');
    define('MYSQL_LOGIN', '".$mysql_login."');
    define('MYSQL_PASSWORD', '".$mysql_password."');
    define('MYSQL_DB', '".$mysql_db."');
    define('MYSQL_PREFIX', '".$mysql_prefix."');
    define('INSTANCE_TITLE', '".$instance_title."');
    define('BASE_URL', '".$_POST['base_url']."');
    define('SALT', '".$salt."');
    define('CURRENCY', '".$_POST['currency']."');
    
    date_default_timezone_set('".$_POST['timezone']."');
    ";

            if(file_put_contents("data/config.php", $config) !== false && file_put_contents("data/notice", '') !== false) {
                try {
                    require_once('inc/User.class.php');
                    $admin = new User();
                    $admin->setLogin($_POST['admin_login']);
                    $admin->setDisplayName($_POST['admin_display_name']);
                    $admin->setPassword($admin->encrypt($_POST['admin_password']));
                    $admin->setAdmin(true);
                    $admin->save();
                    header('location: index.php');
                    exit();
                } catch (Exception $e) {
                    $erreur = 'An error occurred when inserting user in the database.<br/> Error message : '.$e->getMessage().'.';
                }
            }
            else
                $error = 'Unable to write configuration to config file data/config.php.';
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Bouffe@Ulm - Installation</title>
        <link rel="stylesheet" media="screen" type="text/css" href="tpl/css/style.css" />
        <script type="text/javascript" src="tpl/js/main.js"></script>
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
                <p><label for="mysql_host">MySQL host : </label><input type="text" name="mysql_host" id="mysql_host" value="<?php echo (!empty($_POST['mysql_host'])) ? htmlspecialchars($_POST['mysql_host']) : 'localhost';?>"/></p>

                <p><label for="mysql_login">MySQL login : </label><input type="text" name="mysql_login" id="mysql_login" value="<?php echo (!empty($_POST['mysql_login'])) ? htmlspecialchars($_POST['mysql_login']) : '';?>"/></p>
                <p><label for="mysql_password">MySQL password : </label><input type="password" name="mysql_password" id="mysql_password"/> <a href="" onclick="toggle_password('mysql_password'); return false;"><img src="img/toggle_password.jpg" alt="Toggle visible"/></a></p>
                <p>
                <label for="mysql_db">Name of the MySQL database to use : </label><input type="text" name="mysql_db" id="mysql_db" value="<?php echo (!empty($_POST['mysql_db'])) ? htmlspecialchars($_POST['mysql_db']) : 'Bouffe@Ulm';?>"/><br/>
                    <em>Note :</em> You <em>must</em> create this database first.
                </p>
                <p><label for="mysql_prefix">Prefix for the created tables : </label><input type="text" name="mysql_prefix" id="mysql_prefix" value="<?php echo (!empty($_POST['mysql_prefix'])) ? htmlspecialchars($_POST['mysql_prefix']) : 'bouffeatulm_';?>"/><br/>
                    <em>Note :</em> Leave the field blank to not use any.</p>
            </fieldset>
            <fieldset>
                <legend>General options</legend>
                <p><label for="instance_title">Title to display in pages : </label><input type="text" name="instance_title" id="instance_title" value="<?php echo (!empty($_POST['instance_title'])) ? htmlspecialchars($_POST['instance_title']) : 'Bouffe@Ulm';?>"/></p>
                <p>
                    <label for="base_url">Base URL : </label><input type="text" size="30" name="base_url" id="base_url" value="<?php echo (!empty($_POST['base_url'])) ? htmlspecialchars($_POST['base_url']) : 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'].str_replace("install.php", "", $_SERVER['REQUEST_URI']); ?>"/><br/>
                    <em>Note :</em> This is the base URL from which you access this page. You must keep the trailing "/" in the above address.
                </p>
                <p><label for="currency">Currency : </label><input type="text" name="currency" id="currency" size="3"/></p>
                <p>
                    <label for="timezone">Timezone : </label><input type="text" name="timezone" id="timezone" value="<?php echo @date_default_timezone_get();?>"/><br/>
                    <em>For example :</em> Europe/Paris. See the doc for more info.
                </p>
            </fieldset>
            <fieldset>
                <legend>Administrator</legend>
                <p><label for="admin_login">Username of the admin : </label><input type="text" name="admin_login" id="admin_login" <?php echo (!empty($_POST['admin_login'])) ? 'value="'.htmlspecialchars($_POST['admin_login']).'"' : '';?>/></p>
                <p><label for="admin_display_name">Displayed name for admin user : </label><input type="text" name="admin_display_name" id="admin_display_name" <?php echo (!empty($_POST['admin_display_name']) ? 'value="'.htmlspecialchars($_POST['admin_display_name']).'"' : '');?>/></p>
                <p><label for="admin_password">Password for the admin : </label><input type="password" name="admin_password" id="admin_password"/> <a href="" onclick="toggle_password('admin_password'); return false;"><img src="img/toggle_password.jpg" alt="Toggle visible"/></a></p>
            </fieldset>
            <p class="center"><input <?php echo (!empty($block_form)) ? 'disabled ' : '';?>type="submit" value="Install"></p>
        </form>
    </body>
</html>
