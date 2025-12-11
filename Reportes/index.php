<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}

require_once '../includes/database.php';

include '../includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .dashboard-container { max-width: 1300px; margin: 20px auto; padding: 0 20px; }
    .grid-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px; }
    .full-width { grid-column: 1 / -1; }
    
    .chart-card { 
        background: white; 
        padding: 20px; 
        border-radius: 10px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        border: 1px solid #e1e1e1; 
        display: flex; 
        flex-direction: column;
    }
    
    .chart-card h3 { margin-bottom: 15px; color: #002366; font-size: 1.1rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
    .canvas-container { position: relative; flex-grow: 1; min-height: 250px; }
    #loading { text-align: center; font-size: 1.2rem; margin-top: 50px; color: #666; }
</style>

<main class="dashboard-container">
    <h2>📊 Tablero Gerencial</h2>
    <p class="text-muted">Resumen estratégico, táctico y operativo del negocio.</p>
    
    <div id="loading"><i class="fas fa-spinner fa-spin"></i> Generando reportes...</div>

    <div class="grid-charts" id="chartsArea" style="display:none;">
        
        <div class="chart-card full-width">
            <h3>💰 Flujo de Caja: Ventas vs Compras (Últimos 12 meses)</h3>
            <div class="canvas-container" style="height: 300px;">
                <canvas id="finanzasChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>🛠️ Servicios Realizados</h3>
            <div class="canvas-container">
                <canvas id="serviciosChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>🏆 Top 5 Productos Más Vendidos</h3>
            <div class="canvas-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3>⚙️ Ingresos: Máquinas vs Repuestos</h3>
            <div class="canvas-container" style="height: 200px;">
                <canvas id="distribucionChart"></canvas>
            </div>
            <div id="leyendaDistribucion" style="margin-top: 15px; font-size: 0.9rem;"></div>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('data_global.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('chartsArea').style.display = 'grid';
            inicializarGraficos(data);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('loading').innerHTML = "<span style='color:red'>Error cargando datos.</span>";
        });
});

function inicializarGraficos(data) {
  
    new Chart(document.getElementById('finanzasChart'), {
        type: 'line',
        data: {
            labels: data.finanzas.map(d => d.mes),
            datasets: [
                { label: 'Ventas ($)', data: data.finanzas.map(d => d.ventas), borderColor: '#22c55e', backgroundColor: 'rgba(34, 197, 94, 0.1)', fill: true, tension: 0.3 },
                { label: 'Compras ($)', data: data.finanzas.map(d => d.compras), borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } }
    });

 
    new Chart(document.getElementById('serviciosChart'), {
        type: 'bar',
        data: {
            labels: data.servicios.map(d => d.mes),
            datasets: [{ label: 'Servicios', data: data.servicios.map(d => d.total), backgroundColor: '#3b82f6' }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { ticks: { stepSize: 1 } } } }
    });

    
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: data.top_productos.map(d => d.nombre),
            datasets: [{ 
                label: 'Unidades Vendidas', 
                data: data.top_productos.map(d => d.cantidad), 
                backgroundColor: '#f59e0b',
                borderWidth: 1
            }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { ticks: { stepSize: 1 } } } }
    });

    const totalDist = data.distribucion_ventas.reduce((a, b) => a + b.total, 0);
    const colores = ['#002366', '#f59e0b', '#3b82f6'];
    
    new Chart(document.getElementById('distribucionChart'), {
        type: 'doughnut',
        data: {
            labels: data.distribucion_ventas.map(d => d.tipo),
            datasets: [{ data: data.distribucion_ventas.map(d => d.total), backgroundColor: colores }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    const leyenda = document.getElementById('leyendaDistribucion');
    let htmlLeyenda = '<ul style="list-style:none; padding:0;">';
    
    const formatoMoneda = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

    data.distribucion_ventas.forEach((item, i) => {
        const pct = totalDist > 0 ? ((item.total / totalDist) * 100).toFixed(1) : 0;
        const color = colores[i % colores.length];
        const monto = formatoMoneda.format(item.total);
        htmlLeyenda += `<li style="display:flex; justify-content:space-between; margin-bottom:5px; border-bottom:1px solid #eee;">
            <span><span style="display:inline-block; width:10px; height:10px; background:${color}; border-radius:50%; margin-right:5px;"></span>${item.tipo}</span>
            <span>${monto} <small class="text-muted">(${pct}%)</small></span>
        </li>`;
    });
    leyenda.innerHTML = htmlLeyenda + '</ul>';
}
</script>

<?php include '../includes/footer.php'; ?>