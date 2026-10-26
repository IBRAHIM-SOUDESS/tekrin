<?php  
 function bonjour($nom){
    echo "bonjour,$nom !";

 }
bonjour("jean") ; 
function addition($a , $b){
    return $a + $b;
}
$resultat=addition(3,7);
echo $resultat;
$tableau=array('pomme','poire');
echo $tableau[1];
$tableau=['pomme','voiture','biscuit'];
echo $tableau[0];
$tableau = [
    'nom'=> 'jean',
    'age'=>34,
    'ville'=>'cuba',
];
echo $tableau['age'];
?>