<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>projet</title>
</head>
<body>
     <form action="resultat.php" method="POST"> <center><caption><strong>Veuillez remplir les informations pour avoir la moyenne de votre enfant</strong></caption></center>
    <center> 
        <table border="1">
            <tr><td><strong>Nom</strong></td><td><input type="text" name="nom" required></td></tr>
            <tr><td><strong>Prénom</strong></td><td><input type="text" name="prenom" required></td></tr>
            <tr><td><strong>Classe</strong></td>
                <td>
                    <select name="classe" required>
                        <option value="1ere_tia">1ere_tia</option>
                        <option value="term_ti">term_ti</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><input type="submit" value="Envoyer"></td>
                <td><center><input type="reset" value="Annuler"></center></td>
            </tr>
        </table>
    </center>
</form>
</body>
</html>