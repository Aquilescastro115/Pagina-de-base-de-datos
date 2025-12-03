<?php
// reporte_iva.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); exit;
}
include 'db.php';
$reporte = [];
$mensaje = '';
try {
    $stmt = $conn->prepare("{CALL sp_ReportePreciosConIVA}");
    $stmt->execute();
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al generar el reporte: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte IVA</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>
  <div class="navbar"><div class="brand"><h1>Reporte IVA</h1></div><div class="nav-links"><a href="dashboard.php">Inicio</a><a href="logout.php" style="background:#b80000;padding:8px;border-radius:8px;">Salir</a></div></div>

  <div class="container">
    <div class="header-row"><div class="header-left"><h2>Precios con IVA (19%)</h2></div></div>

    <?php if(!empty($mensaje)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>Producto</th><th>Costo Sin IVA</th><th>Precio Final (IVA)</th></tr></thead>
          <tbody>
            <?php if(empty($reporte)): ?>
              <tr><td colspan="3" style="text-align:center">No hay datos.</td></tr>
            <?php else: foreach($reporte as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                <td>$<?php echo number_format((float)$r['costo'],0,',','.'); ?></td>
                <td>$<?php echo number_format((float)$r['PrecioFinalConIVA'],0,',','.'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
