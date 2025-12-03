<?php
// user_area.php
session_start();
// (Verificación de sesión y rol 'user' aquí)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'user') {
    header('Location: login.php');
    exit;
}
// ----------------------------------------------------
// 1. OBTENER LAS PLANILLAS DEL USUARIO LOGEADO
// ----------------------------------------------------
include 'db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$planillas = [];
try {
    // Consulta para seleccionar las planillas creadas por este usuario
    $sql = "SELECT id, fecha_creacion, tipo FROM planillas WHERE creador_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $planillas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // En caso de error de BD, puedes registrarlo o mostrar un mensaje
    $error_message = "Error al cargar planillas: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Área de Usuario - Dashboard</title>
    <link rel="stylesheet" href="css/sidebar.css"> 
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">

    <?php include 'sidebar.html'; // O pega el código aquí ?>

    <main class="main-content">
        <button class="menu-btn" onclick="toggleSidebar()">☰</button>
        
       <header class="content-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($username); ?> 👋</h1>
            <p class="subtitle">Resumen de tus planillas y actividad reciente.</p>
        </header>

        <div class="dropdown" style="margin-bottom: 30px; text-align: right;">
            <button onclick="toggleDropdown()" class="dropdown-btn">
                Crear Planilla ▼
            </button>
            
            <div id="planillaDropdown" class="dropdown-content">
                <a class="create-planilla-link" data-tipo="Antofagasta">Antofagasta</a>
                <a class="create-planilla-link" data-tipo="Plasco">Plasco</a>
            </div>
        </div>

        <section class="data-section">
            <h2>📜 Mis Planillas</h2>

            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <div class="ios-table-container">
                <table class="ios-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Fecha Creación</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (empty($planillas)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;" data-label="Mensaje">
                                    No tienes planillas creadas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($planillas as $planilla): 
                                // Formatear la fecha para que se vea mejor (ej: 24/11/2025 14:00)
                                $fecha_formateada = date('d/m/Y H:i', strtotime($planilla['fecha_creacion']));
                                
                                // Determinar el estilo del "estado" (simulando un badge)
                                $badge_class = ($planilla['tipo'] == 'Antofagasta') ? 'processing' : 'success';
                            ?>
                                <tr>
                                    <td data-label="ID">#<?php echo htmlspecialchars($planilla['id']); ?></td>
                                    <td data-label="Tipo">
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($planilla['tipo']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Fecha Creación"><?php echo $fecha_formateada; ?></td>
                                    <td data-label="Acción">
                                        <a href="ver_planilla.php?id=<?php echo htmlspecialchars($planilla['id']); ?>" class="view-link">     
                                    Ver/Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="js/script.js"></script>
</body>
</html>