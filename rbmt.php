<?php
	require('include.php');
	init(true, false);
		
	//Return an array with date of the start of the month and of the end of the month
	function bornes_mois($num_mois,$annee)
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
	
	//Return what A must pay to B
	function dettes($A, $B, $debut_mois, $fin_mois)
	{
		global $bdd;
		//When A paid and B was here
		$reqA_B = $bdd->prepare('SELECT id, date, montant, copains, invites FROM depenses WHERE date>'.$debut_mois.' AND date<'.$fin_mois.' AND de=:param1 AND (copains LIKE "%,'.(int) $B.',%" OR copains LIKE "%,'.(int)$B.'" OR copains LIKE "'.(int) $B.',%" OR copains LIKE "'.(int) $B.'")');
		$reqA_B->bindvalue(':param1',$A);
		$reqA_B->execute();
		//When B paid and A was here
		$reqB_A = $bdd->prepare('SELECT id, date, montant, copains, invites FROM depenses WHERE date>'.$debut_mois.' AND date<'.$fin_mois.' AND de=:param1 AND (copains LIKE "%,'.(int) $A.',%" OR copains LIKE "%,'.(int)$A.'" OR copains LIKE "'.(int) $A.',%" OR copains LIKE "'.(int) $A.'")');
		$reqB_A->bindvalue(':param1',$B);
		$reqB_A->execute();
		//What A already paid to B for the current month
		$reqPaiementsA_B = $bdd -> prepare('SELECT paiements.montant AS montant FROM paiements LEFT JOIN depenses ON paiements.id_depense=depenses.id WHERE depenses.date >'.$debut_mois.' AND depenses.date<'.$fin_mois.' AND paiements.de=:de AND paiements.a=:a');
		$reqPaiementsA_B->bindvalue(':de',$A);
		$reqPaiementsA_B->bindvalue(':a',$B);
		$reqPaiementsA_B->execute();
		//Same thing for B to A
		$reqPaiementsB_A = $bdd -> prepare('SELECT paiements.montant AS montant FROM paiements LEFT JOIN depenses ON paiements.id_depense=depenses.id WHERE depenses.date >'.$debut_mois.' AND depenses.date<'.$fin_mois.' AND paiements.de=:de AND paiements.a=:a');
		$reqPaiementsB_A->bindvalue(':de',$B);
		$reqPaiementsB_A->bindvalue(':a',$A);
		$reqPaiementsB_A->execute();
		//$deltaAB : What A must pay to B
		$deltaAB = 0;
	
		while($donneesA_B = $reqA_B->fetch())
		{
			//We get the price of the meal, divided by the number of people who ate
			//nbre de virgule + 1
			$deltaAB -= $donneesA_B['montant']/(substr_count($donneesA_B['copains'], ',') + 1 + $donneesA_B['invites']);
		}
		while($donneesB_A = $reqB_A->fetch()) //idem
		{
			$deltaAB += $donneesB_A['montant']/(substr_count($donneesB_A['copains'], ',') + 1 + $donneesB_A['invites']);
		}
		while($donneesPaiementsA_B = $reqPaiementsA_B->fetch()) //idem 
		{
			$deltaAB -= $donneesPaiementsA_B['montant'];
		}
		while($donneesPaiementsB_A = $reqPaiementsB_A->fetch()) //idem
		{
			$deltaAB += $donneesPaiementsB_A['montant'];
		}
		return $deltaAB;
	}

	// When we've "de", "a" and "rbmt", we have to remove the matrix block 
	// corresponding to it in "rbmt"
	if(isset($_GET['de']) && isset($_GET['a']) && isset($_GET['rbmt']))	
	{
		// If your are not the one to receive, or not admin, you've no right
		if($_GET['a'] == $_SESSION['id'] || !empty($_SESSION['admin']))
		{
			// If we have to insert a paiment, we get the matrix and replace the 
			// paiment with 0, then put it back in the database
			if(isset($_POST['confirm']))
			{
				$req = $bdd->query('SELECT matrix FROM remboursements WHERE id='.$_GET['rbmt']);
				$retour = $req->fetch();
				$matrix = unserialize($retour["matrix"]);
				$matrix[$_GET['de']][$_GET['a']] = 0;
				$req = $bdd->prepare('UPDATE remboursements SET matrix=:matrix WHERE id=:id');
				$req->bindValue(':matrix', serialize($matrix));
				$req->bindValue(':id', $_GET['rbmt']);
				$req->execute();
				header('location: message.php?id=10');
			}
			// We prompt to confirm the operation
			else
			{
?>
				<!DOCTYPE html>
			<html lang="fr">
				<head>
					<meta charset="utf-8">
					<title>Bouffe@Ulm : remboursements</title>
					<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
					<link rel="icon" href="favicon.ico" />
				</head>
				<body>
					<h1>Remboursements simplifiés</h1>
					<form method="post" action="rbmt.php?<?php echo $_SERVER['QUERY_STRING'];?>">
						<p>
							<input type="hidden" name="confirm" value="1"/>
							<input type="submit" value="Confirmer le remboursement" /> ou <a href="rbmt.php">retour aux remboursements</a><br/>
							<em>Attention, cette opération n'est pas réversible. Une fois le paiement confirmé, il est impossible de revenir en arrière.</em>
						</p>
					</form>
<?php
			}
		}
		else
			header('location: message.php?id=9');
	}
	// We print the list of elements in remboursements
	else
	{
?>	
			<!DOCTYPE html>
			<html lang="fr">
				<head>
					<meta charset="utf-8">
					<title>Bouffe@Ulm : remboursements</title>
					<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
					<link rel="icon" href="favicon.ico" />
				</head>
				<body>
					<h1>Remboursements simplifiés</h1>
<?php 
		echo '<h2>Remboursements précédents</h2>';
		$req = $bdd->prepare('SELECT * FROM remboursements');
		$req->execute();

		// We create a table string and a links string. The first will contain a 
		// matrix if we want to see one particular rbmt. Links will provide links 
		// to the other rbmt.
		$table = '';
		$links = '';
		while($data = $req->fetch())
		{
			// Reset the list of friend to ''
			$liste = '';
			// Extract info from $data
			// First the date
			$date = date('j/m', $data['date']);
			// List of friend
			$copains = unserialize($data['copains']);
			foreach($copains as $nom)
				$liste .= ', ' . $nom;
		
			// If we clicked on one particular rbmt
			if (isset($_GET['id']) && $_GET['id'] == $data['id'] && isset($_GET['action']) && $_GET['action'] == "show")
			{
				$table .= "<li>Le {$date}{$liste}";
				$matrix = unserialize($data['matrix']);
				$table.='
					<table>
						<tr>
						<th class="centre">Doit\À</th>';
				//Construct the header of the table 
				foreach($copains as $key => $copain)
				{
					if($_SESSION['nom'] == $copain)
						$copain = '<strong>'.$copain.'</strong>';
					$table .= '<th class="centre">'.$copain.'</th>';
				}
				$table .= '</tr>';
	
				//For each peer of buddy, print the block in the array
				foreach($copains as $keyA=>$copainA)
				{
					if($_SESSION['nom'] == $copainA)
						$copainA = '<strong>'.$copainA.'</strong>';
					$table .=  '<tr><th class="centre">'.$copainA.'</th>';
					foreach($copains as $keyB=>$copainB)
					{
						if($matrix[$keyA][$keyB] <= 0)
						 	$table .= '<td class="centre">-</td>';
						else
						{
							if ($keyB == $_SESSION['id'] || !empty($_SESSION['admin']))
								$table .= '<td class="centre"><a href="rbmt.php?rbmt='.$data['id'].'&amp;de='.$keyA.'&amp;a='.$keyB.'">'.$matrix[$keyA][$keyB].'</a></td>';
							else
								$table .= '<td class="centre">'.$matrix[$keyA][$keyB].'</td>';
						}
					}
					$table .= '</tr>';
				}
				$table .= '</table></li>';
			}
			// Else, we print a link
			else
				$links .= "<li><a href='?id={$data['id']}&amp;action=show'>Le {$date}{$liste}</a></li>";
		}
		echo '<p><ul>';
		if (isset($table)) echo $table;
		if (isset($links)) echo $links;
		echo '</ul></p>';
		echo '<p>';
		echo '<a href="index.php">Retour à l\'accueil</a>';
		echo '</p>';
		echo '</body></html>';
	}
?>
