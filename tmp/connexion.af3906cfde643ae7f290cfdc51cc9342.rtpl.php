<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<h1><?php echo $instance_title;?> - Connexion</h1>

<form method="post" action="index.php?do=connect">
    <p><label for="login">Username : </label><input type="text" name="login" id="login"/></p>
    <p><label for="password">Password : </label><input type="password" name="password" id="password"/></p>
    <p><input type="submit" value="Connect"/></p>
</form>
