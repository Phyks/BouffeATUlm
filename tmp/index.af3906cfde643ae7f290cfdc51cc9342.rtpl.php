<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<?php if( $notice != '' ){ ?>

    <div id="notice"><p><?php echo $notice;?></p></div>
<?php } ?>


<div id="quick_summary">
    <h2>Balance</h2>
    <p class="center">Read <em>line</em> owes <em>case</em><?php echo $currency;?> to <em>column</em>. You can click on links to confirm the payback.
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
        <?php $counter1=-1; if( isset($bill) && is_array($bill) && sizeof($bill) ) foreach( $bill as $key1 => $value1 ){ $counter1++; ?>

            <tr>
                <td><?php echo $value1["date"];?></td>
                <td><?php echo $value1["buyer"];?></td>
                <td><?php echo $value1["users_in"];?></td>
                <td><?php echo $value1["amount"];?></td>
                <td><?php echo $value1["what"];?></td>
                <td><a href="index.php?do=edit_bill&id=">Edit</a></td>
                <td><a href="index.php?do=delete_bill&id=">Delete</a></td>
            </tr>
        <?php } ?>

    </table>
</div>

<?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("footer") . ( substr("footer",-1,1) != "/" ? "/" : "" ) . basename("footer") );?>

