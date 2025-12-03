<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /Servindteca/auth/login.php");
    exit();
}
include '../includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .dashboard-container {
        max-width: 1300px;
        margin: 20px auto;
        padding: 0 20px;
    }
    /* Grid mejorado para acomodar más reportes */
    .grid-charts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); /* Adaptable */
        gap: 20px;
        margin-top: 20px;
    }
    /* Estilo para que la tabla de alertas ocupe ancho completo si es necesario */
    .full-width {
        grid-column: 1 / -1;
    }
    .chart-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        border: 1px solid #e1e1e1;
        display: flex;
        flex-direction: column;
    }
    .chart-card h3 {
        margin-bottom: 15px;
        color: #002366;
        font-size: 1.1rem;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }
    .canvas-container {
        position: relative;
        flex-grow: 1;
        min-height: 250px;
    }
    /* Estilos para la tabla pequeña de stock */
    .mini-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .mini-table th, .mini-table td {
        padding: 8px;
        border-bottom: 1px solid #eee;
        text-align: left;
    }
    .mini-table th { background-color: #f8f9fa; color: #555; }
    .badge-danger {
        background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;
    }
    .badge-warning {
        background-color: #ffc107; color: #333; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;
    }
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

        <div class="chart-card">
            <h3>⚠️ Alerta: Stock Bajo (Menos de 5 un.)</h3>
            <div style="overflow-y: auto; max-height: 250px;">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody id="tablaStockBajo">
                        </tbody>
                </table>
            </div>
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
            llenarTablaStock(data.stock_bajo);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('loading').innerHTML = "<span style='color:red'>Error cargando datos.</span>";
        });
});

function llenarTablaStock(data) {
    const tbody = document.getElementById('tablaStockBajo');
    if (data.length === 0) {
        tbody.innerHTML = "<tr><td colspan='3' class='text-center'>¡Todo en orden! No hay stock bajo.</td></tr>";
        return;
    }
    
    let html = '';
    data.forEach(item => {
        const claseBadge = item.stock == 0 ? 'badge-danger' : 'badge-warning';
        html += `
            <tr>
                <td>${item.nombre} <br><small class="text-muted">${item.codigo}</small></td>
                <td>${item.tipo}</td>
                <td><span class="${claseBadge}">${item.stock}</span></td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function inicializarGraficos(data) {
    // 1. FINANZAS (Línea)
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

    // 2. SERVICIOS (Barras)
    new Chart(document.getElementById('serviciosChart'), {
        type: 'bar',
        data: {
            labels: data.servicios.map(d => d.mes),
            datasets: [{ label: 'Servicios', data: data.servicios.map(d => d.total), backgroundColor: '#3b82f6' }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: {
                y: { ticks: { stepSize: 1 } } // Para que no salgan decimales en conteo de servicios (ej: 1.5 servicios)
            }
        }
    });

    // 3. TOP PRODUCTOS (Barras Horizontales)
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
        options: { 
            indexAxis: 'y', 
            responsive: true, 
            maintainAspectRatio: false,
            scales: { x: { ticks: { stepSize: 1 } } }
        }
    });

    // 4. DISTRIBUCIÓN (Dona)
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

    // Leyenda HTML Distribución (CORREGIDO A DÓLARES $)
    const leyenda = document.getElementById('leyendaDistribucion');
    let htmlLeyenda = '<ul style="list-style:none; padding:0;">';
    
    // Formateador de moneda en Dólares
    const formatoMoneda = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

    data.distribucion_ventas.forEach((item, i) => {
        const pct = totalDist > 0 ? ((item.total / totalDist) * 100).toFixed(1) : 0;
        const color = colores[i % colores.length];
        
        // Aquí aplicamos el formato $
        const monto = formatoMoneda.format(item.total); 

        htmlLeyenda += `<li style="display:flex; justify-content:space-between; margin-bottom:5px; border-bottom:1px solid #eee;">
            <span><span style="display:inline-block; width:10px; height:10px; background:${color}; border-radius:50%; margin-right:5px;"></span>${item.tipo}</span>
            <span>${monto} <small class="text-muted">(${pct}%)</small></span>
        </li>`;
    });
    leyenda.innerHTML = htmlLeyenda + '</ul>';
}
</script>