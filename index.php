<?php
// index.php (login)
session_start();
include 'db.php';

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    try {
        $stmt = $conn->prepare("{CALL Sp_Login(?, ?)}");
        $stmt->bindParam(1, $usuario);
        $stmt->bindParam(2, $contrasena);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado && (isset($resultado['Resultado']) && ((int)$resultado['Resultado'] === 1 || $resultado['Resultado'] === '1'))) {
            $_SESSION['loggedin'] = true;
            $_SESSION['usuario'] = $usuario;
            header('Location: dashboard.php');
            exit;
        } else {
            $mensaje = "Acceso no permitido. Credenciales inválidas.";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al intentar iniciar sesión: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Acceso | CERTAMEN4_BASE</title>
<link rel="stylesheet" href="assets/style.css">
<script defer src="assets/script.js"></script>
</head>
<body>


  <div style="max-width:420px;margin:40px auto;">
    <div class="container">
      <h2>🔒 Acceso al Sistema</h2>

      <?php if(!empty($mensaje)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="form-group">
          <label for="usuario">Usuario</label>
          <input id="usuario" name="usuario" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="contrasena">Contraseña</label>
          <input id="contrasena" name="contrasena" type="password" class="form-control" required>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;">Ingresar</button>
      </form>
    </div>
  </div>

</body>
</html>
