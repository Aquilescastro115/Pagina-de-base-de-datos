<?php
// reporte_iva.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php'); exit;
}
include 'db.php';

$mensaje = '';
// IVA por defecto 19
$iva = 19;
$search = '';

// Si llegó por POST (cambiar IVA o búsqueda)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['iva'])) {
        $iva = (float)str_replace(',', '.', $_POST['iva']);
        if ($iva < 0) $iva = 0;
    }
    if (isset($_POST['search'])) {
        $search = trim($_POST['search']);
    }
}

// Obtener datos desde SP
$reporte = [];
try {
    $stmt = $conn->prepare("{CALL sp_ReportePreciosConIVA}");
    $stmt->execute();
    $reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al generar el reporte: " . $e->getMessage();
}

// Si el usuario solicita exportar CSV via GET ?export=csv
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_iva.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Producto','Costo Sin IVA','Precio Final (IVA %'.$iva.')']);
    foreach ($reporte as $r) {
        // aplicar filtro de busqueda si existe (por GET o POST)
        if ($search !== '' && stripos($r['nombre'], $search) === false) continue;
        $costo = (float)$r['costo'];
        $precioFinal = $costo * (1 + $iva/100);
        fputcsv($out, [$r['nombre'], number_format($costo,0,',','.'), number_format($precioFinal,0,',','.')] );
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte IVA</title>
<link rel="stylesheet" href="iva.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <div class="container">
    <div class="header-row">
      <div class="header-left"><h2>Precios con IVA (editable)</h2></div>
      <div class="header-right">
        <a class="btn" href="dashboard.php">Volver</a>
        <a class="btn" href="?export=csv<?php echo $search ? '&search='.urlencode($search):''; ?>">Exportar CSV</a>
        <button class="btn" onclick="window.print()">Imprimir</button>
      </div>
    </div>

    <?php if(!empty($mensaje)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

    <!-- Controles: IVA y búsqueda -->
    <form id="controlsForm" method="post" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;align-items:center;">
      <label for="iva">IVA (%)</label>
      <input id="iva" name="iva" class="form-control" type="number" step="0.01" min="0" style="width:120px;" value="<?php echo htmlspecialchars($iva); ?>">
      <label for="search">Buscar</label>
      <input id="search" name="search" class="form-control" type="text" placeholder="Nombre producto" value="<?php echo htmlspecialchars($search); ?>">
      <button class="btn" type="submit">Aplicar</button>
      <button class="btn" type="button" id="resetBtn">Reset</button>
    </form>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table" id="ivaTable">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Costo Sin IVA</th>
              <th>Precio Final (IVA)</th>
            </tr>
          </thead>
          <tbody>
            <?php
              if (empty($reporte)) {
                echo '<tr><td colspan="3" style="text-align:center">No hay datos.</td></tr>';
              } else {
                foreach ($reporte as $r) {
                    // filtro de búsqueda en servidor
                    if ($search !== '' && stripos($r['nombre'], $search) === false) continue;
                    $costo = (float)$r['costo'];
                    $precioFinal = $costo * (1 + $iva/100);
                    echo '<tr data-costo="'.htmlspecialchars($costo).'">';
                    echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
                    echo '<td>$'.number_format($costo,0,',','.').'</td>';
                    echo '<td class="precio-iva">$'.number_format($precioFinal,0,',','.').'</td>';
                    echo '</tr>';
                }
              }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<script>
// Recálculo en vivo del IVA sin recargar
document.addEventListener('DOMContentLoaded', function(){
  const ivaInput = document.getElementById('iva');
  const table = document.getElementById('ivaTable');
  const resetBtn = document.getElementById('resetBtn');
  const searchInput = document.getElementById('search');

  function recalc() {
    let iva = parseFloat(ivaInput.value);
    if (isNaN(iva)) iva = 0;
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(r => {
      const costo = parseFloat(r.getAttribute('data-costo'));
      if (isNaN(costo)) return;
      const precio = costo * (1 + iva/100);
      // formatear con separador de miles simple
      r.querySelector('.precio-iva').textContent = '$' + numberWithDots(Math.round(precio));
    });
  }
  function numberWithDots(x) {
    // x entero
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  ivaInput.addEventListener('input', recalc);

  // Reset: recarga la página para restablecer servidor (si quieres mantener cliente, cambia)
  resetBtn.addEventListener('click', function(){ window.location.href = 'reporte_iva.php'; });

  // Opcional: submit con Enter en búsqueda también funciona
});
</script>
</body>
</html>
