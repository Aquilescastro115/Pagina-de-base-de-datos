<?php
// crud_venta.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}
include 'db.php';

$mensaje = "";

// Obtener opciones (clientes y catálogo)
function obtenerOpciones($conn, $tabla, $idCol, $nombreCol){
    $data = [];
    try{
        $stmt = $conn->query("SELECT $idCol, $nombreCol FROM $tabla ORDER BY $nombreCol");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* silencioso */ }
    return $data;
}
$clientes = obtenerOpciones($conn, 'Cliente', 'id_cliente', 'nombre');
$catalogos = obtenerOpciones($conn, 'Catalogo', 'num_item', 'nombre');

// INSERTAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'insertar') {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $num_item = (int)($_POST['num_item'] ?? 0);
    $id_cliente = (int)($_POST['id_cliente'] ?? 0);

    try {
        $stmt = $conn->prepare("{CALL sp_InsertarVenta(?, ?, ?)}");
        $stmt->bindParam(1, $fecha);
        $stmt->bindParam(2, $num_item, PDO::PARAM_INT);
        $stmt->bindParam(3, $id_cliente, PDO::PARAM_INT);
        $stmt->execute();
        $mensaje = "Venta registrada exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al registrar la venta: " . $e->getMessage();
    }
}

// ELIMINAR
if (isset($_GET['delete_id'])) {
    $id_venta = (int)$_GET['delete_id'];
    try {
        $stmt = $conn->prepare("{CALL Sp_EliminarVenta(?)}");
        $stmt->bindParam(1, $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        $mensaje = "Venta con ID $id_venta eliminada exitosamente.";
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar la venta: " . $e->getMessage();
    }
    header('Location: crud_venta.php');
    exit;
}

// LEER
$ventas = [];
try {
    $stmt = $conn->prepare("{CALL Sp_LeerVenta}");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar las ventas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ventas | CERTAMEN4_BASE</title>
<link rel="stylesheet" href="venta.css?v=2">
<script defer src="assets/script.js"></script>
</head>
<body>



  <div class="container">
    <div class="header-row"style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <div class="header-left">
        <h2 style="margin: 0; border-left: 5px solid #4a90e2; padding-left: 15px;">Gestión de Catálogo</h2>
      </div>
      <div class="header-right">
        <a href="dashboard.php" class="btn" style="background-color: #555; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">
          ⬅ Volver al Menú
        </a>
      </div>
    </div>

    <?php if (!empty($mensaje)): ?>
      <div class="alert <?php echo (strpos($mensaje,'Error')!==false)?'alert-danger':'alert-success'; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3>Registrar nueva venta</h3>
<form method="post" action="">
    <input type="hidden" name="action" value="insertar">
    
    <div class="form-group">
        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <div class="fila-doble">
        
        <div class="form-group">
            <label for="num_item">Producto</label>
            <select id="num_item" name="num_item" class="form-control" required>
                <?php foreach($catalogos as $item): ?>
                <option value="<?php echo $item['num_item']; ?>"><?php echo htmlspecialchars($item['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="id_cliente">Cliente</label>
            <select id="id_cliente" name="id_cliente" class="form-control" required>
                <?php foreach($clientes as $cliente): ?>
                <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>
    <button class="btn btn-success" type="submit" style="margin-top: 20px;">Registrar Venta</button>
</form>
    </div>

    <div class="card" style="margin-top:16px;">
      <h3>Listado de ventas</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID Venta</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Producto</th>
              <th>Costo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ventas)): ?>
              <tr><td colspan="5" style="text-align:center">No hay ventas registradas.</td></tr>
            <?php else: ?>
<?php foreach($ventas as $v): ?>
<tr>
    <td><?php echo htmlspecialchars($v['id_venta']); ?></td>
    <td><?php echo htmlspecialchars($v['NombreCliente']); ?></td>
    <td><?php echo htmlspecialchars($v['fecha']); ?></td>
    <td><?php echo htmlspecialchars($v['NombreProducto']); ?></td>
    <td>$<?php echo number_format((float)$v['costo'],0,',','.'); ?></td>
    
    <td>
        <a class="btn btn-ghost" href="crud_venta.php?delete_id=<?php echo $v['id_venta']; ?>" data-confirm="¿Eliminar venta <?php echo $v['id_venta']; ?>?">Eliminar</a>
    </td>
</tr>
<?php endforeach; ?>

            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
