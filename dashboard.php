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
<title>Dashboard | Sistema Ventas</title>
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="container">
    
    <div class="header-row">
      <div class="header-left">
        <h2>Hola, <?php echo htmlspecialchars($nombre_usuario); ?> 👋</h2>
        <p class="muted">Panel de control general</p>
      </div>
      <div class="header-right">
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Cerrar Sesión</a>
      </div>
    </div>

    <div class="date-badge">
        <i class="fa-regular fa-calendar"></i> Hoy es <?php echo date('d \d\e F, Y'); ?>
    </div>

    <div class="dashboard-grid">
      
      <a href="crud_venta.php" class="dash-card card-blue">
        <div class="icon-box"><i class="fa-solid fa-cart-shopping"></i></div>
        <h3>Gestión de Ventas</h3>
        <p>Registrar y consultar ventas</p>
      </a>

      <a href="crud_catalogo.php" class="dash-card card-green">
        <div class="icon-box"><i class="fa-solid fa-boxes-stacked"></i></div>
        <h3>Catálogo</h3>
        <p>Administrar productos</p>
      </a>
    </div>

      <a href="reporte_iva.php" class="dash-card card-green">
        <div class="icon-box"><i class="fa-solid fa-boxes-stacked"></i></div>
        <h3>IVA</h3>
        <p>Administrar productos</p>
      </a>
    </div>

      <a href="reporte_stock.php" class="dash-card card-green">
        <div class="icon-box"><i class="fa-solid fa-boxes-stacked"></i></div>
        <h3>Reporte</h3>
        <p>Administrar productos</p>
      </a>
    </div>

    <div class="footer">
      Universidad Adventista de Chile - Proyecto de Ventas -
      Creado por Rafael Aruti y Diego Castro &copy; <?php echo date('Y'); ?>
    </div>
  </div>

</body>
</html>