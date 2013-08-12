<?php if(!class_exists('raintpl')){exit;}?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title><?php echo $instance_title;?></title>
<link rel="stylesheet" media="screen" type="text/css" href="tpl/./css/style.css" />
<link rel="icon" href="tpl/./favicon.ico" />
</head>
<body>
<?php if( !$connection ){ ?>

<h1 id="title"><a href="<?php echo $base_url;?>"><?php echo $instance_title;?></a></h1>

<div id="menu">
    <ul>
        <li><a href="index.php?do=new_invoice">Add a bill</a></li>
        <li><a href="index.php?do=password">Change your password</a></li>
        <li><a href="index.php?do=paybacks">See paybacks</a></li>
        <li><a href="index.php?do=disconnect">Disconnect</a></li>
    </ul>
    <?php if( $current_user->getAdmin() == 1 ){ ?>

    <ul>
        <li><a href="index.php?do=manage_paybacks">Manage paybacks</a></li>
        <li><a href="index.php?do=edit_users">Edit users</a></li>
        <li><a href="index.php?do=edit_notice">Edit notice on homepage</a></li>
        <li><a href="index.php?do=settings">Settings</a></li>
    </ul>
    <?php } ?>

</div>
<?php } ?>

