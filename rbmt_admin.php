<?php
	require('include.php');
	init(true, true);
		
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
		//What A already paid to B 
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
			//nb of comma + 1
			$deltaAB -= $donneesA_B['montant']/(substr_count($donneesA_B['copains'], ',') + 1 + $donneesA_B['invites']);
		}
		while($donneesB_A = $reqB_A->fetch()) //idem
		{
			$deltaAB += $donneesB_A['montant']/(substr_count($donneesB_A['copains'], ',') + 1 + $donneesB_A['invites']);
		}
		while($donneesPaiementsA_B = $reqPaiementsA_B->fetch()) //what has been already paid 
		{
			$deltaAB -= $donneesPaiementsA_B['montant'];
		}
		while($donneesPaiementsB_A = $reqPaiementsB_A->fetch()) //idem
		{
			$deltaAB += $donneesPaiementsB_A['montant'];
		}
		return $deltaAB;
	}
	
	// Reduces the matrix in n steps
	function simplify ($copains){
		// We work directly with $debts
		global $debts;
		global $matrix;
		$max = maximum($debts);
		// On cherche le plus proche en valeur absolue du maximum
		$near = nearest($debts, $max);
		// On en déduit l'écart entre leurs valeurs absolues
		$delta = $max["val"] + $near["val"];
		// Case :
		//A : max		 100
		//B :near		-90
		// delta		 10
		// m[A][B]	-90
		// m[B][A]	 90	
		if ($delta > 0){
			$matrix[$max["id"]][$near["id"]] = -$debts[$near["id"]];
			$matrix[$near["id"]][$max["id"]] = $debts[$near["id"]];
			
			$debts[$max["id"]] = $delta;
			$debts[$near["id"]] = 0;
		}
		else {
			$matrix[$max["id"]][$near["id"]] = $debts[$max["id"]];
			$matrix[$near["id"]][$max["id"]] = -$debts[$max["id"]];
			
			$debts[$max["id"]] = 0;
			$debts[$near["id"]] = $delta;
		}
		return $matrix;
	}

	// Return the nearest value
	function nearest($debts, $max)
	{
		// Initialize with the maximum distance
		$d_min = max($debts) - min($debts);
		// Initialize the default id to one key in the array
		$debts_keys = array_keys($debts);
		$id_min = $debts_keys[1];
		foreach($debts as $id => $val)
		{
			// Computes the absolute distance
			$d = abs($max["val"] + $val);	
			// If it's a new minimum AND the value isn't positive
			if ($d <= $d_min && $val < 0) 
			{
				$id_min = $id;
				$d_min = $d;
			}
		}
		return array("id"=>$id_min, "val"=>$debts[$id_min]);
	}	

	function maximum($tableau)
	{
		$max["id"] = 0;
		$max["val"] = 0;
		foreach($tableau as $id=>$val)
		{
			if($val >= $max["val"])
			{
				$max["val"] = $val;
				$max["id"] = $id;
			}
		}
		return $max;
	}
 
	// Function to insert a regulation between A and B for the simplification 
	// system only.
	function inserer_paiement_rbmt($donnees_depense, $de_paiement, $a_paiement, $rbmt)
	{
		if ($de_paiement == $a_paiement) return 1;
		global $bdd;
		// We get all the payments between a and de (we just filter thanks to the 
		// id which is linked to the a field)
		$paiement_existe_req = $bdd->prepare('SELECT montant, COUNT(*) AS nbre_paiement FROM paiements WHERE id_depense=:id_depense AND de=:de');
		$paiement_existe_req->bindValue(':id_depense', $donnees_depense['id']);
		$paiement_existe_req->bindValue(':de', $de_paiement);
		$paiement_existe_req->execute();

		$deja_paye = 0;
		while ($donnees = $paiement_existe_req->fetch())
		{
			$deja_paye += $donnees['montant'];
		}

		$montant = $donnees_depense['montant']/(substr_count($donnees_depense['copains'], ',') + 1 + $donnees_depense['invites']) - $deja_paye;
		
		$req = $bdd->prepare('INSERT INTO paiements(id, de, a, id_depense, date, montant, rbmt) VALUES("", :de, :a, :id_depense, :date, :montant, :rbmt)');
		$req->bindValue(':de', $de_paiement);
		$req->bindValue(':a', $a_paiement);
		$req->bindValue(':id_depense', $donnees_depense['id']);
		$req->bindValue(':date', time());
		$req->bindValue(':rbmt', $rbmt);
		$req->bindValue(':montant', $montant);
		$req->execute();
		return 1;
	}


		if(!(isset($_GET['confirm']) && isset($_POST['matrix']) && isset($_POST['copains']) && isset($_POST['date'])))
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
		}
		// Creation Step 1
		// show a form to get more info
		if(isset($_GET["action"]) && $_GET["action"] == "new")
		{
			$req = $bdd->query('SELECT id, nom FROM copains ORDER BY nom ASC');
			while($copain = $req->fetch())
			{
				$copains[$copain['id']] = $copain['nom']; //And put it in an array
			}
?>
			<form method="post" action="rbmt_admin.php?">
				<p><label for="jour">Date : </label>
					<select name="jour" id="jour">
<?php
			for($i=1; $i<32; $i++)
			{
				if((date('j') == $i && !isset($modif)) || (isset($modif) && date('j', $donnees['date']) == $i))
					echo "<option value='".$i."' selected='selected'>".$i."</option>";
				else
					echo "<option value='".$i."'>".$i."</option>";
			}
?>
						</select>
						<select name="mois" id="mois">
<?php
								for($i=1; $i<13; $i++)
								{
									if((date('m') == $i && !isset($modif)) || (isset($modif) && date('m', $donnees['date']) == $i))
										echo "<option value='".$i."' selected='selected'>".$i."</option>";
									else
										echo "<option value='".$i."'>".$i."</option>";
								}
?>
						</select>
						<select name="AM_PM">
							<option value='11' <?php if((date('A') == "AM" && !isset($modif)) || (isset($modif) && date('A', $donnees['date']) == "AM")) { echo 'selected="selected"';}?>>Midi</option>
							<option value='22' <?php if((date('A') == "PM" && !isset($modif)) || (isset($modif) && date('A', $donnees['date']) == "PM")) { echo 'selected="selected"';}?>>Soir</option>
						</select>
						<select name="annee" id="annee">
<?php
							for($i=date('Y')-1; $i<date('Y')+2; $i++)
							{
								if((date('Y') == $i && !isset($modif)) || (isset($modif) && date('Y', $donnees['date']) == $i))
									echo "<option value='".$i."' selected='selected'>".$i."</option>";
									else
										echo "<option value='".$i."'>".$i."</option>";
							}
?>
						</select>
					</p>
					<p style="text-align: left; display: inline-block;">Copains : <br/>
<?php
							$req2 = $bdd->query('SELECT id, nom FROM copains ORDER BY nom ASC');
							while($donnees2 = $req2->fetch())
							{
								echo "<input type='checkbox' name='copain_".htmlspecialchars($donnees2['id'])."' id='copain_".htmlspecialchars($donnees2['id'])."' checked='checked'/><label for='copain_".htmlspecialchars($donnees2['id'])."' class='inline'>".htmlspecialchars($donnees2['nom'])."</label><br/>";
							}
?>
					</p>
					<p>
						<input type="submit" value="<?php if(isset($modif)) { echo 'Modifier'; } else { echo 'Ajouter';}?>"/> ou <a href="index.php">retour à l'accueil</a><input type="hidden" name="id" value="<?php if(isset($modif)) { echo $modif;}?>"/>
						<input type="hidden" name="token" value="<?php echo $_SESSION['token_modif'];?>"/>
					</p>
				</form>
		
<?php
		}
		// Creation Step 2
		// check there's everything, create the new simplified matrix and prompt 
		// for confirmation 
		else if (isset($_POST['jour']) && isset($_POST['mois']) && isset($_POST['annee']) && isset($_POST['AM_PM']))
		{
			$req = $bdd->prepare('SELECT nom, id FROM copains ORDER BY nom ASC');
			$req->execute();
			// Fill an array only with friends who are participating
			while($copain = $req->fetch())
			{
				// If the friend was selected
				if(isset($_POST['copain_'.$copain['id']]))
					$copains[$copain['id']] = $copain['nom'];
			}
			$n = count($copains);	// Usefull for the size of the array
			
			// Create the temporal bounds for the requests
			$debut_mois = 0;
			$fin_mois = mktime($_POST['AM_PM'], 0, 0, $_POST['mois'], $_POST['jour'], $_POST['annee']);
			// We create an array containing the total debts and a matrix
			foreach($copains as $idA=>$nameA)
			{
				$debts[$idA] = 0;
				foreach($copains as $idB=>$nameB)
				{
					$matrix[$idA][$idB] = 0;
				}
			}
			// We initialize the debts array
			foreach($copains as $keyA=>$copainA)
			{
				foreach($copains as $keyB=>$copainB)
				{
					$dette =  dettes($keyA,$keyB, $debut_mois, $fin_mois);
					$deltaAB = $dette;
					$debts[$keyA] += $deltaAB;
				}
			}
			// To avoid an infinite while loop, we have to round the value
			foreach($debts as &$val)
				$val = round($val, 2);
	
			// Should be zero, but with float error, it may be non null
			$error = array_sum($debts);
	
			// Do it in n steps
			for ($i=0; $i<$n; $i++)
				$matrix = simplify($copains);
	
			echo "<h2>Récapitulatif</h2><p>";
			// Output the matrix
			foreach($copains as $keyA=>$copainA)
			{
				foreach($copains as $keyB=>$copainB)
				{
					if (isset($matrix[$keyA][$keyB]) && $matrix[$keyA][$keyB] > 0)
						echo '<b>' . $copains[$keyA]  . '</b> doit '. round($matrix[$keyA][$keyB], 2) .  ' à <b>' . $copains[$keyB] .'</b><br/>';
				}
			}
			// Show a forme to confirm
?>
			</p>
			<form method="post" action="rbmt_admin.php?confirm=1">
				<p>
					<input type="hidden" name="date" value='<?php echo $fin_mois;?>' />					
					<input type="hidden" name="matrix" value='<?php echo serialize($matrix); ?>'/>
					<input type="hidden" name="copains" value='<?php echo serialize($copains); ?>'/>
					<input type="submit" value="Confirmer"/> ou <a href="index.php">Retour à l'accueil</a><br/>
				<em>Attention, cette opération est irréversible et annulera tous les paiements jusqu'à la date choisie.</em>
			</p>
		</form>
<?php
	}
	// Creation Step 3
	// let's include some stuff 
	else if (isset($_GET['confirm']) && isset($_POST['matrix']) && isset($_POST['copains']) && isset($_POST['date']))
	{
		$req = $bdd->prepare('INSERT INTO remboursements(date, matrix, id, copains) VALUES(:date, :matrix, "", :copains)');
		$req->bindValue(':date', $_POST['date']);
		$req->bindValue(':matrix', $_POST['matrix']);
		$req->bindValue(':copains', $_POST['copains']);
		// We insert the new matrix in the table
		if (!$req->execute())
			echo "Une erreur est survenue";
		else
		{
			// We get the biggest id (here, the one we just created)
			$req = $bdd->query('SELECT MAX(id) FROM remboursements');
			$retour = $req->fetch();
			$id = $retour["MAX(id)"];
			// Now we can simplify the matrix by inserting as many regulation as 
			// necessary
			$copains = unserialize($_POST['copains']);
			foreach($copains as $a=>$nomA)
			{
				foreach($copains as $de=>$nomB)
				{
					$req = $bdd->prepare('SELECT id, de, copains, montant, invites FROM depenses WHERE (copains LIKE "%,'.$de.',%" OR copains LIKE "%,'.$de.'" OR copains LIKE "'.$de.',%" OR copains LIKE "'.$de.'") AND de=:a AND date>:debut_mois AND date<:fin_mois');
					$req->bindValue(':a', $a);
					// Bounds are 0 and the date given in the previous form
					$req->bindValue(':debut_mois', 0);
					$req->bindValue(':fin_mois', $_POST['date']);
					$req->execute();
					// We've got a bunch of exchange to insert (with a flag to specify we 
					// added it here)
					while($donnees = $req->fetch())
					{
						if(!empty($de))
						{
							inserer_paiement_rbmt($donnees, $de, $a, $id);
						}
						else
						{
							//For all the people who participate...
							$participants = explode(',', $donnees['copains']);
							foreach($participants as $participant)
							{
								// If we've got someone which participates to the 
								// simplification
								if (in_array($participant, array_keys($copains)))
									inserer_paiement_rbmt($donnees, $participant, $a, $id);
							}
						}
					}
				}
			}
			// Everything went right
			header('location: message.php?id=14');
		}
	}
	// If we want to delete one and have the id
	else if(isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == "del")
	{
		// If already confirmed
		if(isset($_POST['confirm']))
		{
			// We delete the simplification AND the regulation it created
			if($bdd->query('DELETE FROM remboursements WHERE id='.$_GET['id']) &&
				 $bdd->query('DELETE FROM paiements WHERE rbmt='.$_GET['id']))
				header('location: message.php?id=15');
			else
				header('location: message.php?id=16');
		}
		else
		{
			$req = $bdd->prepare('SELECT * FROM remboursements WHERE id='.$_GET['id']);
			?>
				<form method="post" action="rbmt_admin.php?<?php echo $_SERVER['QUERY_STRING'];?>">
				<p>
					<input type="submit" value="Confirmer" /> ou <a href="index.php">Retour à l'accueil</a> 
					<input type="hidden" name="confirm" value="1"/>
				</p>
				</form>
			<?php
		}
	}
	// Else, we print the list
	else
	{
		echo '<a href="?action=new">Nouveau remboursement</a> | <a href="index.php">Retour à l\'accueil</a>';
		echo '<h2>Remboursements précédents</h2>';
		$req = $bdd->prepare('SELECT * FROM remboursements ');
		$req->execute();
		
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
		
			// If we clicked on one particular remboursement
			if (isset($_GET['id']) && $_GET['id'] == $data['id'] && isset($_GET['action']) && $_GET['action'] == "show")
			{
				$table .= "<li>Le {$date}{$liste} - <a href='?id={$data['id']}&amp;action=del'>Supprimer</a>";
				$matrix = unserialize($data['matrix']);

				// We build an array containing the data we want to print
				// and a list containing the other remboursement
				$table.='
					<table>
						<tr>
						<th class="centre">Doit\À</th>';

				//Construct the header of the table and display it for the previous months
				foreach($copains as $key => $copain)
				{
					if($_SESSION['nom'] == $copain)
						$copain = '<strong>'.$copain.'</strong>';
					$table .= '<th class="centre">'.$copain.'</th>';
				}
				$table .= '</tr>';
	
				foreach($copains as $keyA=>$copainA)
				{
					if($_SESSION['nom'] == $copainA)
						$copainA = '<strong>'.$copains[$keyA].'</strong>';
					$table .=  '<tr><th class="centre">'.$copainA.'</th>';
					foreach($copains as $keyB=>$copainB)
					{
						if($matrix[$keyA][$keyB] <= 0)
							$table .= '<td class="centre">-</td>';
						else
							$table .= '<td class="centre">'.$matrix[$keyA][$keyB].'</td>';
					}
					$table .= '</tr>';
				}
				$table .= '</table></li>';
			}
			// Else, we print a link
			else
				$links .= "<li><a href='?id={$data['id']}&amp;action=show'>Le {$date}{$liste}</a> - <a href='?id={$data['id']}&amp;action=del'>Supprimer</a></li>";
		}
	}
	echo '<p><ul>';
	if (isset($table)) echo $table;
	if (isset($links)) echo $links;
	echo '</ul></p>';
	echo '<p>';
	echo '<a href="index.php">Retour à l\'accueil</a>';
	echo '</p>';
	echo '</body></html>';

?>
