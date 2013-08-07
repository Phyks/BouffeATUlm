<?php
    if(file_exists('inc/config.php')) exit("Your Bouffe@Ulm instance is already configured. You should either delete inc/config.php to access this page or delete the install.php for security reasons if you are ok with the configuration.");

    if(!function_exists("file_get_contents") && !function_exists("file_put_contents")) {
        $error = "Functions <em>file_get_contents</em> and <em>file_put_contents</em> seems to not be available on your PHP installation. You should enable them first.";
        $block_form = true;
    }

    if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db']) && !empty($_POST['admin_login']) && !empty($_POST['admin_pass'])) {
        $mysql_host = $_POST['mysql_host'];
        $mysql_login = $_POST['mysql_login'];
        $mysql_db = $_POST['mysql_login'];
        $mysql_password = $_POST['mysql_password'];
        $mysql_prefix = $_POST['mysql_prefix'];
        $instance_title = (!empty($_POST['instance_title'])) ? $_POST['instance_title'] : 'Bouffe@Ulm';

        try {
            $db = new Storage(array('host'=>$mysql_host, 'login'=>$mysql_login, 'password'=>$mysql_password, 'db'=>$mysql_db));
            //TODO : Create tables
        } catch (PDOException $e) {
            $error = 'Unable to connect to database, check your credentials.';
        }

        if(empty($error)) {
            if(function_exists('mcrypt_create_iv')) {
                $salt = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
            }
            else {
                mt_srand(microtime(true)*100000 + memory_get_usage(true));
                $salt = md5(uniqid(mt_rand(), true));
            }

            define('SALT', $salt);
            
            $config = "
                define('VERSION_NUMBER', '2.0');
                define('MYSQL_HOST', '".$mysql_host."');
                define('MYSQL_LOGIN', '".$mysql_login."');
                define('MYSQL_PASSWORD', '".$mysql_password."');
                define('MYSQL_DB', '".$mysql_db."');
                define('MYSQL_PREFIX', '".$mysql_prefix."');
                define('INSTANCE_TITLE', '".$instance_title."');
                define('BASE_URL', '".$_POST['base_url']."');
                define('SALT', '".$salt."');";

            if(file_put_contents("inc/config.php", $config)) {
                try {
                    $admin = new User();
                    $admin->setLogin($_POST['admin_login']);
                    $admin->setPassword($_POST['admin_password']);
                    $admin->setAdmin(true);
                    $admin->save();
                    header('location: index.php');
                    exit();
                } catch (Exception $e) {
                    //TODO
                }
            }
            else
                $error = 'Unable to write configuration to config file inc/config.php.';
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Bouffe@Ulm - Installation</title>
        <link rel="stylesheet" media="screen" type="text/css" href="tpl/css/style.css" />
    </head>
    <body id="install">
        <h1 class="center">Bouffe@Ulm - Installation</h1>

        <?php
            if(!empty($error)) {
                echo '<p class="error">'.$error.'</p>';
            }
        ?>

        <p class="center">This small form will guide you through the installation of Bouffe@Ulm.</p>

        <form action="install.php" method="post">
            <fieldset>
                <legend>Database</legend>
                <p><label for="mysql_host">MySQL host : </label><input type="text" name="mysql_host" id="mysql_host" value="<?php echo (!empty($_POST['mysql_host'])) ? htmlspecialchars($_POST['mysql_host']) : 'localhost';?>"/></p>

                <p><label for="mysql_login">MySQL login : </label><input type="text" name="mysql_login" id="mysql_login" value="<?php echo (!empty($_POST['mysql_login'])) ? htmlspecialchars($_POST['mysql_login']) : '';?>"/></p>
                <p><label for="mysql_password">MySQL password : </label><input type="password" name="mysql_password" id="mysql_password"/></p>
                <p>
                <label for="mysql_db">Name of the MySQL database to use : </label><input type="text" name="mysql_db" id="mysql_db" value="<?php echo (!empty($_POST['mysql_db'])) ? htmlspecialchars($_POST['mysql_db']) : 'Bouffe@Ulm';?>"/><br/>
                    <em>Note :</em> You <em>must</em> create this database first.
                </p>
                <p><label for="mysql_prefix">Prefix for the created tables : </label><input type="text" name="mysql_prefix" id="mysql_prefix" value="<?php echo (!empty($_POST['mysql_prefix'])) ? htmlspecialchars($_POST['mysql_prefix']) : 'bouffeatulm_';?>"/></p>
            </fieldset>
            <fieldset>
                <legend>General options</legend>
                <p><label for="instance_title">Title to display in pages : </label><input type="text" name="instance_title" id="instance_title" value="Bouffe@Ulm"/></p>
                <p>
                    <label for="base_url">Base URL : </label><input type="text" size="30" name="base_url" id="base_url" value="<?php echo 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'].str_replace("install.php", "", $_SERVER['REQUEST_URI']); ?>"/><br/>
                    <em>Note :</em> This is the base URL from which you access this website. You must keep the trailing "/" in the above address.
                </p>
            </fieldset>
            <fieldset>
                <legend>Administrator</legend>
                <p><label for="admin_login">Username of the admin : </label><input type="text" name="admin_login" id="admin_login"/></p>
                <p><label for="admin_mdp">Password for the admin : </label><input type="password" name="admin_pass" id="admin_pass"/></p>
            </fieldset>
            <p class="center"><input <?php echo (!empty($block_form)) ? 'disabled ' : '';?>type="submit"></p>
        </form>
    </body>
</html>
