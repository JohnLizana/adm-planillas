<?php
session_start();
include 'db.php'; 
date_default_timezone_set('America/Santiago');
// Redirigir si no está logeado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'components/conductores_data.php';

$planilla_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Asegura que el ID sea válido antes de continuar
if (!$planilla_id) {
    die("ID de planilla no válido.");
}

$add_success = '';
$add_error = '';

try {
    // Consulta para obtener el tipo de planilla y el creador para verificación
    $stmt_planilla = $pdo->prepare("SELECT tipo, creador_id FROM planillas WHERE id = ?");
    $stmt_planilla->execute([$planilla_id]);
    $planilla_info = $stmt_planilla->fetch(PDO::FETCH_ASSOC);

    // Seguridad: Verificar que el usuario logeado es el creador de la planilla
    if (!$planilla_info || $planilla_info['creador_id'] != $_SESSION['user_id']) {
        die("Acceso denegado o planilla no encontrada.");
    }
    
} catch (PDOException $e) {
    // Manejo de error si la planilla no se puede cargar (problemas de DB)
    die("Error de base de datos al cargar la planilla: " . $e->getMessage());
}


// ===============================================
// 1. PROCESAR LA ADICIÓN DE UN NUEVO REGISTRO (POST)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_registro'])) {
    
    // 1. Recolección y saneamiento de datos
    $fecha = trim($_POST['fecha'] ?? '');
    $n_guia = trim($_POST['n_guia'] ?? '');
    $cant_pallet = filter_input(INPUT_POST, 'cant_pallet', FILTER_VALIDATE_INT);
    $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT); 
    $conductor = trim($_POST['conductor'] ?? '');
    $patente = trim($_POST['patente'] ?? '');
    $total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_FLOAT);
    
    $add_error_temp = ''; // Usamos una variable temporal para mensajes de error

    // 2. Validación
    if (empty($fecha) || $cant_pallet === false || $valor === false || empty($conductor) || empty($patente) || $total === false) {
        $add_error_temp = "Error: Todos los campos marcados (*) son obligatorios y deben ser valores válidos.";
    } else {
        try {
            $sql_insert = "INSERT INTO registros_planilla 
                           (planilla_id, fecha, n_guia, cant_pallet, valor, conductor, patente, total) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                $planilla_id, 
                $fecha, 
                $n_guia, 
                $cant_pallet, 
                $valor, 
                $conductor, 
                $patente, 
                $total
            ]);

            $add_success_msg = "Registro agregado exitosamente.";

            // Redirección para evitar reenvío del formulario (Patrón PRG)
            header("Location: ver_planilla.php?id=" . $planilla_id . "&success=" . urlencode($add_success_msg));
            exit;

        } catch (PDOException $e) {
            $add_error = "Error al guardar el registro: " . $e->getMessage();
        }
    }
    // Si hubo un error de validación, lo asignamos aquí para mostrarlo
    if ($add_error_temp !== '') {
        $add_error = $add_error_temp;
    }
}

// 2. Obtener los registros detallados de la planilla CON EL CÁLCULO TOTAL
$registros = [];
// La consulta calcula el total_pallet_diario agrupando por conductor y fecha.
$sql = "
    SELECT 
        rp.*, 
        SUM(rp_total.cant_pallet) AS total_pallet_diario 
    FROM 
        registros_planilla rp
    INNER JOIN 
        registros_planilla rp_total ON 
        rp.conductor = rp_total.conductor AND 
        DATE(rp.fecha) = DATE(rp_total.fecha) AND 
        rp.planilla_id = rp_total.planilla_id
    WHERE 
        rp.planilla_id = ? 
    GROUP BY 
        rp.registro_id, rp.fecha, rp.n_guia, rp.cant_pallet, rp.valor, rp.conductor, rp.patente, rp.total
    ORDER BY 
        rp.fecha DESC
