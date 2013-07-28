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
	
	if(isset($_GET['del'])) //If we want to delete an expenditure
	{
		if(empty($_GET['valide']) OR empty($_GET['token']) OR $_GET['token'] != $_SESSION['token_del_depense'] OR $_SESSION['token_del_depense_time'] < time() - (15*60) OR strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') > 0 OR strpos($_SERVER['HTTP_REFERER'], 'https://'.$CONFIG['base_url'].'/index.php') > 0) //If we didn't click the link to validate the deletion and the token is not valid (not present or older than 15 minutes) or if the referer is not ok
		{
			$_SESSION['token_del_depense'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
			$_SESSION['token_del_depense_time'] = time(); //We also store the time at which the token has been generated
			$lien = 'index.php?del='.$_GET['del'] .'&amp;valide=1&amp;token='.$_SESSION['token_del_depense'];
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
					<h1>Validation de la suppression du repas</h1>
					<p><a href="<?php echo $lien;?>">Confirmer la suppression</a> ou <a href="index.php">Retour</a></p>
				</body>
			</html>
			
<?php
			exit();
		}
		//else, we can delete the expenditure
		$id = (int) $_GET['del'];
		$bdd->query('DELETE FROM depenses WHERE id='.$id);
		$bdd->query('DELETE FROM paiements WHERE id_depense='.$id);
		
		header('location: message.php?id=1');
		exit();
	}
	
	//This get all the friends' name (we need it next)
	$req2 = $bdd->query('SELECT id, nom FROM copains ORDER BY nom ASC');
	while($copain = $req2->fetch())
	{
		$copains[$copain['id']] = $copain['nom']; //And put it in an array
	}
	
	$req_jeu = $bdd->prepare('SELECT COUNT(*) AS nbre_jeu FROM depenses WHERE de=:de');
	$req_jeu->bindValue(':de', $_SESSION['id']);
	$req_jeu->execute();
	
	$donnees_jeu = $req_jeu->fetch(); //To define wether we display the game or not
	
	//SESSION token for the update of what people must pay to us (modif.php)
	$_SESSION['token_validate_single'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
	$_SESSION['token_validate_single_time'] = time(); //We also store the time at which the token has been generated
?>		
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Bouffe@Ulm</title>
		<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
		<link rel="icon" href="favicon.ico" />
		<?php
			if($donnees_jeu['nbre_jeu'] >= 1 || $_SESSION['admin'] == 1)
			{
		?>
			<link href="misc/background.css" rel="stylesheet" type="text/css">
			<script type="text/javascript" src="misc/jquery.min.js"></script>
			<script type="text/javascript" src="misc/background.js"></script>
		<?php
			}
		?>
	</head>
	<body>
		<h1>Bouffe@Ulm</h1>
		<?php 
            if(is_file("annonce"))
            {
        ?>
				<p style="font-weight: bold; color: red; text-align: center; border: 1px solid red; padding-top: 10px; padding-bottom: 10px; font-size: 1.5em;"><?php echo nl2br(file_get_contents('annonce'));?></p>
		<?php
			}
			if(isset($_SESSION['nom']) && $_SESSION['nom'] == 'Grégoire') //Special thing for our friend Gregoire !
			{
				if(empty($_GET['aClique']) OR $_GET['aClique'] != 42) //If 10 seconds timeout ok
				{
					echo '
				<a href="?aClique=42"><pre>
                     xxxxx
                  xXXXXXXXXXx
                 XXXXXXXXXXXXX
                xXXXXXXXX  XXXx
                XXXXXXXXX 0XXXX\\\\\\
               xXXXXXXXXXxxXXXX\\\\\\\
               XXXXXXXXXXXXXXXX////// \
               XXXXXXXXXXXXXXXXX
               XXXXX|\XXX/|XXXXX
               XXXXX| \-/ |XXXXX
              xXXXXX| [ ] |XXXXXx
            xXXXX   | /-\ |   XXXXx
         xXXXXX     |/   \|     XXXXXx
       xXXXXXX                   XXXXXXx
      xXXXXXXX                   XXXXXXXx
     xXXXXXXXX                   XXXXXXXXx
    xXXXXXXXXX                   XXXXXXXXXx
   xXXXXXXXXXX                   XXXXXXXXXXx
  xXXXXXXXXXXX                   XXXXXXXXXXXx
 xXXXXXXXX XXX                   XXX XXXXXXXXx
 XXXXXXXX  XXX                   XXX  XXXXXXXX
xXXXXXXX   XXX                   XXX   XXXXXXXx
XXXXXX     XXX                   XXX     XXXXXX
XXXX       XXX                   XXX       XXXX
 XX        XXX                   XXX        XX
           XXX                   XXX
           XXX                   XXX
           XXX                   XXX
           XXX                   XXX
           XXXx                 xXXX
           XXXXXXXXXXXXXXXXXXXXXXXXX
           XXXXXXX           XXXXXXX
       ____XXXXXX             XXXXXX____
      /________/               \________\</pre></a></body></html>';
      				exit();
				}
				else
				{
					$_SESSION['aClique_time'] = time();
				}
			}
		?>
		
		<p>
			<a href="modif.php">Ajouter une dépense</a> | <a href="modif_password.php">Modifier le mot de passe</a> | <a href="rbmt.php">Consulter les remboursements</a> | 
			<?php if(!empty($_SESSION['admin']))
			{
			?>
				<a href="rbmt_admin.php">Gérer les remboursements</a> | 
				<a href="copains.php">Modifier les copains</a> | 
				<a href="modif_annonce.php">Modifier l'annonce d'accueil</a> | 
			<?php
			}
			?>
			<a href="connexion.php?deco=1">Déconnexion</a>
		</p>
		
		<h2>Qui doit quoi ?</h2>
				<p>Lire "ligne" doit "case"€ à "colonne". Les liens permettent de confirmer le paiement des dettes.</p> <!-- Read "line" must pay "case"€ to "column" -->
				<table>
					<tr>
						<th class="centre">Doit\À</th>
						<?php
							//Construct the header of the table and display it for the previous months
							foreach($copains as $copain)
							{
								if($_SESSION['nom'] == $copain)
									$copain = '<strong>'.$copain.'</strong>';
								echo '<th class="centre">'.$copain.'</th>';
							}
						?>
					</tr>
					<?php
						$mois = date('n');
						$annee = date('Y');
						$bornes = bornes_mois($mois, $annee);
						$debut_mois = 0;
						$fin_mois = $bornes[1];
						
				
						foreach($copains as $keyA=>$copainA)
						{
							if($_SESSION['nom'] == $copainA)
								$copainA = '<strong>'.$copainA.'</strong>';
							echo '<tr><th class="centre">'.$copainA.'</th>';
							foreach($copains as $keyB=>$copainB)
							{
								$deltaAB = dettes($keyA,$keyB, $debut_mois, $fin_mois);
								if(round($deltaAB,2) <= 0) echo '<td class="centre">-</td>';
								else
								{
									echo '<td class="centre"><a href="valider_paiements.php?de=' . $keyA . '&amp;a=' . $keyB . '&amp;date=all">' . round($deltaAB, 2) . '€</a></td>';
									$lien_valider_tous[$keyB] = 1;
								}
							}
							echo '</tr>';
						}
						echo '<tr><th>Validation</th>';
						
						foreach($copains as $key=>$copain)
						{
							if(($_SESSION['nom'] == $copain OR $_SESSION['admin'] == 1) && !empty($lien_valider_tous[$key]))
								echo '<td><a href="valider_paiements.php?all=1&amp;a='.$key.'&amp;date=prev">Confirmer paiements</a></td>';
							else
								echo '<td></td>';
						}
						echo '</tr>';
					?>
				</table>
		
		<?php
		if(empty($_GET['all'])) echo '<h2>Dépenses détaillées du mois actuel</h2>';
		else echo '<h2>Dépenses détaillées</h2>';
		
		//Then we display all the expenditures
		?>
		
		<table>
			<tr>
				<th class="centre">Date <a class='text-deco-none' href='?tri=date&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=date&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
				<th class="centre">Payé par <a class='text-deco-none' href='?tri=de&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=de&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
				<th class="centre">Copains <a class='text-deco-none' href='?tri=copains&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=copains&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
				<th class="centre">Montant <a class='text-deco-none' href='?tri=depense&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=depense&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
				<th class="centre">Menu <a class='text-deco-none' href='?tri=menu&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=menu&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
				<th class="centre">Modifier</th>
				<th class="centre">Supprimer</th>
			</tr>
			<?php
				//Limites :
				$bornes = bornes_mois(date('n'), date('y'));
				$debut_mois = $bornes[0];
				$fin_mois = $bornes[1];
				if(!empty($_GET['all'])) $debut_mois = 0;
				
				//First, we get the expenditures we want
				if(isset($_GET['tri']) && isset($_GET['sens']) && in_array($_GET['tri'], array('id', 'menu', 'date', 'de', 'copains', 'montant')) && ($_GET['sens'] == 'asc' || $_GET['sens'] == 'desc'))
				{
					$req = $bdd->query('SELECT id, menu, date, de, copains, montant, invites FROM depenses WHERE date>'.$debut_mois.' AND date<'.$fin_mois.' ORDER BY '.$_GET['tri'].' '.$_GET['sens'].', date DESC');
				}
				else
				{
					$req = $bdd->query('SELECT id, menu, date, de, copains, montant, invites FROM depenses WHERE date>'.$debut_mois.' AND date<'.$fin_mois.' ORDER BY date DESC');
				}
				
				while($donnees = $req->fetch())
				{
					//Date (AM/PM)
					$AM_PM = array('AM'=>'le midi', 'PM'=>'le soir');
					$date = date('j/m', $donnees['date']).' '.$AM_PM[date('A', $donnees['date'])];
				
					$id = (int) $donnees['id'];
					
					$copains_in_array_id = explode(',', $donnees['copains']); //List of friends who ate (array)
					$copains_in = '';
					$nombre_participants = count($copains_in_array_id);
					
					$req_paiements = $bdd->query('SELECT de, montant FROM paiements WHERE id_depense='.$id); //List of who paid yet
					
					$paiements = array();
					$montants = array();
					while($paiement = $req_paiements->fetch())
					{
						// We use an array to store the list of friends who paid and so to 
						// avoir a useless 2D array search
						$paiements[$paiement['de']] = $paiement['de']; 
						// If we already defined $montant[]
						if (isset($montants[$paiement['de']]))
							$montants[$paiement['de']] += $paiement['montant'];
						else
							$montants[$paiement['de']] = $paiement['montant'];	
					}

					// Friend number 0 is none
					$copains[0] = "Tout seul"; 
					
					$copains_in_array_name = array();
					
					//Prepare an array with buddy names to sort it
					foreach($copains_in_array_id as $key=>$id_copain) 					{
						$copains_in_array_name[$key] = $copains[(int) $id_copain];
					}
					asort($copains_in_array_name);
					
					$i = 0;
					// What to write in the friends cell
					foreach($copains_in_array_name as $key=>$copain)					
					{
						$copains_in .= $copain;
						$id_copain = $copains_in_array_id[$key];
					
						$keys = array_keys($paiements, $id_copain);

						if($id_copain != $donnees['de'])
						{
							$montant_du = (float) $donnees['montant']/(substr_count($donnees['copains'], ',') + 1 + $donnees['invites']);
							if(!empty($keys))
							{
								if(round($montants[$keys[0]],2) == round($montant_du,2))
									$copains_in .= ' (payé)';
								else
									$copains_in .= ' (<a href="modif.php?de='.$id_copain.'&amp;id_depense='.$id.'&amp;a='.$donnees['de'].'&amp;token='.$_SESSION['token_validate_single'].'">reste '.round($montant_du - $montants[$keys[0]],2).'€</a>)';
							}
							else
								$copains_in .= ' (<a href="modif.php?de='.$id_copain.'&amp;id_depense='.$id.'&amp;a='.$donnees['de'].'&amp;token='.$_SESSION['token_validate_single'].'">reste '.round($montant_du,2).'€</a>)';
						}
					
						if($i != $nombre_participants-1)
							$copains_in .= '<br/>';
						
						$i++;
					}
					
					$invites = '';
					if($donnees['invites'] == 1)
						$invites = '<br/>'. (int) $donnees['invites'].' invité';
					if($donnees['invites'] > 1)
						$invites = '<br/>'. (int) $donnees['invites'].' invités';
					
					//Only the admin and the one who paid the meal can edit it
					if((int) $donnees['de'] == $_SESSION['id'] || $_SESSION['admin'] == 1) 					{
						$modif_link = '<a href="modif.php?id='.$id.'">Modifier</a>';
						$suppr_link = '<a href="?del='.$id.'">Supprimer</a>';
					}
					else
					{
						$modif_link = '';
						$suppr_link = '';
					}
					
					echo '<tr>
						<td>'.$date.'</td>
						<td>'.$copains[(int) $donnees["de"]].'</td>
						<td>'.$copains_in.$invites.'</td>
						<td>'.(float) $donnees['montant'].'€</td>
						<td>'.nl2br(htmlspecialchars($donnees["menu"])).'</td>
						<td>'.$modif_link.'</td>
						<td>'.$suppr_link.'</td>
					       </tr>';
				}
				$req->closeCursor();
			?>
		</table>
		<p>
			<?php
				if(!empty($_GET['all']))
				{
					echo '<a href="index.php">N\'afficher que les dépenses du dernier mois.</a>';
				}
				else
				{
					echo '<a href="index.php?all=1">Afficher toutes les dépenses</a>';
				}
			?>
		</p>
		<?php
			if($donnees_jeu['nbre_jeu'] >= 1 || $_SESSION['admin'] == 1 || $_SESSION['nom'] == 'Alexandre') //Bonus : display a "My little Poney" game
			{
		?>
				<div id="fake"></div>
				<div class="section" id="empty"></div>
				<div id="background">
					<div class="level">
					<div class="mario-sprite" style="left: 256.3720261632001px; bottom: 112px; "><img src="misc/mariosprite.png"></div>
				</div>
		<?php
			}
		?>
	</body>
</html>
