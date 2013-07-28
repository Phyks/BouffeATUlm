<?php
	require('include.php');
	init(true, true);
	
	if(isset($_GET['del']) && !empty($_GET['token']) && $_GET['token'] == $_SESSION['token_buddy'] && $_SESSION['token_buddy_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0) //If we want to delete a buddy
	{
		$id = (int) $_GET['del'];
		$bdd->query('DELETE FROM copains WHERE id='.$id);
		
		header('location: message.php?id=4');
		exit();
	}
	
	if(isset($_POST['id']) && isset($_POST['nom']) && !empty($_POST['token']) && $_POST['token'] == $_SESSION['token_buddy'] && $_SESSION['token_buddy_time'] > time() - (15*60) AND strpos($_SERVER['HTTP_REFERER'], 'http://'.$CONFIG['base_url'].'/index.php') == 0) //If we want to add or delete a buddy
	{
		if(!empty($_POST['id']))
		{
			$req = $bdd->prepare('UPDATE copains SET nom=:nom, admin=:admin WHERE id='.(int) $_POST['id']);
			if(!empty($_POST['password']))
			{
				$req2 = $bdd->prepare('UPDATE copains SET password=:password WHERE id='.(int) $_POST['id']);
				$req2->bindValue(':password', sha1($_POST['password'] . $CONFIG['salt']));
				$req2->execute();
			}
			$message = 5;
		}
		else
		{
			$req = $bdd->prepare('INSERT INTO copains (id, nom, password, admin) VALUES ("", :nom, :password, :admin)');
			$req->bindValue(':password', sha1($_POST['password'] . $CONFIG['salt']));
			$message = 6;
		}
		
		$req->bindValue(':nom', $_POST['nom']);
		$req->bindValue(':admin', intval($_POST['admin']));
		$req->execute();

		header('location: message.php?id='.$message);
	}
	
	$_SESSION['token_buddy'] = sha1(uniqid(rand(), true)); //We generate a token and store it in a session variable
	$_SESSION['token_buddy_time'] = time(); //We also store the time at which the token has been generated
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
		<?php
			if(!isset($_GET['modif']))
			{
		?>
				<h2>Liste des copains</h2>
				<p><a href="index.php">Retour à l'accueil</a></p>
				<table><!-- Form -->
					<tr>
						<th>N° <a class='text-deco-none' href='?tri=id&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=id&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
						<th>Nom <a class='text-deco-none' href='?tri=nom&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=nom&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
						<th>Admin ? <a class='text-deco-none' href='?tri=admin&amp;sens=asc' title='/\'><img src="misc/asc.png" alt="/\"/></a> <a class='text-deco-none' href='?tri=admin&amp;sens=desc' title='\/'><img src="misc/desc.png" alt="/\"/></a></th>
						<th>Modifier</th>
						<th>Supprimer</th>
					</tr>
					<?php
						if(isset($_GET['tri']) && isset($_GET['sens']) && ($_GET['tri'] == 'id' || $_GET['tri'] == 'nom') && ($_GET['sens'] == 'asc' || $_GET['sens'] == 'desc'))
						{
							$req = $bdd->query('SELECT id, nom, admin FROM copains ORDER BY '.$_GET['tri'].' '.$_GET['sens']);
						}
						else
						{
							$req = $bdd->query('SELECT id, nom, admin FROM copains ORDER BY nom ASC');
						}
						
						while($donnees = $req->fetch())
						{
							$id = (int) $donnees['id'];
							
							if($donnees['admin'] == 1)
							{
								$admin = 'Oui';
							}
							else
							{
								$admin = 'Non';
							}
							
							echo '<tr>
								<td>'.$id.'</td>
								<td>'.htmlspecialchars($donnees["nom"]).'</td>
								<td>'.$admin.'</td>
								<td><a href="?modif='.$id.'">Modifier</a></td>
								<td><a href="?del='.$id.'&amp;token='.$_SESSION['token_buddy'].'">Supprimer</a></td>
								</tr>';
						}
						$req->closeCursor();
					?>
				</table>
				<h2>Ajouter un copain</h2>
		<?php
			}
			else
			{
				$modif = (int) $_GET['modif'];
				$req = $bdd->query('SELECT nom, admin FROM copains WHERE id='.$modif);
				$donnees = $req->fetch();
				$req->closeCursor();
		?>
				<h2>Modifier un copain</h2>
		<?php
			}
		?>
		<form method="post" action="copains.php">
			<p><label for="nom">Nom : </label><input type="text" name="nom" id="nom" size="50" value="<?php if(isset($modif)) echo htmlspecialchars($donnees['nom']);?>"/></p>
			<p><label for="password">Mot de passe (laisser vide pour ne pas modifier) : </label><input type="password" name="password" id="password" size="50"/></p>
			<p>
				<label for="admin">Admin ? </label>
				<select name="admin" id = "admin">
					<option value="0" <?php if($donnees['admin'] != 1) echo 'selected="selected"';?>>Non</option>
					<option value="1" <?php if($donnees['admin'] == 1) echo 'selected="selected"';?>>Oui</option>
				</select>
			</p>
			<p>
				<input type="submit" value="<?php if(isset($modif)) { echo 'Modifier'; } else { echo 'Ajouter';}?>"/><input type="hidden" name="id" value="<?php if(isset($modif)) { echo $modif;}?>"/>
				<input type="hidden" name="token" value="<?php echo $_SESSION['token_buddy'];?>"/>
			</p>
		</form>
	</body>
</html>

