<?php
	require('include.php');
	init(true, false);
	
	if(isset($_POST['password_old']) && isset($_POST['password_new1']) && isset($_POST['password_new2']) && !empty($_POST['token']) && $_POST['token'] == $_SESSION['token_password'] && $_SESSION['token_password_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0) //If we update the password and token is correct
	{
		$req_pass = $bdd->query('SELECT password FROM copains WHERE id='.(int) $_SESSION['id']);
		$password_bdd = $req_pass->fetch();
		
		if($_POST['password_new1'] == $_POST['password_new2'] && $password_bdd['password'] == sha1($_POST['password_old'] . $CONFIG['salt']))
		{
			$req = $bdd->prepare('UPDATE copains SET password=:password WHERE id='.(int) $_SESSION['id']);
			$req->bindValue(':password', sha1($_POST['password_new1'] . $CONFIG['salt']));
			$req->execute();
		
			header('location: message.php?id=13');
			exit();
		}
		else
		{
			header('location: message.php?id=12');
			exit();
		}
	}
	
	$_SESSION['token_password'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
	$_SESSION['token_password_time'] = time(); //We also store the time at which the token has been generated
?>		
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Bouffe@Ulm</title>
		<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
		<link rel="icon" href="favicon.ico" />
	</head>
	<body>
		<h1>Bouffe@Ulm</h1>
		<h2>Modifier le mot de passe</h2>
		<p><a href="index.php">Retour à l'accueil</a></p>
		<form method="post" action="modif_password.php">
			<p><label for="password_old">Ancien mot de passe : </label><input type="password" name="password_old" id="password_old" size="50"/></p>
			<p><label for="password_new1">Nouveau mot de passe : </label><input type="password" name="password_new1" id="password_new1" size="50"/></p>
			<p><label for="password_new2">Nouveau mot de passe (confirmation) : </label><input type="password" name="password_new2" id="password_new2" size="50"/></p>
			<p>
				<input type="submit" value="Modifier"/>
				<input type="hidden" name="token" value="<?php echo $_SESSION['token_password'];?>"/>
			</p>
		</form>
	</body>
</html>
