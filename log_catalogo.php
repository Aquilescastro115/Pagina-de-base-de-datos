<?php
// log_catalogo.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); exit;
}
include 'db.php';
$log = [];
$mensaje = '';
try {
    $stmt = $conn->prepare("{CALL sp_LeerCatalogoLog}");
    $stmt->execute();
    $log = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar el log: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Log de Catálogo</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>
  <div class="navbar"><div class="brand"><h1>Log Catalogo</h1></div><div class="nav-links"><a href="dashboard.php">Inicio</a><a href="logout.php" style="background:#b80000;padding:8px;border-radius:8px;">Salir</a></div></div>

  <div class="container">
    <div class="header-row"><div class="header-left"><h2>Registro del Trigger (Log)</h2></div></div>

    <?php if(!empty($mensaje)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead><tr><th>ID Log</th><th>Fecha y Hora</th><th>Detalle</th></tr></thead>
          <tbody>
            <?php if(empty($log)): ?>
              <tr><td colspan="3" style="text-align:center">No hay registros en la tabla de log.</td></tr>
            <?php else: foreach($log as $l): ?>
              <tr>
                <td><?php echo htmlspecialchars($l['id_Log']); ?></td>
                <td><?php echo htmlspecialchars($l['Fecha']); ?></td>
                <td><?php echo htmlspecialchars($l['Detalle']); ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
