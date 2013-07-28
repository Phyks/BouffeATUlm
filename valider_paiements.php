<?php
	require('include.php');
	init(true, false);
	
	if((empty($_GET['de']) OR empty($_GET['a'])) AND empty($_GET['all'])) //If we didn't get the right arguments
	{
			header('location: index.php');
			exit();
	}
	
	$a = (int) $_GET['a'];
	if($a != $_SESSION['id'] AND $_SESSION['admin'] != 1) //We can only validate what other people owe to us !
	{
		header('location: message.php?id=9');
		exit();
	}
	
	if(empty($_GET['valide'])) //Validation page to be sure the user didn't click by mistake
	{
		$_SESSION['token_validation'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
		$_SESSION['token_validation_time'] = time(); //We also store the time at which the token has been generated
	
		$lien = 'valider_paiements.php?valide=1&amp;date='.$_GET['date'].'&amp;a='.$a.'&amp;token='.$_SESSION['token_validation'];
		if(!empty($_GET['all']))
			$lien .= '&amp;all=1';
		if(!empty($_GET['de']))
			$lien .= '&amp;de='.(int)$_GET['de'];
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Bouffe@Ulm</title>
		<link rel="stylesheet" media="screen" type="text/css" href="design.css" />
		<link rel="icon" href="favicon.ico" />
	</head>
	<body>
		<h1>Validation du remboursement des dettes</h1>
		<p><a href="<?php echo $lien;?>">Confirmer le remboursement</a> ou <a href="index.php">Retour</a></p>
	</body>
</html>
<?php
		exit();
	}
	
	function inserer_paiement($donnees_depense, $de_paiement, $a_paiement) //Function to insert a regulation between A and B
	{
		if ($de_paiement == $a_paiement) return 1;
		global $bdd;
		// We count the number of payment not created during a simplification turn 
		// (created by this page, not rbmt_admin.php
		$paiement_existe_req = $bdd->prepare('SELECT *,COUNT(*) AS nbre_paiement FROM paiements WHERE id_depense=:id_depense AND de=:de');
		$paiement_existe_req->bindValue(':id_depense', $donnees_depense['id']);
		$paiement_existe_req->bindValue(':de', $de_paiement);
		$paiement_existe_req->execute();

		$deja_paye = 0;
		while($paiement_existe = $paiement_existe_req->fetch())
		{
			$deja_paye += $paiement_existe['montant'];
		}
				
		$montant = $donnees_depense['montant']/(substr_count($donnees_depense['copains'], ',') + 1 + $donnees_depense['invites']) - $deja_paye;
		
		if($paiement_existe['nbre_paiement'] == 0)
			$req = $bdd->prepare('INSERT INTO paiements(id, de, a, id_depense, date, montant) VALUES("", :de, :a, :id_depense, :date, :montant)');
		else
			$req = $bdd->prepare('UPDATE paiements SET montant=:montant, date=:date WHERE de=:de AND a=:a AND id_depense=:id_depense AND rbmt=0');

		$req->bindValue(':de', $de_paiement);
		$req->bindValue(':a', $a_paiement);
		$req->bindValue(':id_depense', $donnees_depense['id']);
		$req->bindValue(':date', time());

		$req->bindValue(':montant', $montant);
		$req->execute();
		return 1;
	}
	
	function bornes_mois($num_mois,$annee) //Function to get the limit of dates to make the queries
	{
		$debut_mois = mktime(0, 0, 0,$num_mois, 1, $annee);
		$dernier_jour = array(
					1=>31,
					2=>28+date('L'),
					3=>31,
					4=>30,
					5=>31,
					6=>30,
					7=>31,
					8=>31,
					9=>30,
					10=>31,
					11=>30,
					12=>31);
		$fin_mois = mktime(23, 59, 59, $num_mois, $dernier_jour[$num_mois], $annee);
		$bornes = array($debut_mois, $fin_mois);
		return $bornes;
	}
	
	if(!empty($_GET['token']) && $_GET['token'] == $_SESSION['token_validation'] && $_SESSION['token_validation_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0) // Check wether the token is valid or not
	{
		if(!empty($_GET['all']))
		{		
			//Validate everything for a
	
			$req = $bdd->prepare('SELECT id, de, copains, montant, invites FROM depenses WHERE de=:a AND date>:debut_mois AND date<:fin_mois');
			$req->bindValue(':a', $a);
		}
		else
		{
			$de = (int) $_GET['de'];
	
			//Validate everything between a and de
			$req = $bdd->prepare('SELECT id, de, copains, montant, invites FROM depenses WHERE (copains LIKE "%,'.$de.',%" OR copains LIKE "%,'.$de.'" OR copains LIKE "'.$de.',%" OR copains LIKE "'.$de.'") AND de=:a AND date>:debut_mois AND date<:fin_mois');
			$req->bindValue(':a', $a);
		}

		if($_GET['date'] == 'now') //Bind date bounds
		{
			$bornes = bornes_mois(date('n'),date('Y'));
			$req->bindValue(':debut_mois', $bornes[0]);
			$req->bindValue(':fin_mois', $bornes[1]);
		}
		elseif($_GET['date'] == 'all')
		{
			$bornes = bornes_mois(date('n'),date('Y'));
			$bornes[0] = 0;
			$req->bindValue(':debut_mois', $bornes[0]);
			$req->bindValue(':fin_mois', $bornes[1]);
		}
		else
		{
			header('location: index.php');
			exit();
		}
		$req->execute();

		while($donnees = $req->fetch())
		{
			if(!empty($de) && $de != $a)
			{
				inserer_paiement($donnees, $de, $a);
			}
			else
			{
				//For all the people who participate...
				$participants = explode(',', $donnees['copains']);
				foreach($participants as $participant)
				{
				echo $participant . ',' .$a . '<br/>';
					if ($participant != $a) 	inserer_paiement($donnees, $participant, $a);
				}
			}
		}

		//And don't forget to validate everything I owe to others
		$req_me = $bdd->prepare('SELECT id, copains, de, montant, invites FROM depenses WHERE de=:de AND (copains LIKE "%,'.$a.',%" OR copains LIKE "%,'.$a.'" OR copains LIKE "'.$a.',%" OR copains LIKE "'.$a.'") AND date>:debut_mois AND date<:fin_mois');
		$req_me->bindValue(':de', $de);
		$req_me->bindValue(':debut_mois', $bornes[0]);
		$req_me->bindValue(':fin_mois', $bornes[1]);
		$req_me->execute();
		while($donnees_me = $req_me->fetch())
		{
			inserer_paiement($donnees_me, $a, $donnees_me['de']);
		}
		header('location: message.php?id=11');
		exit();
	}
	else //If not valid, go back to index.php
	{
		header('location: index.php');
		exit();
	}
?>
