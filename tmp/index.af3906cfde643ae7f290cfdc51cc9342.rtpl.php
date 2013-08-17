<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( $notice != '' ){ ?>

    <div id="notice"><p><?php echo $notice;?></p></div>
<?php } ?>


<div id="quick_summary">
    <h2>Balance</h2>
    <p class="center">Read <em>line</em> owes <em>case</em> <?php echo $currency;?> to <em>column</em>. You can click on links to confirm the payback.
    <table> 
        <tr>
            <th>Owes\To</th>
            <?php $counter1=-1; if( isset($users) && is_array($users) && sizeof($users) ) foreach( $users as $key1 => $value1 ){ $counter1++; ?>

            <th><?php echo $value1->getDisplayName();?></th>
            <?php } ?>

        </tr>
        <?php $counter1=-1; if( isset($users) && is_array($users) && sizeof($users) ) foreach( $users as $key1 => $value1 ){ $counter1++; ?>

            <tr>
                <th><?php echo $value1->getDisplayName();?></th>
                <?php $counter2=-1; if( isset($users) && is_array($users) && sizeof($users) ) foreach( $users as $key2 => $value2 ){ $counter2++; ?>

                    <td><a href=""><?php echo $value2->getDisplayName();?></a></td>
                <?php } ?>

            </tr>
        <?php } ?>

    </table>
</div>
<div id="detailed_summary">
    <h2>Detailed list of bills for last month</h2>

    <table>
        <tr>
            <th>Date</th>
            <th>Paid by</th>
            <th>Users in</th>
            <th>Amount</th>
            <th>What ?</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
        <?php $counter1=-1; if( isset($invoices) && is_array($invoices) && sizeof($invoices) ) foreach( $invoices as $key1 => $value1 ){ $counter1++; ?>

            <tr>
                <td><?php echo $value1->getDate;?></td>
                <td><?php echo $value1->getBuyer;?></td>
                <td><?php echo $value1->getUsersIn;?></td>
                <td><?php echo $value1->getAmount;?></td>
                <td><?php echo $value1->getWhat;?></td>
                <td><a href="index.php?do=edit_bill&id=<?php echo $value1->getId();?>">Edit</a></td>
                <td><a href="index.php?do=delete_bill&id=<?php echo $value1->getId();?>">Delete</a></td>
            </tr>
        <?php } ?>

    </table>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>

