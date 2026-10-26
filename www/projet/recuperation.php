 <?php 
$nom = $_POST["nom"];
$prenom = $_POST["prenom"];
$email = $_POST["email"];
if ($nom != "" && $prenom != "" && $email != "") {
    echo "Bonjour monsieur " . $nom . " " . $prenom . ", votre email est : " . $email;
} else {
    echo "Veuillez remplir tout les champs.";
}
?>