<?php
	//Init file
	
	function init($protect, $admin)
	{
		global $bdd;
		global $CONFIG;
		//Connect to the BDD
		$bdd = new PDO('mysql:host=localhost;dbname=Bouffe@Ulm', 'Bouffe@Ulm', 'aR4ndnabwE5RyEEx');
		$bdd->query("SET NAMES 'utf8'");
	
		session_start();
	
		date_default_timezone_set('Europe/Paris'); //Definition of the clock
	
		if($protect) //If user must be logged in
		{
			if(empty($_SESSION['nom']))
			{
				header('location:connexion.php');
				exit();
			}
		}
		if($admin) //If he must be an admin
		{
			if(empty($_SESSION['admin']))
			{
				header('location: message.php?id=7');
				exit();
			}
		}
	
		$CONFIG['base_url'] = 'localhost/Bouffe@Ulm/';
		$CONFIG['domain'] = '';
		$CONFIG['salt'] = '62407efbf5e8508baf096e1e23f497991e12a3bd';
	}
