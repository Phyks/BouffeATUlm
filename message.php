<?php
	if(empty($_GET['id']))
	{
		header('location: index.php');
	}
	else
	{
		$id = (int) $_GET['id'];
				
		$message = array(
			1=>array("message"=>"Dépense supprimée avec succès.", "url"=>"index.php"), 
			2=>array("message"=>"Dépense modifiée avec succès.", "url"=>"index.php"), 
			3=>array("message"=>"Dépense ajoutée avec succès.", "url"=>"index.php"), 
			4=>array("message"=>"Copain supprimé avec succès.", "url"=>"copains.php"), 
			5=>array("message"=>"Copain modifié avec succès.", "url"=>"copains.php"), 
			6=>array("message"=>"Copain ajouté avec succès.", "url"=>"copains.php"), 
			7=>array("message"=>"Page à accès restreint, se connecter en administrateur.", "url"=>"index.php"),
			8=>array("message"=>"Erreur à la connexion. Vérifiez vos identifiants.", "url"=>"connexion.php"), 
			9=>array("message"=>"Vous n'avez pas le droit d'exécuter cette action.", "url"=>"index.php"), 
			10=>array("message"=>"Paiement validé.", "url"=>"index.php"), 
			11=>array("message"=>"Paiements validés.", "url"=>"index.php"), 
			12=>array("message"=>"Erreur lors de la modification du mot de passe.", "url"=>"modif_password.php"), 
			13=>array("message"=>"Mot de passe modifié avec succès.", "url"=>"index.php"),
			14=>array("message"=>"Remboursement ajouté avec succès.", "url"=>"rbmt_admin.php"),
			15=>array("message"=>"Remboursement supprimé avec succès.", "url"=>"rbmt_admin.php"),
			16=>array("message"=>"Erreur lors de la suppression du remboursement.", "url"=>"rbmt_admin.php")
		);
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Bouffe@Ulm</title>
		<link rel="stylesheet" media="screen" type="text/css" href="misc/design.css" />
		<link rel="icon" href="favicon.ico" />
		<?php
			echo '<meta http-equiv="refresh" content="1;URL='.$message[$id]['url'].'">';
		?>
	</head>
	<body>
		<h1>Bouffe@Ulm</h1>
		<?php
			echo '<p>'.$message[$id]['message'].' Redirection automatique dans 1 seconde. <a href="'.$message[$id]['url'].'">Ne pas attendre</a></p>';
		?>
	</body>
</html>
<?php
	}
?>
