<?php
/**
 * Componente que genera gráficos y tarjetas de resumen
 * del total trasladado por conductor para una planilla específica.
 * Requiere que las variables $pdo y $planilla_id estén definidas previamente.
 */

// -----------------------------------------------------
// 1. OBTENCIÓN DE DATOS POR CONDUCTOR
// -----------------------------------------------------

try {
    $sql = "
        SELECT 
            conductor, 
            SUM(cant_pallet) AS total_pallets,
            SUM(total) AS total_valor
        FROM 
            registros_planilla
        WHERE 
            planilla_id = :planilla_id
        GROUP BY 
            conductor
        ORDER BY 
            total_pallets DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':planilla_id' => $planilla_id]);
    $resultados_conductores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<p class='error-message'>Error al cargar los datos del gráfico: " . htmlspecialchars($e->getMessage()) . "</p>";
    return; // Detiene la ejecución del componente si hay error de DB
}

if (empty($resultados_conductores)) {
    echo "<p class='message secondary-button' style='border: none; background-color: var(--bg-primary);'>No hay registros para generar gráficos.</p>";
    return;
}

// -----------------------------------------------------
// 2. PREPARACIÓN DE DATOS PARA JAVASCRIPT
// -----------------------------------------------------
$labels = [];          // Nombres de los conductores
$data = [];            // Totales de pallets de cada conductor
$total_general_pallets = 0;    // Total de pallets de toda la planilla
$colores = ['#007AFF', '#34C759', '#FF9500', '#5856D6', '#FF3B30', '#AF52DE', '#FFCC00', '#5AC8FA']; // Colores iOS

foreach ($resultados_conductores as $index => $row) {
    $labels[] = $row['conductor'];
    $data[] = (int)$row['total_pallets'];
    $total_general_pallets += (int)$row['total_pallets'];
}

$labels_json = json_encode($labels);
$data_json = json_encode($data);
$colores_json = json_encode($colores);

// -----------------------------------------------------
// 3. RENDERIZACIÓN DE HTML (Gráfico y Tarjetas)
// -----------------------------------------------------
?>
<div class="ios-card-container">
    <h2>📊 Resumen de Pallets y Valor por Conductor</h2>
    
    <div style="width: 100%; max-width: 800px; margin: 0 auto 30px;">
        <h3 style="text-align: center; color: var(--text-secondary); margin-bottom: 25px;">
            Total Pallets Trasladados en Planilla: 
            <span style="color: var(--link-color); font-weight: 700; font-size: 1.2em;">
                <?php echo number_format($total_general_pallets, 0, ',', '.'); ?>
            </span>
        </h3>
        <canvas id="palletsChart"></canvas>
    </div>
    
    <div style="border-top: 1px solid var(--border-color); margin-top: 20px; padding-top: 20px;">
        <h3>Detalle por Conductor</h3>
        
        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px;">
            <?php foreach ($resultados_conductores as $index => $row): 
                $porcentaje = ($row['total_pallets'] / $total_general_pallets) * 100;
                $color_tarjeta = $colores[$index % count($colores)];
                
                // Formateo del valor total
                $valor_formateado = '$' . number_format($row['total_valor'], 0, ',', '.');
            ?>
                <div class="ios-card-container" style="flex: 1 1 250px; padding: 15px; border-left: 5px solid <?php echo $color_tarjeta; ?>; margin-bottom: 0;">
                    
                    <p style="font-weight: 700; margin: 0; font-size: 1.1em; color: <?php echo $color_tarjeta; ?>;">
                        <?php echo htmlspecialchars($row['conductor']); ?>
                    </p>
                    
                    <p style="margin: 5px 0 0 0; color: var(--text-primary); font-size: 1em; font-weight: 600;">
                        Valor Total: <?php echo $valor_formateado; ?>
                    </p>
                    
                    <p style="margin: 5px 0 0 0; color: var(--text-primary); font-size: 1.4em; font-weight: 800;">
                        <?php echo number_format($row['total_pallets'], 0, ',', '.'); ?> Pallets
                    </p>
                    
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9em;">
                        (<?php echo number_format($porcentaje, 1, ',', '.'); ?>% del total)
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('palletsChart');
    
    const labels = <?php echo $labels_json; ?>;
    const data = <?php echo $data_json; ?>;
    const colores = <?php echo $colores_json; ?>;

    const chartBackgroundColors = data.map((d, i) => colores[i % colores.length] + '80');
    const chartBorderColors = data.map((d, i) => colores[i % colores.length]);

    const textPrimaryColor = getComputedStyle(document.body).getPropertyValue('--text-primary').trim();
    const borderPrimaryColor = getComputedStyle(document.body).getPropertyValue('--border-color').trim();

    new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Pallets Trasladados',
                data: data,
                backgroundColor: chartBackgroundColors,
                borderColor: chartBorderColors,
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textPrimaryColor,
                        font: { size: 12, weight: '600' }
                    },
                    grid: {
                        color: borderPrimaryColor + '30'
                    },
                    title: {
                        display: true,
                        text: 'Pallets',
                        color: textPrimaryColor
                    }
                },
                x: {
                    ticks: {
                        color: textPrimaryColor,
                        font: { size: 12 }
                    },
                    grid: {
                        color: borderPrimaryColor + '30'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Pallets por Conductor',
                    color: textPrimaryColor,
                    font: { size: 16, weight: '600' }
                }
            }
        }
    });
});
</script>