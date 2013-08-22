<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<h2>Add a bill</h2>

<form method="post" action="index.php?do=new_invoice" id="invoice_form">
    <p>
        <label for="what">What ? </label>
    </p>
    <textarea name="what" id="what" rows="10"><?php echo $what_post;?></textarea>
    <p>
        <label for="amount">Amount : </label>
        <input type="text" name="amount" id="amount" <?php if( $amount_post != 0 ){ ?> value="<?php echo $amount_post;?>" <?php } ?> size="5"/> <?php echo $currency;?>

    </p>
    <p>
        <label for="date_day">Date : </label>
        <select name="date_day" id="date_day">
            <?php $counter1=-1; if( isset($days) && is_array($days) && sizeof($days) ) foreach( $days as $key1 => $value1 ){ $counter1++; ?>

            <option value="<?php echo $value1;?>" <?php if( $value1 == $day_post ){ ?>selected<?php } ?>><?php echo $value1;?></option>
            <?php } ?>

        </select> / 
        <select name="date_month" id="date_month" onchange="set_days_month_year();">
            <?php $counter1=-1; if( isset($months) && is_array($months) && sizeof($months) ) foreach( $months as $key1 => $value1 ){ $counter1++; ?>

            <option value="<?php echo $value1;?>" <?php if( $value1 == $month_post ){ ?>selected<?php } ?>><?php echo $value1;?></option>
            <?php } ?>

        </select> / 
        <select name="date_year" id="date_year" onchange="set_days_month_year();">
            <?php $counter1=-1; if( isset($years) && is_array($years) && sizeof($years) ) foreach( $years as $key1 => $value1 ){ $counter1++; ?>

                <option value="<?php echo $value1;?>" <?php if( $value1 == $year_post ){ ?>selected<?php } ?>><?php echo $value1;?></option>
            <?php } ?>

        </select>
    </p>
    <p>
        Users in ?
        <?php $counter1=-1; if( isset($users) && is_array($users) && sizeof($users) ) foreach( $users as $key1 => $value1 ){ $counter1++; ?>

        <br/><input type="checkbox" name="users_in[]" value="<?php echo $value1->getId();?>" id="users_in_<?php echo $value1->getId();?>" <?php if( $current_user->getId() == $value1->getId() || in_array($value1->getId(), $users_in) ){ ?> checked <?php } ?>/> <label for="users_in_<?php echo $value1->getId();?>"><?php echo $value1->getDisplayName();?></label> and <input type="text" name="guest_user_<?php echo $value1->getId();?>" id="guest_user_<?php echo $value1->getId();?>" size="1" <?php if( in_array($value1->getId(), $users_in) ){ ?> value="<?php echo $guests[$value1->getId()];?>" <?php }else{ ?> value="0" <?php } ?> onkeyup="guest_user_label(<?php echo $value1->getId();?>);"/><label for="guest_user_<?php echo $value1->getId();?>" id="guest_user_<?php echo $value1->getId();?>_label"> guest</label>.
        <?php } ?>

    </p>
    <p>
        <input type="submit" value="Add"/>
        <?php if( $id != 0 ){ ?><input type="hidden" name="id" value="<?php echo $id;?>"/><?php } ?>

    </p>
</form>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>

