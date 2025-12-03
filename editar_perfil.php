<?php
session_start();
include 'db.php'; 

// Redirigir si no está logeado o no es rol 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// ----------------------------------------------------
// 1. Cargar datos actuales del usuario
// ----------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) {
        // Esto no debería pasar si la sesión es válida
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $current_username = $current_user['username'];

} catch (PDOException $e) {
    $error_message = "Error al cargar datos: " . $e->getMessage();
}


// ----------------------------------------------------
// 2. Procesar el formulario POST
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    // Bandera para indicar si se necesita actualizar la BD
    $update_needed = false;
    $update_fields = [];
    $update_values = [];

    // A. Actualizar Nombre de Usuario
    if ($new_username && $new_username !== $current_username) {
        $update_fields[] = "username = ?";
        $update_values[] = $new_username;
        $update_needed = true;
    }

    // B. Actualizar Contraseña
    if (!empty($new_password)) {
        // 1. Re-autenticar al usuario con la contraseña antigua
        $stmt_auth = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt_auth->execute([$user_id]);
        $user_auth = $stmt_auth->fetch(PDO::FETCH_ASSOC);

        if (!$user_auth || !password_verify($old_password, $user_auth['password'])) {
            $error_message = "La contraseña actual es incorrecta.";
        } else {
            // 2. Hash la nueva contraseña e incluirla en la actualización
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update_fields[] = "password = ?";
            $update_values[] = $new_password_hash;
            $update_needed = true;
        }
    }
    
    // C. Ejecutar la Actualización
    if (empty($error_message) && $update_needed) {
        $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_values[] = $user_id;

        try {
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute($update_values);

            // Si el nombre de usuario fue actualizado, actualizar la sesión
            if (in_array("username = ?", $update_fields)) {
                $_SESSION['username'] = $new_username;
                $current_username = $new_username; // Actualizar variable local
            }
            
            $success_message = "¡Perfil actualizado exitosamente!";

        } catch (PDOException $e) {
            // Error específico si el nombre de usuario ya existe (constraint UNIQUE)
            if ($e->getCode() == '23000') { 
                $error_message = "Ese nombre de usuario ya está en uso.";
            } else {
                $error_message = "Error al actualizar la base de datos: " . $e->getMessage();
            }
        }
    } else if (empty($error_message) && !$update_needed) {
         $error_message = "No hay cambios para guardar.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
        <?php include 'sidebar.html'; // O pega el código aquí ?>
    <main class="main-content" id="main-content">
        <button class="menu-btn" onclick="toggleSidebar()">☰</button>
        
        <header class="content-header">
            <h1>👤 Editar Perfil</h1>
            <p class="subtitle">Actualiza tu nombre de usuario y contraseña.</p>
        </header>

        <section class="profile-section">
            
            <?php if ($success_message): ?>
                <div class="message success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="ios-card-container"> <form method="POST" action="editar_perfil.php">
                    
                    <div class="input-group">
                        <label for="username">Nombre de Usuario Actual</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($current_username); ?>" required>
                    </div>
                    
                    <h3>Actualizar Contraseña</h3>
                    <p class="subtitle-small">Solo llena estos campos si deseas cambiar tu contraseña.</p>

                    <div class="input-group">
                        <label for="old_password">Contraseña Actual (Obligatoria si cambias la contraseña)</label>
                        <input type="password" id="old_password" name="old_password" placeholder="••••••••">
                    </div>

                    <div class="input-group">
                        <label for="new_password">Nueva Contraseña</label>
                        <input type="password" id="new_password" name="new_password" placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="login-btn" style="width: auto; padding: 10px 25px;">
                        Guardar Cambios
                    </button>
                </form>
            </div>
        </section>
        
    </main>
    <script src="js/script.js"></script> 
</body>
</html>