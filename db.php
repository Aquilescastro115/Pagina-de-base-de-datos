<?php
// db.php
$serverName = "DIEGO\SQLEXPRESS02"; // Tu Nombre de Servidor (De la imagen: DIEGO\SQLEXPRESS02)
$database = "CERTAMEN4_BASE"; // Nombre de tu BD (De la imagen: CERTAMEN4_BASE)
$uid = "sa"; // Tu usuario de SQL Server (De la imagen: sa)
$pwd = "1234"; // **IMPORTANTE: CAMBIA ESTO por tu contraseña**

try {
    // La conexión se realiza con el driver sqlsrv y PDO
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $uid, $pwd);
    // Configurar el modo de error para que PDO lance excepciones
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Para que los resultados numéricos se devuelvan como tipos nativos de PHP
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>