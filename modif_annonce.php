<?php
        require('include.php');
        init(true, true);

        if(isset($_POST['annonce']))
        {
                if(!empty($_POST['annonce']))
                        file_put_contents("annonce", htmlspecialchars($_POST['annonce']));
                else if(is_file("annonce"))
                        unlink("annonce");

                header('location: index.php');
                exit();
        }
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
                <form method="post" action="modif_annonce.php">
                        <p>
                                <label for="annonce">Annonce : </label>
                        </p>
                        <textarea style="width: 60%; min-height: 100px;" id="annonce" name="annonce"><$
                        <p><input type="submit" value="Envoyer"/></p>
                </form>
        </body>
</html>

