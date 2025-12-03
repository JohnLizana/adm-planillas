<?php
// login.php (Simplificado sin filtro de rol en la consulta)
session_start();
include 'db.php'; // Incluye el archivo de conexión

$error_message = '';

// 1. Procesar el formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 2. Preparar y ejecutar la consulta (Busca SOLO por nombre de usuario)
    // Ya no se necesita el campo user_type en el formulario.
    $stmt = $pdo->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Verificar usuario y contraseña
    if ($user && password_verify($password, $user['password'])) {
        // Inicio de sesión exitoso
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol']; // El rol se obtiene de la BD

        // Redirigir según el rol detectado
        if ($user['rol'] === 'admin') {
            header('Location: admin_dashboard.php');
        } else { // Rol 'user'
            header('Location: user_area.php');
        }
        exit;
    } else {
        $error_message = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Estilo Apple</title>
    <link rel="stylesheet" href="css/login.css"> 
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1 class="logo"><img src="img/login.png" alt="" srcset="" style="width:5rem"></h1>
            <h2 class="title">Iniciar Sesión</h2>
            
            <?php if ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form id="login-form" method="POST" action="login.php">
                <div class="input-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" 
                           placeholder="usuario" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="login-btn">Continuar</button>
            </form>

            <p class="forgot-password">
                ¿Olvidaste tu contraseña? <a href="#">Restablecer</a>
            </p>
        </div>
    </div>
    
    </body>
</html>