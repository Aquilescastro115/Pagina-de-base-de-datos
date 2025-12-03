<?php
// index.php (login)
session_start();
include 'db.php'; // Asegúrate de que este archivo exista y conecte bien

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    try {
        // Tu lógica original de conexión
        $stmt = $conn->prepare("{CALL Sp_Login(?, ?)}");
        $stmt->bindParam(1, $usuario);
        $stmt->bindParam(2, $contrasena);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validación de resultado
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
<link rel="stylesheet" href="login.css">
</head>
<body>

  <div class="login-container">
      
      <h2>🔒 Acceso al Sistema</h2>

      <?php if(!empty($mensaje)): ?>
          <div class="alert"><?php echo htmlspecialchars($mensaje); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="form-group">
          <label for="usuario">Usuario</label>
          <input id="usuario" name="usuario" class="form-control" required autofocus>
        </div>
        
        <div class="form-group">
          <label for="contrasena">Contraseña</label>
          <input id="contrasena" name="contrasena" type="password" class="form-control" required>
        </div>
        
        <button class="btn-primary" type="submit">Ingresar</button>
      </form>
      
  </div>

</body>
</html>
