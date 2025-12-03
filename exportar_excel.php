<?php
session_start();
include 'db.php'; 

// 1. Configuración de Zona Horaria
date_default_timezone_set('America/Santiago'); 

// 2. Seguridad y Obtención de ID
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado.");
}

$planilla_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$planilla_id) {
    die("ID de planilla no válido para exportar.");
}

try {
    // Obtener información para el nombre del archivo
    $stmt_planilla = $pdo->prepare("SELECT tipo FROM planillas WHERE id = ?");
    $stmt_planilla->execute([$planilla_id]);
    $planilla_info = $stmt_planilla->fetch(PDO::FETCH_ASSOC);

    if (!$planilla_info) {
        die("Planilla no encontrada.");
    }

    $nombre_planilla = $planilla_info['tipo'];
    $fecha_exportacion = date('Ymd_His');
    // Usamos .xls, que es reconocido por Excel para formatos HTML/XML, funciona en versiones modernas.
    $nombre_archivo = "Reporte_" . $nombre_planilla . "_" . $fecha_exportacion . ".xls"; 

    // 3. Configurar Encabezados para Forzar la Descarga como Archivo de Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    // 4. Consulta SQL (MISMA lógica que en ver_planilla.php)
    $sql = "
        SELECT 
            rp.fecha, 
            rp.n_guia, 
            rp.cant_pallet, 
            rp.valor, 
            rp.conductor, 
            rp.patente, 
            rp.total,
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

    // 5. Generar la Tabla HTML (Con Estilos)
    
    // Definición de estilos CSS básicos para la tabla y encabezados
    
    // 5. Generar la Tabla HTML (Con Estilos Reforzados)
    
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<style>
            table { 
                border-collapse: collapse; 
                width: 100%; 
                font-family: Arial, sans-serif; 
                font-size: 10pt;
            }
            
            /* Estilo general de celdas y bordes */
            th, td { 
                border: 1px solid #999; /* Bordes grises */
                padding: 10px; 
                text-align: left; 
            }
            
            /* Estilo de Encabezados (Amarillo y Texto Blanco) */
            th { 
                background-color: #FFC107; /* Amarillo */
                color: white; /* Texto Blanco */
                font-weight: bold; 
                text-transform: uppercase;
                text-align: center; /* Centrar texto en el encabezado */
            }

            /* Rayado (Filas pares con fondo gris muy claro) */
            tr:nth-child(even) { 
                background-color: #f7f7f7; 
            } 
            
            /* Alineación a la derecha para números y totales */
            .number {
                text-align: right;
            }
          </style>';

    echo '<table>';
// Generación de Encabezados con estilo INLINE
    echo '<thead>';
    
    // Define el estilo que Excel debe leer
    $header_style = 'style="background-color: #FFC107; color: white; font-weight: bold; text-transform: uppercase; text-align: center;"';
    
    echo '<tr>';
    echo '<th ' . $header_style . '>Fecha y Hora</th>';
    echo '<th ' . $header_style . '>Nº Guía</th>';
    echo '<th ' . $header_style . '>Cant. Pallet</th>';
    echo '<th ' . $header_style . '>Valor</th>';
    echo '<th ' . $header_style . '>Conductor</th>';
    echo '<th ' . $header_style . '>Patente</th>';
    echo '<th ' . $header_style . '>Total</th>';
    echo '<th ' . $header_style . '>Total Pallet Diario</th>';
    echo '</tr>';
    echo '</thead>';

    // Contenido de la Tabla
    echo '<tbody>';
// 8. Escribir Filas de Datos
    foreach ($registros as $row) {
        $fecha_formateada = date('d/m/Y H:i', strtotime($row['fecha']));
        
        // Formato para Excel (sin símbolo '$' y usando separador de miles '.')
        // Esto facilita que Excel lo reconozca como número.
        $valor_formateado = number_format($row['valor'], 0, '', '.');
        $total_formateado = number_format($row['total'], 0, '', '.');
        $cant_pallet = number_format($row['cant_pallet'], 0, '', '.');
        $total_pallet_diario = number_format($row['total_pallet_diario'], 0, '', '.');

        echo '<tr>';
        // Fecha y Guía (Alineación izquierda por defecto)
        echo '<td>' . htmlspecialchars($fecha_formateada) . '</td>';
        echo '<td>' . htmlspecialchars($row['n_guia']) . '</td>';
        
        // Cant. Pallet y Valor (Alineación derecha - clase .number)
        echo '<td class="number">' . htmlspecialchars($cant_pallet) . '</td>';
        echo '<td class="number">' . htmlspecialchars($valor_formateado) . '</td>';
        
        // Conductor y Patente (Alineación izquierda por defecto)
        echo '<td>' . htmlspecialchars($row['conductor']) . '</td>';
        echo '<td>' . htmlspecialchars($row['patente']) . '</td>';
        
        // Total y Total Pallet Diario (Alineación derecha - clase .number)
        echo '<td class="number">' . htmlspecialchars($total_formateado) . '</td>';
        echo '<td class="number">' . htmlspecialchars($total_pallet_diario) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    
    exit;
} catch (PDOException $e) {
    die("Error al obtener los datos para la exportación: " . $e->getMessage());
}


?>