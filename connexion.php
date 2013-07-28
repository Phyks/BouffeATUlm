<?php
	require('include.php');
	init(false, false); //No need to authenticate to see this page, the authentications are made below

	if(!empty($_COOKIE['id']) AND !empty($_COOKIE['connexion_auto']) AND empty($_GET['deco']))
	{
		$req = $bdd->prepare('SELECT nom, password, admin FROM copains WHERE id=:id');
		$req->bindValue(':id', $_COOKIE['id']);
		$req->execute();
		
		$donnees = $req->fetch();

		$navigateur = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$hash_cookie = '8531fd8c7a18b10700b9e7bf040b349009f7c711'.sha1($donnees['nom']).'9ff80fa675712e6cfa5482b96a4a5e488b68cabe'.sha1($donnees['password']).'cb9013648bed4362d3d98b553f1afc62c4381058'.sha1($navigateur).'17c0cf0afe131e12886bea1757dba73801b6c7d1'.sha1($_SERVER['REMOTE_ADDR']).'bf63c72e9a6ecad6c0d85d8eb972fceed8a14da2';

		if($hash_cookie == $_COOKIE['connexion_auto'])
		{
			$_SESSION['id'] = (int) $_COOKIE['id'];
			$_SESSION['nom'] = htmlspecialchars($donnees['nom']);
			$_SESSION['admin'] = (int) $donnees['admin'];

			header('location: index.php');
			exit();
		}
	}
	
	if(!empty($_SESSION['nom']) && empty($_GET['deco'])) //If we don't want to disconnect
	{
		header('location: index.php'); //No need to see this page
		exit();
	}
	
	if(!empty($_POST['nom']) && !empty($_POST['password'])) //If we want to connect
	{
		$req = $bdd->prepare('SELECT id, password, admin FROM copains WHERE nom=:nom'); //Get the pass in bdd
		$req->bindValue(':nom', $_POST['nom']);
		$req->execute();
		
		$donnees = $req->fetch();
		
		$password = sha1($_POST['password'] . $CONFIG['salt']);

		if($donnees['password'] == $password) //Salt
		{
			$_SESSION['id'] = (int) $donnees['id'];
			$_SESSION['nom'] = htmlspecialchars($_POST['nom']);
			$_SESSION['admin'] = (int) $donnees['admin'];
			
			
			if(!empty($_POST['auto_connect']))
			{
				$navigateur = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
				$hash_cookie = '8531fd8c7a18b10700b9e7bf040b349009f7c711'.sha1($_SESSION['nom']).'9ff80fa675712e6cfa5482b96a4a5e488b68cabe'.sha1($password).'cb9013648bed4362d3d98b553f1afc62c4381058'.sha1($navigateur).'17c0cf0afe131e12886bea1757dba73801b6c7d1'.sha1($_SERVER['REMOTE_ADDR']).'bf63c72e9a6ecad6c0d85d8eb972fceed8a14da2';
				
				setcookie( 'id', $_SESSION['id'], time()+31536000, '/', $CONFIG['domain'], true, true);
				setcookie('connexion_auto', $hash_cookie, time()+31536000, '/', $CONFIG['domain'], true, true);
			}
			
			header('location: index.php');
			exit();
		}
		else
		{
			header('location:message.php?id=8'); //Error message
			exit();
		}
	}
	
	if(!empty($_GET['deco'])) //If we want to disconnect
	{
		session_destroy();
		if(!empty($_COOKIE['id']))
			setcookie( 'id', '', time()-31536000, '/', $CONFIG['domain'], true, true);
		if(!empty($_COOKIE['connexion_auto']))
			setcookie( 'connexion_auto', '', time()-31536000, '/', $CONFIG['domain'], true, true);
			
		header('location: connexion.php');
		exit();
	}
	//Display a log form
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
		<h1>Bouffe@Ulm - Connexion</h1>
		<form method="post" action="connexion.php">
			<p>
				<label for="nom">Nom : </label><input type="text" size="50" name="nom" id="nom"/>
			</p>
			<p>
				<label for="password">Mot de passe : </label><input type="password" size="50" name="password" id="password"/>
			</p>
			<p>
				<label class="inline" for="1">Connexion automatique ? </label><input type="checkbox" name="auto_connect" value="1" id="1">
			</p>
			<p><input type="submit" value="Connexion"/></p>
		</form>
	</body>
</html>
