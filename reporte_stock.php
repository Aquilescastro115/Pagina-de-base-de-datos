<?php
// reporte_stock.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); exit;
}
include 'db.php';
$reporte = [];
$mensaje = '';
$umbral = 10;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['umbral'])) {
    $umbral = (int)$_POST['umbral'];
}
try {
    $stmt = $conn->prepare("{CALL sp_ReporteBajoStock(?)}");
    $stmt->bindParam(1, $umbral, PDO::PARAM_INT);
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
<title>Reporte Bajo Stock</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>
  <div class="navbar"><div class="brand"><h1>Reporte Stock</h1></div><div class="nav-links"><a href="dashboard.php">Inicio</a><a href="logout.php" style="background:#b80000;padding:8px;border-radius:8px;">Salir</a></div></div>

  <div class="container">
    <div class="header-row"><div class="header-left"><h2>Productos con Bajo Stock</h2></div></div>

    <?php if(!empty($mensaje)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <form method="post" style="display:flex;gap:12px;align-items:center;margin-bottom:12px;">
      <label for="umbral">Umbral (<=):</label>
      <input id="umbral" name="umbral" class="form-control" type="number" style="width:120px;" value="<?php echo $umbral; ?>" required>
      <button class="btn btn-primary" type="submit">Aplicar</button>
    </form>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>Producto</th><th>Stock Actual</th><th>Costo</th></tr></thead>
          <tbody>
            <?php if(empty($reporte)): ?>
              <tr><td colspan="3" style="text-align:center">No se encontraron productos bajo el umbral de <?php echo $umbral; ?>.</td></tr>
            <?php else: foreach($reporte as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['nombre']); ?></td>
                <td style="color:var(--danger);font-weight:700;"><?php echo htmlspecialchars($r['stock']); ?></td>
                <td>$<?php echo number_format((float)$r['costo'],0,',','.'); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
