<?php
    if(file_exists('inc/config.php')) exit("Your Bouffe@Ulm instance is already configured. You should either delete inc/config.php to access this page or delete the install.php for security reasons if you are ok with the configuration.");

    if(!function_exists("file_get_contents") && !function_exists("file_put_contents")) {
        $error = "Functions <em>file_get_contents</em> and <em>file_put_contents</em> seems to not be available on your PHP installation. You should enable them first.";
        $block_form = true;
    }

    if(!empty($_POST['mysql_host']) && !empty($_POST['mysql_login']) && !empty($_POST['mysql_db'])) {
        $mysql_host = $_POST['mysql_host'];
        $mysql_login = $_POST['mysql_login'];
        $mysql_db = $_POST['mysql_login'];
        $mysql_password = $_POST['mysql_password'];
        $mysql_prefix = $_POST['mysql_prefix'];
        $instance_title = (!empty($_POST['instance_title'])) ? $_POST['instance_title'] : 'Bouffe@Ulm';

        try {
            $db = new PDO("mysql:host=".$mysql_host.";dbname=".$mysql_db, $mysql_login, $mysql_password);
        }
        catch (PDOException $e) {
            $error = 'Unable to connect to database, check your credentials.';
        }

        if(empty($error)) {
            $config = "
                define('VERSION_NUMBER', '2.0');

                define('MYSQL_HOST', '".$mysql_host."');
                define('MYSQL_LOGIN', '".$mysql_login."');
                define('MYSQL_PASSWORD', '".$mysql_password."');
                define('MYSQL_DB', '".$mysql_db."');
                define('MYSQL_PREFIX', '".$mysql_prefix."');

                define('INSTANCE_TITLE', '".$instance_title."');";
            file_put_contents("inc/config.php", $config);
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
    <body>
        <h1>Bouffe@Ulm - Installation</h1>

        <?php
            if(!empty($error)) {
                echo '<p class="error">'.$error.'</p>';
            }
        ?>

        <p>This small form will guide you through the installation of Bouffe@Ulm.</p>

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
            </fieldset>

            <p><input <?php echo (!empty($block_form)) ? 'disabled ' : '';?>type="submit" class="center"></p>
        </form>
    </body>
</html>
