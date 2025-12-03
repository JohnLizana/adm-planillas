<?php
// Incluir el archivo de conexión a la base de datos
// (Asegúrate de que tus datos de conexión son correctos)
include 'db.php'; 

// 1. Configuración de cabeceras para respuesta JSON
header('Content-Type: application/json');

// 2. Validar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 3. Obtener y validar datos
$tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
$creador_id = filter_input(INPUT_POST, 'creador_id', FILTER_VALIDATE_INT);

// Verificación básica
if (empty($tipo) || $creador_id === false || $creador_id === null) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']);
    exit;
}

// Asegurar que el tipo sea válido
if (!in_array($tipo, ['Antofagasta', 'Plasco'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de planilla inválido.']);
    exit;
}

// 4. Preparar y ejecutar la consulta SQL (usando sentencias preparadas por seguridad)
$sql = "INSERT INTO planillas (tipo, creador_id) VALUES (?, ?)";

try {
    // Asumiendo que $pdo es tu objeto de conexión PDO definido en 'db_connection.php'
    // Si usas mysqli, ajusta el código
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipo, $creador_id]);
    
    // Obtener el ID del registro recién creado
    $last_id = $pdo->lastInsertId();

    // 5. Responder con éxito
    echo json_encode([
        'success' => true,
        'message' => 'Registro creado.',
        'id' => $last_id
    ]);

} catch (PDOException $e) {
    // 6. Responder con error de base de datos
    echo json_encode([
        'success' => false,
        'message' => 'Error de BD: ' . $e->getMessage()
    ]);
}
?>