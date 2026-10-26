 import random
import string

print("--- GÉNÉRATEUR DE MOT DE PASSE SÉCURISÉ ---")

# 1. On définit les caractères possibles (lettres, chiffres, symboles)
caracteres = string.ascii_letters + string.digits + string.punctuation

# 2. On demande la longueur voulue à l'utilisateur
longueur = int(input("Combien de caractères voulez-vous ? "))

# 3. On choisit des caractères au hasard et on les assemble
mot_de_passe = "".join(random.choice(caracteres) for i in range(longueur))

# 4. On affiche le résultat
print(f"\nVotre nouveau mot de passe est : {mot_de_passe}")
print("-------------------------------------------")