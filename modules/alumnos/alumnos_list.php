<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid px-4 mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Directorio de Alumnos</h2>
            <p class="text-muted small mb-0">Gestión académica y monitoreo de riesgo.</p>
        </div>
        <div>
            <a href="alumnos_form.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-user-plus me-1"></i> Nuevo Alumno
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-3 bg-light rounded-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="filtroBusqueda" class="form-control border-start-0" placeholder="Buscar por nombre o apellidos...">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <select id="filtroGrupo" class="form-select">
                        <option value="">Grupo: Todos</option>
                        <option value="A">Grupo A</option>
                        <option value="B">Grupo B</option>
                        <option value="C">Grupo C</option>
                        <option value="D">Grupo D</option>
                        <option value="E">Grupo E</option>
                        <option value="F">Grupo F</option>
                    </select>
                </div>

                <div class="col-md-4 text-end">
                    <div id="loadingIndicator" class="spinner-border spinner-border-sm text-primary d-none" role="status"></div>
                    <span class="text-muted small ms-2 d-none d-md-inline">Resultados en tiempo real</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-4 sortable" onclick="cambiarOrden('nombre')" style="cursor: pointer;">
                            Nombre Completo <i class="fas fa-sort ms-1" id="icon-nombre"></i>
                        </th>
                        <th class="sortable" onclick="cambiarOrden('grado')" style="cursor: pointer;">
                            Ubicación <i class="fas fa-sort ms-1" id="icon-grado"></i>
                        </th>
                        <th>Estado Académico</th>
                        <th class="sortable" onclick="cambiarOrden('probabilidad_ia_cache')" style="cursor: pointer;">
                            Riesgo IA <i class="fas fa-sort ms-1" id="icon-probabilidad_ia_cache"></i>
                        </th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAlumnos">
                    </tbody>
            </table>
        </div>
    </div>

    <div id="emptyState" class="text-center py-5 d-none">
        <h5 class="text-muted">No se encontraron alumnos</h5>
        <p class="text-secondary small">Intenta con otro criterio de búsqueda.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables de estado
    let currentSortCol = 'apellido_paterno';
    let currentSortOrder = 'ASC';

    const filtroBusqueda = document.getElementById('filtroBusqueda');
    const filtroGrupo = document.getElementById('filtroGrupo');
    const tabla = document.getElementById('tablaAlumnos');
    const loader = document.getElementById('loadingIndicator');
    const emptyState = document.getElementById('emptyState');

    window.cambiarOrden = function(columna) {
        if (currentSortCol === columna) {
            currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
        } else {
            currentSortCol = columna;
            currentSortOrder = 'ASC';
        }
        
        actualizarIconosOrden(columna, currentSortOrder);
        cargarAlumnos();
    };

    function actualizarIconosOrden(columna, orden) {
        document.querySelectorAll('.fa-sort, .fa-sort-up, .fa-sort-down').forEach(icon => {
            icon.className = 'fas fa-sort ms-1 text-muted';
        });
        const activeIcon = document.getElementById('icon-' + columna);
        if (activeIcon) {
            activeIcon.className = (orden === 'ASC') ? 'fas fa-sort-up ms-1 text-primary' : 'fas fa-sort-down ms-1 text-primary';
        }
    }

    function getRiesgoBadge(riesgo, probabilidad) {
        if (!riesgo) return '<span class="badge bg-light text-muted border">Pendiente</span>';
        
        let clase = 'bg-secondary';
        let icono = '';

        if (riesgo.includes('TDAH')) {
            clase = 'bg-warning text-dark';
            icono = '<i class="fas fa-bolt small me-1"></i>';
        } else if (riesgo.includes('TND') || riesgo.includes('TD')) {
            clase = 'bg-danger';
            icono = '<i class="fas fa-exclamation-triangle small me-1"></i>';
        } else if (riesgo.includes('Sin')) {
            clase = 'bg-success';
            icono = '<i class="fas fa-check small me-1"></i>';
        }

        const probText = (probabilidad && probabilidad > 0) ? `(${Math.round(probabilidad)}%)` : '';
        return `<span class="badge ${clase}">${icono}${riesgo} ${probText}</span>`;
    }

    function cargarAlumnos() {
        loader.classList.remove('d-none');
        
        // nombres de parametros de api_master_alumnos
        const params = new URLSearchParams({
            q: filtroBusqueda.value,
            grupo: filtroGrupo.value,
            sort: currentSortCol,
            order: currentSortOrder
        });

        fetch(`../../api/api_master_alumnos.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                tabla.innerHTML = '';
                
                if (data.length === 0) {
                    emptyState.classList.remove('d-none');
                    return;
                }
                emptyState.classList.add('d-none');

                data.forEach(alumno => {
                    let fechaUpdate = '';
                    if (alumno.fecha_cache_actualizacion) {
                        const dateObj = new Date(alumno.fecha_cache_actualizacion);
                        fechaUpdate = `<small class="text-muted d-block" style="font-size:0.7em">Act: ${dateObj.toLocaleDateString()}</small>`;
                    }

                    const html = `
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">${alumno.nombre} ${alumno.apellido_paterno} ${alumno.apellido_materno || ''}</div>
                                <small class="text-muted">ID: ${alumno.id}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    ${alumno.grado}° "${alumno.grupo}"
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info px-2">
                                    ${alumno.estado}
                                </span>
                            </td>
                            <td>
                                ${getRiesgoBadge(alumno.riesgo_ia_cache, alumno.probabilidad_ia_cache)}
                                ${fechaUpdate}
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="alumnos_form.php?id=${alumno.id}" class="btn btn-sm btn-outline-secondary" title="Editar Información">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../alertas/alertas_form.php?alumno_id=${alumno.id}" class="btn btn-sm btn-outline-danger" title="Crear Alerta de Riesgo">
                                        <i class="fas fa-bell"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                    tabla.insertAdjacentHTML('beforeend', html);
                });
            })
            .catch(err => console.error(err))
            .finally(() => {
                loader.classList.add('d-none');
            });
    }

    // Event listeners
    let timeout = null;
    filtroBusqueda.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(cargarAlumnos, 300);
    });

    filtroGrupo.addEventListener('change', cargarAlumnos);

    // Cargar inicial
    cargarAlumnos();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>