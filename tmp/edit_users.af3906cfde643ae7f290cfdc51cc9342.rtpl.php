<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( $error != '' ){ ?>

    <p class="error"><?php echo $error;?></p>
<?php } ?>


<?php if( $view == 'list_users' ){ ?>

<h2>List of users</h2>
<p>You can also <a href="?do=add_user">add a user</a>.</p>
<table>
    <tr>
        <th>Id</th>
        <th>Login</th>
        <th>Display Name</th>
        <th>Is admin ?</th>
        <th>Edit</th>
        <th>Delete</th>
    </tr>
    <?php $counter1=-1; if( isset($users) && is_array($users) && sizeof($users) ) foreach( $users as $key1 => $value1 ){ $counter1++; ?>

    <tr>
        <td><?php echo $value1->getId();?></td>
        <td><?php echo $value1->getLogin();?></td>
        <td><?php echo $value1->getDisplayName();?></td>
        <td><?php echo $value1->getAdmin() ? "Yes" : "No";?></td>
        <td><a href="index.php?do=edit_users&user_id=<?php echo $value1->getId();?>">Edit</a></td>
        <td><?php if( $value1->getId() != $current_user->getId() ){ ?><a href="index.php?do=delete_user&user_id=<?php echo $value1->getId();?>">Delete</a><?php } ?></td>
    </tr>
    <?php } ?>

</table>
<?php }elseif( $view == 'edit_user' ){ ?>

<h2>Edit a user</h2>
<form method="post" action="index.php?do=add_user" id="edit_user_form">
    <p>
        <label for="login" class="label-block">Login : </label><input type="text" name="login" id="login" <?php if( $login_post != '' ){ ?> value="<?php echo $login_post;?>" <?php }else{ ?> <?php echo $user_id != -1 ? 'value="'.$user_data->getLogin().'"' : '';?> <?php } ?>/>
    </p>
    <p>
        <label for="display_name" class="label-block">Displayed name : </label><input type="text" name="display_name" id="display_name" <?php if( $display_name_post != '' ){ ?> value="<?php echo $display_name_post;?>" {/else} <?php echo $user_id != -1 ? 'value="'.$user_data->getDisplayName().'"' : '';?> <?php } ?>/>
    </p>
    <p>
        <label for="password" class="label-block">Password : </label><input type="password" name="password" id="password"/>
        <?php if( $user_id != -1 ){ ?>

            <br/><em>Note :</em> Leave blank this field if you don't want to edit password.
        <?php } ?>

    </p>
    <p id="edit_user_admin_rights">
        Give admin rights to this user ?<br/>
    <input type="radio" id="admin_yes" value="1" name="admin" <?php if( $admin_post == 1 || ($admin_post == -1 && $user_id != -1 && $user_data->getAdmin()) ){ ?> checked<?php } ?>/><label for="admin_yes">Yes</label><br/>
    <input type="radio" id="admin_no" value="0" name="admin" <?php if( $admin_post == 0 || ($admin_post == -1 && ($user_id == -1 || !$user_data->getAdmin())) ){ ?> checked<?php } ?>/><label for="admin_no">No</label>
    </p>
    <p class="center">
        <input type="submit" value="<?php echo $user_id != -1 ? 'Edit' : 'Add';?>"/>
        <?php if( $user_id != -1 ){ ?><input type="hidden" name="user_id" value="<?php echo $user_id;?>"/><?php } ?>

    </p>
</form>

<?php }elseif( $view == 'password' ){ ?>

<h2>Edit your password</h2>
<form method="post" action="index.php?do=password" id="edit_password_form">
    <p><label for="password" class="label-block">New password : </label><input type="password" id="password" name="password"/></p>
    <p><label for="password_confirm" class="label-block">Confirm new password : </label><input type="password" id="password_confirm" name="password_confirm"/></p>
    <p class="center"><input type="submit" value="Update"/></p>
</form>
<?php } ?>

