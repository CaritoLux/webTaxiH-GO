<?php
// =====================================================================
//  Conexión a la base de datos (versión HOSTING · bandline.com.mx)
//
//  Los datos de acceso (host, base, usuario y contraseña) NO se escriben
//  aquí: se toman del archivo de la API, que ya está en el servidor en
//  public_html/taxihgo/taxihgo_api/config/conexion.php
//  Así la contraseña vive en un solo lugar y no se sube a GitHub.
//
//  public_html/taxihgo/
//  ├── taxihgo_api/config/conexion.php   <- de aquí salen $host $db $user $pass
//  └── webTaxiH-GO/config/conexion.php   <- este archivo
// =====================================================================
require_once __DIR__ . '/../../taxihgo_api/config/conexion.php';

// La API conecta con PDO; la web usa MySQLi (como en clase) con los mismos datos
$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

date_default_timezone_set('America/Mexico_City');
mysqli_query($conexion, "SET time_zone = '-06:00'");
?>
