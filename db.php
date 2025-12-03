<?php
// db.php
$host = 'localhost';
$db = 'DataPlanillas'; // ¡Cambia esto!
$user = 'root'; // ¡Cambia esto por un usuario con permisos limitados en producción!
$pass = ''; // ¡Cambia esto!

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Configura PDO para lanzar excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa"; // Solo para probar la conexión
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>