<?php
require_once 'db.php';

echo "Nettoyage de la base de données...<br>";
mysqli_query($conn, "DELETE FROM logs_audit");
mysqli_query($conn, "DELETE FROM demandes");
mysqli_query($conn, "ALTER TABLE demandes AUTO_INCREMENT = 1");
mysqli_query($conn, "ALTER TABLE logs_audit AUTO_INCREMENT = 1");

$services = ['Comptabilité', 'Ressources Humaines', 'Commercial', 'Logistique', 'Direction'];
$categories = ['Matériel', 'Logiciel métier', 'Réseau / Internet', 'Autre'];
$impacts = ['utilisateur', 'département', 'entreprise'];
$urgences_brutes = ['informatif', 'gênant', 'bloquant'];
$noms = ['Jean KAMAL', 'Alice MENDY', 'Oumar TRAORE', 'Fatou DIALLO', 'Ibrahim CISSE', 'Marie TOURE', 'Koffi ADO', 'Amina OUATTARA', 'Bakary SOW', 'Sarah GUEYE'];
$objets = [
    'Panne imprimante réseau', 'Problème accès VPN', 'Écran bleu au démarrage', 
    'Lenteur logicielle SAP', 'Mot de passe oublié', 'Besoin installation Office',
    'Coupure internet fibre', 'Clavier défectueux', 'Accès dossier partagé bloqué',
    'Mise à jour Windows échouée'
];

echo "Génération de 20 tickets...<br>";

for ($i = 1; $i <= 20; $i++) {
    $nom = $noms[array_rand($noms)];
    $service = $services[array_rand($services)];
    $objet = $objets[array_rand($objets)] . " (#$i)";
    $categorie = $categories[array_rand($categories)];
    $impact = $impacts[array_rand($impacts)];
    $urgence_brute = $urgences_brutes[array_rand($urgences_brutes)];
    
    // Mapping priorité
    $map_priorite = [
        'utilisateur' => ['informatif' => 'basse', 'gênant' => 'moyenne', 'bloquant' => 'haute'],
        'département' => ['informatif' => 'moyenne', 'gênant' => 'haute', 'bloquant' => 'critique'],
        'entreprise' => ['informatif' => 'haute', 'gênant' => 'critique', 'bloquant' => 'critique']
    ];
    $priorite = $map_priorite[$impact][$urgence_brute];
    
    // Mapping urgence BDD
    $map_urgence_bdd = ['informatif' => 'basse', 'gênant' => 'moyenne', 'bloquant' => 'haute'];
    $urgence_bdd = $map_urgence_bdd[$urgence_brute];
    
    $message = "Ceci est une demande de test automatique générée pour la démonstration. Le problème concerne $objet pour le service $service. Merci de traiter rapidement.";
    $statut = ($i > 15) ? 'terminé' : 'en cours';
    $date = date('Y-m-d H:i:s', strtotime("-" . rand(1, 10) . " days -" . rand(1, 12) . " hours"));
    $date_cloture = ($statut == 'terminé') ? date('Y-m-d H:i:s', strtotime($date . " + " . rand(2, 24) . " hours")) : null;
    $note = ($statut == 'terminé') ? "Problème résolu par le technicien de garde. Vérification effectuée." : null;

    $sql = "INSERT INTO demandes (nom_employe, service, objet, categorie, urgence, message, impact, priorite_calculee, statut, date_creation, date_cloture, note_resolution) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssssss", $nom, $service, $objet, $categorie, $urgence_bdd, $message, $impact, $priorite, $statut, $date, $date_cloture, $note);
    mysqli_stmt_execute($stmt);
    
    $tid = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO logs_audit (ticket_id, action, details, date_action) VALUES ($tid, 'Création', 'Génération automatique', '$date')");
    if ($statut == 'terminé') {
        mysqli_query($conn, "INSERT INTO logs_audit (ticket_id, action, details, date_action) VALUES ($tid, 'Clôture', 'Clôture automatique', '$date_cloture')");
    }
}

echo "Terminé ! 20 tickets ont été créés.<br>";
echo "<a href='admin.php'>Voir le tableau de bord</a>";
?>