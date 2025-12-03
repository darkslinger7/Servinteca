<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/login.php");
    exit();
}
// Asumo que tu header.php incluye la estructura HTML inicial
include '../includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<section class="empresas-list" style="max-width: 900px;">
    <h2>📈 Reporte Mensual de Servicios</h2>
    
    <div style="width: 100%; height: 400px; margin: auto;">
        <canvas id="serviciosChart"></canvas>
    </div>
    
    <div id="loading" style="text-align: center; margin-top: 20px;">Cargando datos...</div>
</section>

<script>
function dibujarGrafico(datos) {
    const chartContainer = document.getElementById('serviciosChart');
    const loading = document.getElementById('loading');
    loading.style.display = 'none';

    if (datos.length === 0) {
        // Mostrar un mensaje si no hay datos
        const parent = chartContainer.parentElement;
        chartContainer.remove();
        parent.innerHTML = '<p style="text-align:center; font-weight: bold; padding: 20px;">No hay servicios registrados para generar el reporte.</p>';
        return;
    }

    // Preparar los datos para Chart.js
    const labels = datos.map(item => item[0]); // Meses/Años (ej. 2025-11)
    const servicios = datos.map(item => item[1]); // Cantidad de servicios

    new Chart(chartContainer, {
        type: 'bar', // Tipo de gráfico: Barras
        data: {
            labels: labels,
            datasets: [{
                label: 'Servicios Realizados',
                data: servicios,
                backgroundColor: 'rgba(99, 219, 255, 0.6)', // Color Rojo (Puedes cambiarlo)
                borderColor: 'rgba(99, 224, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Permite que el gráfico use el tamaño del div contenedor
            scales: {
                y: {
                    beginAtZero: true,
                    // Asegurar que el eje Y muestre solo números enteros (para conteos)
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {if (value % 1 === 0) {return value;}}
                    },
                    title: {
                        display: true,
                        text: 'Cantidad de Servicios'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Ocultar la leyenda si solo hay una serie
                },
                title: {
                    display: true,
                    text: 'Servicios por Mes (' + labels[0].substring(0, 4) + ' - ' + labels[labels.length - 1].substring(0, 4) + ')'
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // 3. Llamada Fetch para obtener los datos JSON
    fetch('servicios_data.php') 
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar los datos del reporte.');
            }
            return response.json();
        })
        .then(data => {
            dibujarGrafico(data);
        })
        .catch(error => {
            document.getElementById('loading').innerHTML = 'Error: No se pudieron cargar los datos del gráfico. Verifique la base de datos.';
            console.error(error);
        });
});
</script>
<?php include '../includes/footer.php'; ?>
