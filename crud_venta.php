<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
include 'db.php';

$mensaje = "";
$venta_editar = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $num_item = (int)($_POST['num_item'] ?? 0);
    $id_cliente = (int)($_POST['id_cliente'] ?? 0);
    $accion = $_POST['action'] ?? '';
    
    try {
        if ($accion === 'actualizar') {

            $id_venta = (int)$_POST['id_venta_editar'];
            
            $stmt = $conn->prepare("{CALL sp_ActualizarVenta(?, ?, ?, ?)}");
            $stmt->bindParam(1, $id_venta);
            $stmt->bindParam(2, $fecha);
            $stmt->bindParam(3, $num_item);
            $stmt->bindParam(4, $id_cliente);
            $stmt->execute();
            
            $mensaje = "Venta actualizada correctamente.";
            
        } elseif ($accion === 'insertar') {
            $stmt = $conn->prepare("{CALL sp_InsertarVenta(?, ?, ?)}");
            $stmt->bindParam(1, $fecha);
            $stmt->bindParam(2, $num_item);
            $stmt->bindParam(3, $id_cliente);
            $stmt->execute();
            
            $mensaje = "Venta registrada exitosamente.";
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("{CALL Sp_EliminarVenta(?)}");
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $mensaje = "Venta eliminada.";
    } catch (Exception $e) { $mensaje = "Error al eliminar."; }
}

if (isset($_GET['edit_id'])) {
    $id_edit = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM Venta WHERE id_venta = ?");
    $stmt->execute([$id_edit]);
    $venta_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerOpciones($conn, $tabla, $idCol, $nombreCol){
    $data = [];
    try{
        $stmt = $conn->query("SELECT $idCol, $nombreCol FROM $tabla ORDER BY $nombreCol");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
    return $data;
}
$clientes = obtenerOpciones($conn, 'Cliente', 'id_cliente', 'nombre');
$catalogos = obtenerOpciones($conn, 'Catalogo', 'num_item', 'nombre');

$ventas = [];
try {
    $stmt = $conn->prepare("{CALL Sp_LeerVenta}");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ventas</title>
<link rel="stylesheet" href="catalogo.css?v=2">
</head>
<body>
  <div class="container">
    
    <div class="header-row">
      <div class="header-left"><h2>Gestión de Ventas</h2></div>
      <div class="header-right"><a href="dashboard.php" class="btn">⬅ Volver al Menú</a></div>
    </div>

    <?php if (!empty($mensaje)): ?>
      <div class="alert"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <div class="card">
      <h3><?php echo $venta_editar ? 'Modificar Venta #' . $venta_editar['id_venta'] : 'Registrar nueva venta'; ?></h3>
      
      <form method="post" action="crud_venta.php">
        
        <input type="hidden" name="action" value="<?php echo $venta_editar ? 'actualizar' : 'insertar'; ?>">
        
        <?php if($venta_editar): ?>
            <input type="hidden" name="id_venta_editar" value="<?php echo $venta_editar['id_venta']; ?>">
        <?php endif; ?>

        <div class="form-group">
          <label>Fecha</label>
          <input type="date" name="fecha" class="form-control" 
                 value="<?php echo $venta_editar ? $venta_editar['fecha'] : date('Y-m-d'); ?>" required>
        </div>

        <div class="fila-doble"> <div class="form-group">
              <label>Producto</label>
              <select name="num_item" class="form-control" required>
                <?php foreach($catalogos as $item): ?>
                  <option value="<?php echo $item['num_item']; ?>" 
                    <?php if($venta_editar && $venta_editar['num_item'] == $item['num_item']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($item['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Cliente</label>
              <select name="id_cliente" class="form-control" required>
                <?php foreach($clientes as $cliente): ?>
                  <option value="<?php echo $cliente['id_cliente']; ?>"
                    <?php if($venta_editar && $venta_editar['id_cliente'] == $cliente['id_cliente']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
        </div>

        <button class="btn btn-success" type="submit" style="margin-top: 20px;">
            <?php echo $venta_editar ? 'Guardar Cambios' : 'Registrar Venta'; ?>
        </button>
        
        <?php if($venta_editar): ?>
            <a href="crud_venta.php" class="btn btn-ghost" style="margin-top: 20px; display:inline-block;">Cancelar Edición</a>
        <?php endif; ?>

      </form>
    </div>

    <div class="card">
      <h3>Listado de ventas</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Producto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($ventas as $v): ?>
            <tr>
                <td><?php echo $v['id_venta']; ?></td>
                <td><?php echo isset($v['NombreCliente']) ? $v['NombreCliente'] : $v['nombre']; ?></td>
                <td><?php echo $v['fecha']; ?></td>
                <td><?php echo isset($v['NombreProducto']) ? $v['NombreProducto'] : $v['nombre']; ?></td>
                <td>
                    <a class="btn" style="background:#f39c12; color:white; padding:5px 10px; font-size:0.8rem;" 
                       href="crud_venta.php?edit_id=<?php echo $v['id_venta']; ?>">Modificar</a>
                    
                    <a class="btn btn-ghost" 
                       href="crud_venta.php?delete_id=<?php echo $v['id_venta']; ?>" 
                       onclick="return confirm('¿Eliminar?');">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
