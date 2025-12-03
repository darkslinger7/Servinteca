document.addEventListener('DOMContentLoaded', function() {
   
    const buscador = document.getElementById('buscar-empresa');
    if (buscador) {
        buscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('table tbody tr');
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                fila.style.display = textoFila.includes(termino) ? '' : 'none';
            });
        });
    }

   
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Está seguro de eliminar esta empresa?')) {
                window.location.href = `eliminar.php?id=${id}`;
            }
        });
    });

    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    });
});
document.addEventListener('DOMContentLoaded', function() {
   
    const buscador = document.getElementById('buscar-empresa');
    if (buscador) {
        buscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('table tbody tr');
            
            filas.forEach(fila => {
                const textoFila = fila.textContent.toLowerCase();
                fila.style.display = textoFila.includes(termino) ? '' : 'none';
            });
        });
    }

   
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (confirm('¿Está seguro de eliminar esta empresa?')) {
                window.location.href = `eliminar.php?id=${id}`;
            }
        });
    });

    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.opacity = '0';
            setTimeout(() => alerta.remove(), 300);
        }, 5000);
    });
});