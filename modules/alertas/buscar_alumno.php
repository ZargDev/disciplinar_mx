<?php require_once __DIR__ . '/../../config/db.php'; require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center mb-4">
            <h2 class="fw-bold">Nueva Alerta Manual</h2>
            <p class="text-muted">Busca al estudiante para iniciar el reporte</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="input-group input-group-lg mb-0">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                        <input type="text" id="buscador" class="form-control border-start-0" placeholder="Escribe el nombre del alumno..." autocomplete="off">
                    </div>
                </div>
                <div id="resultados" class="list-group list-group-flush border-top">
                    </div>
            </div>
            
            <div class="text-center mt-3 text-muted small">
                ¿No encuentras al alumno?
                <a href="alertas_list.php"><i class="fas fa-arrow-left"></i> Regresar</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('buscador').addEventListener('input', function(e) {
    const term = e.target.value;
    const lista = document.getElementById('resultados');
    
    if(term.length < 2) { lista.innerHTML = ''; return; }

    fetch(`api_buscar_alumnos.php?q=${term}`)
        .then(r => r.json())
        .then(data => {
            lista.innerHTML = '';
            if(data.length === 0) {
                lista.innerHTML = '<div class="p-3 text-center text-muted">No se encontraron resultados</div>';
                return;
            }
            
            data.forEach(alum => {
                const nombre = `${alum.nombre} ${alum.apellido_paterno} ${alum.apellido_materno}`;
                // Badges de riesgo
                let riesgoHtml = '';
                if(alum.riesgo_ia_cache && !alum.riesgo_ia_cache.includes('Sin')) {
                    riesgoHtml = `<span class="badge bg-danger bg-opacity-10 text-danger ms-2"><i class="fas fa-exclamation"></i> ${alum.riesgo_ia_cache}</span>`;
                }

                const item = `
                    <a href="alertas_form.php?alumno_id=${alum.id}&return=buscar_alumno.php" class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">${nombre}</h5>
                            <small class="text-muted">${alum.grado}° Grado, Grupo "${alum.grupo}"</small>
                            ${riesgoHtml}
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                `;
                lista.insertAdjacentHTML('beforeend', item);
            });
        });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>