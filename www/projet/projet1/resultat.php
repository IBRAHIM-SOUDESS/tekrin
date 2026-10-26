 <?php
// 1. Paramètres de connexion
$host = "localhost";
$user = "root";
$password = ""; // Vide par défaut sur WAMP
$dbname = "ecole_db";

try {
    // Connexion avec activation des erreurs SQL pour le débogage
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion à la base : " . $e->getMessage());
}

// 2. Vérification de la réception des données
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Récupération et nettoyage des données (enlève les espaces vides)
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $classe = isset($_POST['classe']) ? trim($_POST['classe']) : '';

    if (empty($nom) || empty($prenom) || empty($classe)) {
        die("Veuillez remplir tous les champs du formulaire.");
    }

    // 3. Requête SQL
    // Utilisation de LOWER() pour ignorer les problèmes de Majuscules/Minuscules
    $sql = "SELECT * FROM eleves WHERE LOWER(nom) = LOWER(?) AND LOWER(prenom) = LOWER(?) AND classe = ?";
    $query = $db->prepare($sql);
    $query->execute([$nom, $prenom, $classe]);
    
    $eleve = $query->fetch(PDO::FETCH_ASSOC);

    // 4. Affichage des résultats
    echo "<html><head><meta charset='UTF-8'><style>
            body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
            .success { color: #2e7d32; background: #e8f5e9; padding: 15px; border-radius: 5px; border: 1px solid #c8e6c9; }
            .error { color: #c62828; background: #ffebee; padding: 15px; border-radius: 5px; border: 1px solid #ffcdd2; }
            table { margin-top: 10px; border-collapse: collapse; }
            td { padding: 8px; border: 1px solid #ddd; }
          </style></head><body>";

    if ($eleve) {
        echo "<div class='success'>";
        echo "<h2>Fiche de : " . htmlspecialchars($eleve['prenom']) . " " . htmlspecialchars($eleve['nom']) . "</h2>";
        echo "<table>";
        echo "<tr><td><strong>Classe</strong></td><td>" . htmlspecialchars($eleve['classe']) . "</td></tr>";
        echo "<tr><td><strong>Moyenne Trimestrielle</strong></td><td><b style='font-size: 1.2em;'>" . $eleve['moyenne_trimestrielle'] . "</b></td></tr>";
        echo "<tr><td><strong>Moyenne Séquentielle</strong></td><td>" . $eleve['moyenne_sequentielle'] . "</td></tr>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>Élève non trouvé !</strong><br>";
        echo "Le système a cherché dans la table <b>eleves</b> :<br>";
        echo "<ul>
                <li>Nom : $nom</li>
                <li>Prénom : $prenom</li>
                <li>Classe : $classe</li>
              </ul>";
        echo "<i>Vérifiez que ces informations correspondent exactement à votre base de données.</i>";
        echo "</div>";
    }

    echo "<br><a href='index.html'>← Retour au formulaire</a>";
    echo "</body></html>";
}
?>