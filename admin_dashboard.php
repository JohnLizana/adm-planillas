<?php
// admin_dashboard.php
session_start();

// Verificar si el usuario está logueado y si su rol es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administrador</title>
</head>
<body>
    <h1>Bienvenido, Administrador (<?php echo htmlspecialchars($_SESSION['username']); ?>)</h1>
    <p>Este es el panel exclusivo para usuarios con rol de **Administrador**.</p>
    <p><a href="logout.php">Cerrar Sesión</a></p>
</body>
</html>