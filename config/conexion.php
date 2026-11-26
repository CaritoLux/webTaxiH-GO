<?php

$host = "localhost";
$db = "u168691445_bd_taxihgo";
$user = "u168691445_taxihgo";
$pass = "b1nA3Tjp?S4@";
//conexion para  web
$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>
