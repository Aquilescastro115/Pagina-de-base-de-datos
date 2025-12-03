<?php
// reporte_stock.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); exit;
}
include 'db.php';

$mensaje = '';
$umbral = 10;
$q = '';
$sort_by = 'stock'; // default
$order = 'desc';
$per_page = 10;
$page = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['umbral'])) $umbral = (int)$_POST['umbral'];
    if (isset($_POST['q'])) $q = trim($_POST['q']);
    if (isset($_POST['per_page'])) $per_page = max(1, (int)$_POST['per_page']);
    if (isset($_POST['page'])) $page = max(1, (int)$_POST['page']);
    if (isset($_POST['sort_by'])) $sort_by = in_array($_POST['sort_by'], ['stock','costo']) ? $_POST['sort_by'] : 'stock';
    if (isset($_POST['order'])) $order = $_POST['order'] === 'asc' ? 'asc' : 'desc';
} else {
    // Soporta GET para export
    if (isset($_GET['umbral'])) $umbral = (int)$_GET['umbral'];
    if (isset($_GET['q'])) $q = trim($_GET['q']);
    if (isset($_GET['per_page'])) $per_page = max(1, (int)$_GET['per_page']);
    if (isset($_GET['page'])) $page = max(1, (int)$_GET['page']);
    if (isset($_GET['sort_by'])) $sort_by = in_array($_GET['sort_by'], ['stock','costo']) ? $_GET['sort_by'] : 'stock';
    if (isset($_GET['order'])) $order = $_GET['order'] === 'asc' ? 'asc' : 'desc';
}

$reporte = [];
try {
    $stmt = $conn->prepare("{CALL sp_ReporteBajoStock(?)}");
    $stmt->bindParam(1, $umbral, PDO::PARAM_INT);
    $stmt->execute();
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al generar el reporte: " . $e->getMessage();
}

// Filtrar por búsqueda (PHP)
if ($q !== '') {
    $reporte = array_filter($reporte, function($r) use ($q) {
        return stripos($r['nombre'], $q) !== false;
    });
}

// Ordenar
usort($reporte, function($a,$b) use ($sort_by, $order) {
    $va = isset($a[$sort_by]) ? (float)$a[$sort_by] : 0;
    $vb = isset($b[$sort_by]) ? (float)$b[$sort_by] : 0;
    if ($va == $vb) return 0;
    if ($order === 'asc') return ($va < $vb) ? -1 : 1;
    return ($va > $vb) ? -1 : 1;
});

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_stock.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Producto','Stock','Costo']);
    foreach ($reporte as $r) {
        fputcsv($out, [$r['nombre'], $r['stock'], number_format((float)$r['costo'],0,',','.')]);
    }
    fclose($out);
    exit;
}

// Paginado simple
$total_items = count($reporte);
$total_pages = max(1, ceil($total_items / $per_page));
if ($page > $total_pages) $page = $total_pages;
$start = ($page - 1) * $per_page;
$reporte_page = array_slice($reporte, $start, $per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte Bajo Stock</title>
<link rel="stylesheet" href="stock.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <div class="container">
    <div class="header-row">
      <div class="header-left"><h2>Productos con Bajo Stock</h2></div>
      <div class="header-right">
        <a class="btn" href="dashboard.php">Volver</a>
        <a class="btn" href="reporte_stock.php?export=csv&umbral=<?php echo $umbral;?>&q=<?php echo urlencode($q);?>">Exportar CSV</a>
        <button class="btn" onclick="window.print()">Imprimir</button>
      </div>
    </div>

    <?php if(!empty($mensaje)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <form method="post" style="display:flex;gap:12px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
      <label for="umbral">Umbral (<=):</label>
      <input id="umbral" name="umbral" class="form-control" type="number" style="width:120px;" value="<?php echo $umbral; ?>" required>

      <label for="q">Buscar:</label>
      <input id="q" name="q" class="form-control" type="text" placeholder="Nombre producto" value="<?php echo htmlspecialchars($q); ?>">

      <label for="per_page">Por página:</label>
      <select id="per_page" name="per_page" class="form-control">
        <option value="5"<?php if($per_page==5) echo ' selected';?>>5</option>
        <option value="10"<?php if($per_page==10) echo ' selected';?>>10</option>
        <option value="25"<?php if($per_page==25) echo ' selected';?>>25</option>
      </select>

      <label for="sort_by">Ordenar por:</label>
      <select id="sort_by" name="sort_by" class="form-control">
        <option value="stock"<?php if($sort_by=='stock') echo ' selected';?>>Stock</option>
        <option value="costo"<?php if($sort_by=='costo') echo ' selected';?>>Costo</option>
      </select>

      <select id="order" name="order" class="form-control">
        <option value="desc"<?php if($order=='desc') echo ' selected';?>>Desc</option>
        <option value="asc"<?php if($order=='asc') echo ' selected';?>>Asc</option>
      </select>

      <button class="btn" type="submit">Aplicar</button>
    </form>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr><th>Producto</th><th>Stock Actual</th><th>Costo</th></tr>
          </thead>
          <tbody>
            <?php if (empty($reporte_page)): ?>
              <tr><td colspan="3" style="text-align:center">No se encontraron productos bajo el umbral de <?php echo $umbral; ?>.</td></tr>
            <?php else: foreach ($reporte_page as $r): ?>
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

    <!-- Paginación -->
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <form method="post" style="display:inline;">
        <!-- mantener filtros en paginacion -->
        <input type="hidden" name="umbral" value="<?php echo $umbral; ?>">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
        <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
        <input type="hidden" name="sort_by" value="<?php echo $sort_by; ?>">
        <input type="hidden" name="order" value="<?php echo $order; ?>">
        <button class="btn" type="submit" name="page" value="1" <?php if($page<=1) echo 'disabled';?>>Primera</button>
        <button class="btn" type="submit" name="page" value="<?php echo max(1,$page-1);?>" <?php if($page<=1) echo 'disabled';?>>Anterior</button>
        <span>Página <?php echo $page;?> / <?php echo $total_pages;?> (<?php echo $total_items;?> items)</span>
        <button class="btn" type="submit" name="page" value="<?php echo min($total_pages,$page+1);?>" <?php if($page>=$total_pages) echo 'disabled';?>>Siguiente</button>
        <button class="btn" type="submit" name="page" value="<?php echo $total_pages;?>" <?php if($page>=$total_pages) echo 'disabled';?>>Última</button>
      </form>
    </div>

  </div>
</body>
</html>
