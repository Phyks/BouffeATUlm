<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( !$show_settings ){ ?>

<h2>Edit homepage notice</h2>
<form method="post" id="notice_form" action="index.php?do=edit_notice">
    <p>
        <label for="textarea_notice">Homepage notice :</label><br/>
        <textarea name="notice" rows="15" id="textarea_notice"><?php echo $notice;?></textarea>
    </p>
    <p><em>Note :</em> You can use HTML formatting in this form.</p>
    <input type="submit" value="Submit"/>
</form>

<?php }else{ ?>


<h2>Change settings of your Bouffe@Ulm installation</h2>
<form method="post" action="index.php?do=settings" id="settings_form">
    <fieldset>
        <legend>Database</legend>
        <p><em>Note :</em> Use these settings carefully. Your database won't be updated by the script as it was during install and you'll have to manually update it.</p>
        <p><label for="mysql_host">MySQL host : </label><input type="text" name="mysql_host" id="mysql_host" value="<?php echo $mysql_host;?>"/></p>

        <p><label for="mysql_login">MySQL login : </label><input type="text" name="mysql_login" id="mysql_login" value="<?php echo $mysql_login;?>"/></p>
        <p>
            <label for="mysql_password">MySQL password : </label><input type="password" name="mysql_password" id="mysql_password"/><br/>
            <em>Note :</em> Leave the above field blank if you don't want to change your password.
        </p>
        <p>
            <label for="mysql_db">Name of the MySQL database to use : </label><input type="text" name="mysql_db" id="mysql_db" value="<?php echo $mysql_db;?>"/><br/>
            <em>Note :</em> You <em>must</em> create this database first.
        </p>
        <p>
            <label for="mysql_prefix">Prefix for the created tables : </label><input type="text" name="mysql_prefix" id="mysql_prefix" value="<?php echo $mysql_prefix;?>"/><br/>
            <em>Note :</em> Leave the field blank to not use any.</p>
    </fieldset>
    <fieldset>
        <legend>General options</legend>
        <p><label for="instance_title">Title to display in pages : </label><input type="text" name="instance_title" id="instance_title" value="<?php echo $instance_title;?>"/></p>
        <p>
        <label for="base_url">Base URL : </label><input type="text" size="30" name="base_url" id="base_url" value="<?php echo $base_url;?>"/><br/>
            <em>Note :</em> This is the base URL from which you access this page. You must keep the trailing "/" in the above address.
        </p>
        <p><label for="currency">Currency : </label><input type="text" name="currency" id="currency" size="3" value="<?php echo $currency;?>"/></p>
    </fieldset>
    <p class="center"><input type="submit" value="Update settings"></p>
</form>

<?php } ?>


<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>
