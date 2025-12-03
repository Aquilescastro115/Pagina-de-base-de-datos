<?php
session_start();
if (!isset($_SESSION['loggedin'])) { header('Location: index.php'); exit; }
include 'db.php';

$mensaje = "";
$item_editar = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_item = (int)$_POST['num_item'];
    $nombre = $_POST['nombre'];
    $costo = (int)$_POST['costo'];
    $stock = (int)$_POST['stock'];
    $accion = $_POST['action'];

      try {
          if ($accion === 'actualizar') {
              $stmt = $conn->prepare("{CALL sp_ActualizarCatalogo(?, ?, ?, ?)}");
              $stmt->bindParam(1, $num_item);
              $stmt->bindParam(2, $nombre);
              $stmt->bindParam(3, $costo);
              $stmt->bindParam(4, $stock);
              $stmt->execute();
              $mensaje = "Producto actualizado.";
          } else {
              $stmt = $conn->prepare("{CALL SP_insertCatalogo(?, ?, ?, ?)}");
              $stmt->bindParam(1, $num_item);
              $stmt->bindParam(2, $nombre);
              $stmt->bindParam(3, $costo);
              $stmt->bindParam(4, $stock);
              $stmt->execute();
              $mensaje = "Producto creado.";
          }
      } catch (PDOException $e) { $mensaje = "Error: " . $e->getMessage(); }
  }

// --- LÓGICA GET ---
if (isset($_GET['delete_id'])) {
    try {
        $id = (int)$_GET['delete_id'];
        $stmt = $conn->prepare("{CALL sp_EliminarCatalogo(?)}");
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $mensaje = "Producto eliminado.";
    } catch (Exception $e) { $mensaje = "Error al eliminar."; }
}

if (isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM Catalogo WHERE num_item = ?");
    $stmt->execute([$id]);
    $item_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- LEER ---
$productos = [];
try {
    $stmt = $conn->prepare("{CALL sp_LeerCatalogo}");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo</title>
<link rel="stylesheet" href="catalogo.css">
</head>
<body>
<div class="container">
    <div class="header-row">
        <div class="header-left"><h2>Gestión de Catálogo</h2></div>
        <div class="header-right">
    <a href="dashboard.php" class="btn">
       ⬅ Volver al Menú
    </a>
</div>
    </div>
    
    <?php if ($mensaje): ?><div class="alert"><?php echo $mensaje; ?></div><?php endif; ?>

    <div class="card">
        <h3><?php echo $item_editar ? 'Editar Producto' : 'Nuevo Producto'; ?></h3>
        <form method="post">
            <input type="hidden" name="action" value="<?php echo $item_editar ? 'actualizar' : 'insertar'; ?>">
            
            <div class="fila-doble">
                <div class="form-group">
                    <label>ID (Num Item)</label>
                    <input type="number" name="num_item" class="form-control" 
                           value="<?php echo $item_editar ? $item_editar['num_item'] : ''; ?>" 
                           <?php echo $item_editar ? 'readonly style="background:#eee;"' : 'required'; ?>>
                </div>
                <div class="form-group">
                    <label>Nombre del Producto</label>
                    <input type="text" name="nombre" class="form-control" 
                           value="<?php echo $item_editar ? $item_editar['nombre'] : ''; ?>" required>
                </div>
            </div>

            <div class="fila-doble">
                <div class="form-group">
                    <label>Costo</label>
                    <input type="number" name="costo" class="form-control" 
                           value="<?php echo $item_editar ? $item_editar['costo'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control" 
                           value="<?php echo $item_editar ? $item_editar['stock'] : ''; ?>" required>
                </div>
            </div>

            <button class="btn btn-success" type="submit" style="margin-top:20px;">
                <?php echo $item_editar ? 'Guardar Cambios' : 'Crear Producto'; ?>
            </button>
            <?php if($item_editar): ?>
                <a href="crud_catalogo.php" class="btn btn-ghost" style="margin-top:20px;">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h3>Inventario Actual</h3>
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Costo</th><th>Stock</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach($productos as $p): ?>
                <tr>
                    <td><?php echo $p['num_item']; ?></td>
                    <td><?php echo $p['nombre']; ?></td>
                    <td>$<?php echo number_format($p['costo'], 0, ',', '.'); ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td>
                        <a href="crud_catalogo.php?edit_id=<?php echo $p['num_item']; ?>" 
                        class="btn btn-warning">Modificar</a>
                        
                        <a href="crud_catalogo.php?delete_id=<?php echo $p['num_item']; ?>" 
                           class="btn btn-ghost" onclick="return confirm('¿Borrar?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>