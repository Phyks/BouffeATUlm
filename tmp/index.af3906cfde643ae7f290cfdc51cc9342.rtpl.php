<?php if(!class_exists('raintpl')){exit;}?><?php $tpl = new RainTPL;$tpl_dir_temp = self::$tpl_dir;$tpl->assign( $this->var );$tpl->draw( dirname("header") . ( substr("header",-1,1) != "/" ? "/" : "" ) . basename("header") );?>


<h1><?php echo $title;?></h1>

<?php echo $notice;?>


<div id="menu">
    <ul>
        <li><a href="modif.php">Ajouter une dépense</a></li>
        <li><a href="modif_password.php">Modifier le mot de passe</a></li>
        <li><a href="rbmt.php">Consulter les remboursements</a></li>
    </ul>
    <?php if( $admin ){ ?>

    <ul>
        <li><a href="rbmt_admin.php">Gérer les rembourements</a></li>
        <li><a href="copains.php">Modifier les copains</a></li>
        <li><a href="modif_annonce.php">Modifier l'annonce d'accueil</a></li>
        <li><a href="connexion.php?deco=1">Déconnexion</a></li>
    </ul>
    <?php } ?>

</div>
<div id="quick_summary">
    <h2>Qui doit quoi ?</h2>
    <p>Lire <em>ligne</em> doit <em>case</em>€ à <em>colonne</em>. Les liens permettent de confirmer le paiement des dettes.</p>
    <table>
        <tr>
            <th>Doit\À</th>
        </tr>
    </table>
</div>
<div id="detailed_summary">
    <h2>Dépenses détaillées du mois actuel</h2>

    <table>
        <tr>
            <th>Date</th>
            <th>Payé par</th>
            <th>Participants</th>
            <th>Montant</th>
            <th>Menu</th>
            <th>Modifier</th>
            <th>Supprimer</th>
        </tr>
    </table>
</div>