"; 
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$planilla_id]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar mensajes después de la redirección
if (isset($_GET['success'])) {
    $add_success = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles Planilla - <?php echo htmlspecialchars($planilla_info['tipo'] ?? 'Cargando...'); ?></title>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body data-user-id="<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>">

    <div class="sidebar-container" id="sidebar">
        </div>
    <?php include 'sidebar.html'; // O pega el código aquí ?>
    <main class="main-content" id="main-content">
        <button class="menu-btn" onclick="toggleSidebar()">☰</button>
        
        <header class="content-header">
    <h1>Detalles de Planilla ID #<?php echo htmlspecialchars($planilla_id); ?></h1>
    <p class="subtitle">Tipo: <?php echo htmlspecialchars($planilla_info['tipo'] ?? 'N/A'); ?></p>
    
    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <a href="user_area.php" class="ios-button secondary-button">
            ← Volver al Dashboard
        </a>
        
        <a 
            href="exportar_excel.php?id=<?php echo htmlspecialchars($planilla_id); ?>" 
            class="ios-button secondary-button" 
            style="background-color: #4cd964; color: white; border-color: #4cd964;"
        >
            Exportar a Excel 📊
        </a>
    </div>
</header>
        
        <section class="data-section">
            <div class="header-with-button">
                <button id="toggle-add-form" class="ios-button secondary-button">
                    Agregar
                </button>
            </div>
            
            <?php if (!empty($add_success)): ?>
                <div class="message success-message"><?php echo $add_success; ?></div>
            <?php endif; ?>

            <?php if (!empty($add_error)): ?>
                <div class="message error-message"><?php echo $add_error; ?></div>
            <?php endif; ?>

            <div id="add-record-form-container" class="ios-card-container collapsed">
                <form method="POST" action="ver_planilla.php?id=<?php echo htmlspecialchars($planilla_id); ?>">
                    <input type="hidden" name="add_registro" value="1">
                    
                    <div class="form-row"> 
                        <div class="input-group"><label for="fecha">Fecha y Hora (*)</label>
                            <input type="datetime-local" id="fecha" name="fecha" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                        <div class="input-group"><label for="n_guia">Nº Guía (*)</label>
                            <input type="text" id="n_guia" name="n_guia" required>
                        </div>
                        <div class="input-group"><label for="cant_pallet">Cant. Pallet (*)</label>
                            <input type="number" step="1" id="cant_pallet" name="cant_pallet" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group"><label for="valor">Valor (*)</label>
                            <input type="number" step="1" id="valor" name="valor" required>
                        </div>
                        <div class="form-row">
                <div class="input-group"><label for="conductor">Conductor (*)</label>
                    <input type="text" id="conductor" name="conductor" required 
                           list="listaConductores"> </div>
                </div>
                    
                    <datalist id="listaConductores">
                        <?php 
                        // Usamos el array cargado desde conductores_data.php
                        foreach ($conductores_array as $conductor): 
                        ?>
                            <option value="<?php echo htmlspecialchars($conductor); ?>">
                        <?php endforeach; ?>
                    </datalist>
                        <div class="input-group"><label for="patente">Placa Patente (*)</label>
                            <input type="text" id="patente" name="patente" list="listaPatentes" required>
                            <datalist id="listaConductores">
                                <?php foreach ($conductores_array as $conductor): ?>
                                    <option value="<?php echo htmlspecialchars($conductor); ?>">
                                <?php endforeach; ?>
                            </datalist>

                            <datalist id="listaPatentes">
                                <?php foreach ($patentes_array as $patente): ?>
                                    <option value="<?php echo htmlspecialchars($patente); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    
                    <div class="form-row">
                         <div class="input-group"><label for="total">Total (*)</label>
                            <input type="number" step="1" id="total" name="total" required>
                        </div>
                         <div class="input-group"></div>
                         <div class="input-group"></div>
                    </div>
                    
                    <button type="submit" class="ios-button primary-button full-width-button">
                        Guardar Nuevo Registro
                    </button>
                    <p class="subtitle-small" style="margin-top: 10px;">(*) Campos obligatorios.</p>
                </form>
            </div>
        </section>
        

        <section class="data-section">
            <h2>📜 Registros de Planilla</h2>

            <div class="ios-table-container" style="overflow-x: auto;">
                <table class="ios-table">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Fecha</th>
                            <th style="min-width: 100px;">Nº Guía</th>
                            <th style="min-width: 100px;">Cant. Pallet</th>
                            <th style="min-width: 100px;">Valor</th>
                            <th style="min-width: 100px;">Conductor</th>
                            <th style="min-width: 100px;">Patente</th>
                            <th class="text-right" style="min-width: 100px;">Total</th>
                            <th style="min-width: 120px;">Total Pallet Diario</th>
                            <th style="min-width: 80px;">Acción</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center;" data-label="Mensaje">
                                    Esta planilla no tiene registros.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $registro): 
                                $fecha_formateada = date('d/m/Y H:i', strtotime($registro['fecha']));
                                $badge_class = ($registro['total'] >= 0) ? 'success' : 'cancelled';
                            ?>
                                <tr>
                                    <td data-label="Fecha"><?php echo $fecha_formateada; ?></td>
                                    <td data-label="Nº Guía"><?php echo htmlspecialchars($registro['n_guia']); ?></td>
                                    <td data-label="Cant. Pallet"><?php echo htmlspecialchars($registro['cant_pallet']); ?></td>
                                    <td data-label="Valor"><?php echo '$' . number_format($registro['valor'], 0, ',', '.'); ?></td>
                                    <td data-label="Conductor"><?php echo htmlspecialchars($registro['conductor']); ?></td>
                                    <td data-label="Patente"><?php echo htmlspecialchars($registro['patente']); ?></td>
                                    
                                    <td data-label="Total" class="text-right">
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo '$' . number_format($registro['total'], 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    
                                    <td data-label="Total Pallet Diario">
                                        <strong><?php echo htmlspecialchars($registro['total_pallet_diario'] ?? '0'); ?></strong>
                                    </td>
                                    
                                    <td data-label="Acción"><a href="#" class="view-link">Editar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <hr>
                    <?php 
            // Asegúrate de que tienes un ID válido antes de incluir el componente
            if ($planilla_id) {
                include 'components/graficos_planilla.php'; 
            }
            ?>

            <div class="ios-table-container">
            </div>
    </main>

    <script src="js/script.js"></script> 
</body>
</html>