 <?php
 $connection=mysqli_connect("localhost","root","");
 if($connection==true){
    echo"connection reuissir";
 }else{
    echo" echec de connection";
 }
 ?>