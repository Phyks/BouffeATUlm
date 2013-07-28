<?php
	require('include.php');
	init(true, false);
	
	//If we want to validate something someone paid us
	if(isset($_GET['de']) && isset($_GET['a']) && isset($_GET['id_depense']) && !empty($_GET['token']) && $_GET['token'] == $_SESSION['token_validate_single'] && $_SESSION['token_validate_single_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0)
	{
		//Check wether we are the person who receive the money or the admin
		if($_SESSION['id'] == $_GET['a'] || $_SESSION['admin'] == 1)
		{
			//And check that we didn't validate it before
			$req_count = $bdd->prepare('SELECT COUNT(*) AS nbre_paiements FROM paiements WHERE de=:de AND a=:a AND id_depense=:id_depense');
			$req_count->bindValue(':de', $_GET['de']);
			$req_count->bindValue(':a', $_GET['a']);
			$req_count->bindValue(':id_depense', $_GET['id_depense']);
			$req_count->execute();
			
			$count = $req_count->fetch();
			
			if($count['nbre_paiements'] == 0) //If everything is ok -> validation
			{
				$req = $bdd->prepare('INSERT INTO paiements(id, de, a, id_depense, date, montant) VALUES("", :de, :a, :id_depense, :date, :montant)');
				$req->bindValue(':de', $_GET['de']);
				$req->bindValue(':a', $_GET['a']);
				$req->bindValue(':id_depense', $_GET['id_depense']);
				$req->bindValue(':date', time());
				
				$req_montant = $bdd->prepare('SELECT montant, copains, invites FROM depenses WHERE id=:id_depense');
				$req_montant->bindValue(':id_depense', $_GET['id_depense']);
				$req_montant->execute();
				$donnees_montant = $req_montant->fetch();
				$montant = $donnees_montant['montant']/(substr_count($donnees_montant['copains'], ',') + 1 +  $donnees_montant['invites']);
				
				$req->bindValue(':montant', $montant);
				$req->execute();
			}
			else //If entry already exist -> we update it because the cost of the meal may have been changed
			{
				$req_montant = $bdd->prepare('SELECT montant, copains, invites FROM depenses WHERE id=:id_depense');
				$req_montant->bindValue(':id_depense', $_GET['id_depense']);
				$req_montant->execute();
				$donnees_montant = $req_montant->fetch();
				$montant = $donnees_montant['montant']/(substr_count($donnees_montant['copains'], ',') + 1 + $donnees_montant['invites']);
				
				$req = $bdd->prepare('UPDATE paiements SET montant=:montant, date=:date WHERE de=:de AND a=:a AND id_depense=:id_depense');
				$req->bindValue(':de', $_GET['de']);
				$req->bindValue(':a', $_GET['a']);
				$req->bindValue(':id_depense', $_GET['id_depense']);
				$req->bindValue(':date', time());
				
				$req->bindValue(':montant', $montant);
				$req->execute();
			}
			
			header('location: message.php?id=10');
			exit();
		}
		else
		{
			header('location: message.php?id=9');
			exit();
		}
	}
	
	//If we want to add a new meal (or edit it)
	if(isset($_POST['menu']) && isset($_POST['jour']) && isset($_POST['mois']) && isset($_POST['annee']) && isset($_POST['AM_PM']) && isset($_POST['montant'])  && isset($_POST['invites']) && !empty($_POST['token']) && $_POST['token'] == $_SESSION['token_modif'] && $_SESSION['token_modif_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0)
	{
		if(!empty($_POST['id']))
		{
			$req = $bdd->query('SELECT de FROM depenses WHERE id='.(int) $_POST['id']);
			$donnees = $req->fetch();
			
			if($donnees['de'] != $_SESSION['id'] && $_SESSION['admin'] != 1)
			{
				header('location: message.php?id=9');
				exit();
			}
			
			$req = $bdd->prepare('UPDATE depenses SET menu=:menu, date=:date, montant=:montant, copains=:copains, invites=:invites WHERE id='.(int) $_POST['id']);
			$message = 2;
		}
		else
		{
			$req = $bdd->prepare('INSERT INTO depenses (id, menu, date, de, copains, montant, invites) VALUES ("", :menu, :date, '.$_SESSION['id'].', :copains, :montant, :invites)');
			$message = 3;
		}
		
		//Here, we treat $_POST['copain_...']
		$copains_req = $bdd->query('SELECT id FROM copains ORDER BY id ASC');
		$i = 0;
		$copains_insert = '';
		
		while($copain_base = $copains_req->fetch())
		{
			if(!empty($_POST['copain_'.$copain_base['id']]))
			{
				if($i != 0)
				{
					$copains_insert .= ',';
				}
				$copains_insert .= $copain_base['id'];
				$i = 1;
			}
		}
		
		$req->bindValue(':menu', $_POST['menu']);
		$req->bindValue(':date', mktime($_POST['AM_PM'], 0, 0, $_POST['mois'], $_POST['jour'], $_POST['annee']));
		$req->bindValue(':copains', $copains_insert);
		$req->bindValue(':montant', (float) strtr($_POST['montant'], ',', '.'));
		$req->bindValue(':invites', (int) $_POST['invites']);
		$req->execute();
		header('location: message.php?id='.$message);
		exit();
	}
	else //Else, we just display the form
	{		
		if(isset($_GET['id'])) //And get the data to prefill if we edit a meal
		{
			$modif = (int) $_GET['id'];
			$req = $bdd->query('SELECT menu, de, date, copains, montant, invites FROM depenses WHERE id='.$modif);
			
			$donnees = $req->fetch();
			
			if($donnees['de'] != $_SESSION['id'] && $_SESSION['admin'] != 1)
			{
				header('location: message.php?id=9');
				exit();
			}
			
			$copains_modif = explode(',', $donnees['copains']);
		}
		$_SESSION['token_modif'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
		$_SESSION['token_modif_time'] = time(); //We also store the time at which the token has been generated
?>		
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Bouffe@Ulm</title>
		<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
		<script type="text/javascript" src="misc/modif.js"></script>
		<link rel="icon" href="favicon.ico" />
	</head>
	<body>
		<h1>Bouffe@Ulm</h1>
		<?php
			if(isset($modif)) echo '<h2>Modifier une dépense</h2>'; else echo '<h2>Ajouter une dépense</h2>';
		?>
		<form method="post" action="modif.php">
			<p><label for="menu">Menu : </label><textarea name="menu" value="menu" cols="40" rows="5"><?php if(isset($modif)) { echo nl2br(htmlspecialchars($donnees['menu']));}?></textarea></p>
			<p><label for="jour">Date : </label>
				<select name="jour" id="jour">
					<?php
						for($i=1; $i<32; $i++)
						{
							if((date('j') == $i && !isset($modif)) || (isset($modif) && date('j', $donnees['date']) == $i))
							{
								echo "<option value='".$i."' selected='selected'>".$i."</option>";
							}
							else
							{
								echo "<option value='".$i."'>".$i."</option>";
							}
						}
					?>
				</select>
				<select name="mois" id="mois">
					<?php
						for($i=1; $i<13; $i++)
						{
							if((date('m') == $i && !isset($modif)) || (isset($modif) && date('m', $donnees['date']) == $i))
							{
								echo "<option value='".$i."' selected='selected'>".$i."</option>";
							}
							else
							{
								echo "<option value='".$i."'>".$i."</option>";
							}
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
							{
								echo "<option value='".$i."' selected='selected'>".$i."</option>";
							}
							else
							{
								echo "<option value='".$i."'>".$i."</option>";
							}
						}
					?>
				</select>
			</p>
			<p><label for="montant">Montant : </label><input type="text" size="5" maxlength="6" name="montant" id="montant" <?php if(isset($modif)) echo 'value="'.$donnees['montant'].'"';?>/>€</p>
			<p style="text-align: left; display: inline-block;">Copains : <br/>
				<?php
					$req2 = $bdd->query('SELECT id, nom FROM copains ORDER BY nom ASC');
					while($donnees2 = $req2->fetch())
					{
						if((isset($copains_modif) && in_array($donnees2['id'], $copains_modif)) || ($_SESSION['id'] == $donnees2['id']))
						{
							echo "<input type='checkbox' name='copain_".htmlspecialchars($donnees2['id'])."' id='copain_".htmlspecialchars($donnees2['id'])."' checked='checked'/><label for='copain_".htmlspecialchars($donnees2['id'])."' class='inline'>".htmlspecialchars($donnees2['nom'])."</label><br/>";
						}
						else
						{
							echo "<input type='checkbox' name='copain_".htmlspecialchars($donnees2['id'])."' id='copain_".htmlspecialchars($donnees2['id'])."'/><label for='copain_".htmlspecialchars($donnees2['id'])."' class='inline'>".htmlspecialchars($donnees2['nom'])."</label><br/>";
						}
					}
				?>
				<input type="number" name="invites" id="invites" size="2" maxlength="2" value="<?php if(isset($donnees['invites'])) echo (int) $donnees['invites']; else echo "0";?>"/>
				<label for="invites" class="inline" id="invites_label">
					<?php if(isset($donnees['invites']) && $donnees['invites'] > 1) echo 'invités'; else echo 'invité';?>
				</label>
			</p>
			<p>
				<input type="submit" value="<?php if(isset($modif)) { echo 'Modifier'; } else { echo 'Ajouter';}?>"/> ou <a href="index.php">retour à l'accueil</a><input type="hidden" name="id" value="<?php if(isset($modif)) { echo $modif;}?>"/>
				<input type="hidden" name="token" value="<?php echo $_SESSION['token_modif'];?>"/>
			</p>
		</form>
	</body>
</html>
<?php
	}
?>
