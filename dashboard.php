<?php
// dashboard.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
$nombre_usuario = $_SESSION['usuario'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Dashboard | CERTAMEN4_BASE</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>


  <div class="container">
    <div class="header-row">
      <div class="header-left">
        <h2>¡Hola de nuevo, <?php echo htmlspecialchars($nombre_usuario); ?>!</h2>
        <p class="muted">Bienvenido al panel - selecciona una opción del menú.</p>
      </div>
      <div class="header-right">Hoy: <?php echo date('d \d\e F, Y'); ?></div>
    </div>

    <div class="menu-inline">
      <a class="active" href="dashboard.php">Inicio</a>
      <a href="crud_venta.php">Gestión de Ventas</a>
      <a href="crud_catalogo.php">Catálogo</a>    
    </div>

    <div class="card">
      <p>Holaaas saaaaaaaaaassasasasas. esta parte fafa si quieres le pones un texto para que se vea bonito</p>
    </div>

    <div class="footer">
      Universidad Adventista de Chile - Proyecto de Ventas
    </div>
  </div>

</body>
</html>
