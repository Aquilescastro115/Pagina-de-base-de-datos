<?php
// crud_catalogo.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
include 'db.php';

$mensaje = "";

// INSERTAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'insertar') {
    $num_item = (int)($_POST['num_item'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $costo = (int)($_POST['costo'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    try {
        $stmt = $conn->prepare("{CALL SP_insertCatalogo(?, ?, ?, ?)}");
        $stmt->bindParam(1, $num_item, PDO::PARAM_INT);
        $stmt->bindParam(2, $nombre);
        $stmt->bindParam(3, $costo, PDO::PARAM_INT);
        $stmt->bindParam(4, $stock, PDO::PARAM_INT);
        $stmt->execute();
        $mensaje = "Producto '".htmlspecialchars($nombre)."' registrado exitosamente. (Trigger Log activado)";
    } catch (PDOException $e) {
        $mensaje = "Error al registrar el producto: " . $e->getMessage();
    }
}

// ELIMINAR
if (isset($_GET['delete_id'])) {
    $num_item = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("{CALL sp_EliminarCatalogo(?)}");
        $stmt->bindParam(1, $num_item, PDO::PARAM_INT);
        $stmt->execute();
        $mensaje = "Producto con ID $num_item eliminado exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el producto. (Puede tener ventas asociadas): " . $e->getMessage();
    }
    header('Location: crud_catalogo.php');
    exit;
}

// LEER
$catalogos = [];
try {
    $stmt = $conn->prepare("{CALL sp_LeerCatalogo}");
    $stmt->execute();
    $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar el catálogo: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo | CERTAMEN4_BASE</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>

  <div class="container">
    <div class="header-row">
      <div class="header-left"><h2>Gestión de Catálogo</h2></div>
    </div>

    <?php if (!empty($mensaje)): ?>
      <div class="alert <?php echo (strpos($mensaje,'Error')!==false)?'alert-danger':'alert-success'; ?>">
        <?php echo $mensaje; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3>Agregar nuevo producto</h3>
      <form method="post" action="">
        <input type="hidden" name="action" value="insertar">
        <div class="form-group">
          <label for="num_item">ID Producto</label>
          <input id="num_item" name="num_item" type="number" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input id="nombre" name="nombre" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="costo">Costo</label>
          <input id="costo" name="costo" type="number" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="stock">Stock</label>
          <input id="stock" name="stock" type="number" class="form-control" required>
        </div>
        <button class="btn btn-success" type="submit">Guardar Producto</button>
      </form>
    </div>

    <div class="card" style="margin-top:14px;">
      <h3>Listado de productos</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th><th>Nombre</th><th>Costo</th><th>Stock</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($catalogos)): ?>
              <tr><td colspan="5" style="text-align:center">No hay productos registrados.</td></tr>
            <?php else: foreach($catalogos as $it): ?>
              <tr>
                <td><?php echo htmlspecialchars($it['num_item']); ?></td>
                <td><?php echo htmlspecialchars($it['nombre']); ?></td>
                <td>$<?php echo number_format((float)$it['costo'],0,',','.'); ?></td>
                <td><?php echo htmlspecialchars($it['stock']); ?></td>
                <td>
                  <a class="btn btn-ghost" href="crud_catalogo.php?delete_id=<?php echo $it['num_item']; ?>" data-confirm="¿Eliminar producto <?php echo htmlspecialchars($it['nombre']); ?>?">Eliminar</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
