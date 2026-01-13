<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/modal_alumno_selector.php';
?>

<div class="container-fluid px-4 mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="../riesgo_predictivo/dashboard.php" class="btn btn-outline-secondary btn-sm" title="Volver al Dashboard">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="fw-bold text-dark mb-0">Gestión de Alertas</h2>
                <p class="text-muted small mb-0">Monitoreo y seguimiento de casos activos.</p>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" onclick="abrirBuscadorAlertas()">
                <i class="fas fa-plus-circle me-2"></i> Nueva Alerta Manual
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-3 bg-light rounded-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-secondary ps-3"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroTexto" class="form-control border-start-0 ps-2" placeholder="Buscar por alumno o título..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-secondary fw-bold border-end-0">Estado</span>
                        <select id="filtroEstado" class="form-select border-start-0 ps-1 bg-white">
                            <option value="Todos">Todos</option>
                            <option value="Abierta" selected>Abierta</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Cerrada">Cerrada</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-secondary fw-bold border-end-0">Prioridad</span>
                        <select id="filtroPrioridad" class="form-select border-start-0 ps-1 bg-white">
                            <option value="Todas">Todas</option>
                            <option value="Critica">Crítica</option>
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1 text-end">
                    <div id="loadingIndicator" class="spinner-border spinner-border-sm text-primary d-none" role="status"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th style="width: 25%;" class="ps-4 cursor-pointer user-select-none" onclick="cambiarOrden('alumno')">
                            Alumno <i class="fas fa-sort ms-1 text-muted" id="sort-alumno"></i>
                        </th>
                        <th style="width: 25%;" class="user-select-none">Alerta</th>
                        <th style="width: 10%; text-align: center;" class="ps-4 cursor-pointer user-select-none" onclick="cambiarOrden('prioridad')">
                            Prioridad <i class="fas fa-sort ms-1 text-muted" id="sort-prioridad"></i>
                        </th>
                        <th style="width: 10%; text-align: center;" class="ps-4 cursor-pointer user-select-none" onclick="cambiarOrden('estado')">
                            Estado <i class="fas fa-sort ms-1 text-muted" id="sort-estado"></i>
                        </th>
                        <th style="width: 10%; text-align: center;" class="cursor-pointer user-select-none" onclick="cambiarOrden('riesgo')">
                            Riesgo IA <i class="fas fa-sort ms-1 text-muted" id="sort-riesgo"></i>
                        </th>
                        <th style="width: 10%; text-align: right;" class="ps-4 cursor-pointer user-select-none" onclick="cambiarOrden('fecha')">
                            Fecha <i class="fas fa-sort ms-1 text-muted" id="sort-fecha"></i>
                        </th>
                        <th style="width: 5%;"></th> 
                    </tr>
                </thead>
                <tbody id="tablaAlertas"></tbody>
            </table>
        </div>

        <div class="card-footer bg-white py-3 border-top-0 d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="infoPaginacion">Cargando...</span>
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm px-3" id="btnPrev" onclick="cambiarPagina(-1)">
                    <i class="fas fa-chevron-left me-1"></i> Anterior
                </button>
                <button class="btn btn-outline-secondary btn-sm px-3" id="btnNext" onclick="cambiarPagina(1)">
                    Siguiente <i class="fas fa-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div id="emptyState" class="text-center py-5 d-none">
        <h5 class="text-muted">No se encontraron alertas con estos filtros</h5>
    </div>
</div>

<script src="../../assets/js/selector_alumnos.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Estado Global
    let currentPage = 1;
    let totalPages = 1;
    let currentSortCol = 'fecha';
    let currentSortOrder = 'DESC';

    // Referencias DOM
    const filtroTexto = document.getElementById('filtroTexto');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroPrioridad = document.getElementById('filtroPrioridad');
    const tabla = document.getElementById('tablaAlertas');
    const loader = document.getElementById('loadingIndicator');
    const emptyState = document.getElementById('emptyState');
    
    // Controles Paginación
    const infoPag = document.getElementById('infoPaginacion');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');

    // Funciones Helper
    function getBadgeClass(tipo, valor) {
        if (tipo === 'prioridad') {
            const map = { 'Critica': 'bg-danger', 'Alta': 'bg-warning text-dark', 'Media': 'bg-info text-dark', 'Baja': 'bg-secondary' };
            return map[valor] || 'bg-secondary';
        }
        if (tipo === 'estado') {
            const map = { 'Abierta': 'bg-danger', 'En Proceso': 'bg-primary', 'Cerrada': 'bg-success' };
            return map[valor] || 'bg-secondary';
        }
        return 'bg-secondary';
    }

    // Funciones
    window.cambiarOrden = function(col) {
        if(currentSortCol === col) {
            currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
        } else {
            currentSortCol = col;
            currentSortOrder = 'ASC';
        }
        actualizarIconosOrden(col, currentSortOrder);
        cargarAlertas();
    };

    window.cambiarPagina = function(direction) {
        const nuevaPagina = currentPage + direction;
        if(nuevaPagina > 0 && nuevaPagina <= totalPages) {
            currentPage = nuevaPagina;
            cargarAlertas();
        }
    };

    window.abrirBuscadorAlertas = function() {
        SelectorAlumnos.abrir(
            function(alumno) {
                window.location.href = `alertas_form.php?alumno_id=${alumno.id}&return=alertas_list.php`;
            }, 
            "Crear Alerta"
        );
    }

    function actualizarIconosOrden(col, orden) {
        document.querySelectorAll('.fa-sort, .fa-sort-up, .fa-sort-down').forEach(icon => {
            icon.className = 'fas fa-sort ms-1 text-muted';
        });
        const icon = document.getElementById('sort-' + col);
        if(icon) {
            icon.className = `fas fa-sort-${orden === 'ASC' ? 'up' : 'down'} ms-1 text-primary`;
        }
    }

    function cargarAlertas() {
        loader.classList.remove('d-none');
        
        const params = new URLSearchParams({
            page: currentPage,
            texto: filtroTexto.value,
            estado: filtroEstado.value,
            prioridad: filtroPrioridad.value,
            sort: currentSortCol,
            order: currentSortOrder
        });

        fetch(`api_alertas.php?${params.toString()}`)
            .then(response => response.json())
            .then(resp => {
                // Actualizar estado de paginación
                totalPages = resp.pages;
                infoPag.innerText = `Página ${resp.page} de ${resp.pages} (${resp.total} alertas)`;
                btnPrev.disabled = (resp.page <= 1);
                btnNext.disabled = (resp.page >= resp.pages);

                // Renderizar Tabla
                tabla.innerHTML = '';
                const data = resp.data;

                if (data.length === 0) {
                    emptyState.classList.remove('d-none');
                    return;
                }
                emptyState.classList.add('d-none');

                data.forEach(alerta => {
                    const html = `
                        <tr style="cursor: pointer;" onclick="window.location='alerta_detalle.php?id=${alerta.id}'">
                            <td class="ps-4">
                                <div class="fw-bold text-dark text-truncate">${alerta.nombre} ${alerta.apellido_paterno}</div>
                                <small class="text-muted">Grupo ${alerta.grupo}</small>
                            </td>
                            <td>
                                <div class="text-dark fw-medium text-truncate" title="${alerta.titulo}">${alerta.titulo}</div>
                            </td>
                            <td class="text-center"><span class="badge ${getBadgeClass('prioridad', alerta.prioridad)} rounded-pill px-3">${alerta.prioridad}</span></td>
                            <td class="text-center"><span class="badge ${getBadgeClass('estado', alerta.estado)}">${alerta.estado}</span></td>
                            <td class="text-center">
                                ${alerta.riesgo_detectado.includes('Sin') 
                                    // Si no hay riesgo, mostramos un badge verde suave
                                    ? '<span class="badge bg-success bg-opacity-10 text-success border border-success small">Sin Riesgo</span>' 
                                    // Si HAY riesgo, mostramos el nombre exacto (alerta.riesgo_detectado)
                                    : `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger small">${alerta.riesgo_detectado}</span>`
                                }
                            </td>
                            <td class="text-end text-muted small pe-3">${new Date(alerta.fecha_alerta).toLocaleDateString()}</td>
                            <td class="text-end pe-4 text-muted"><i class="fas fa-chevron-right"></i></td>
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

    // Event listeners (Resetean a pagina 1 al filtrar)
    let timeout = null;
    filtroTexto.addEventListener('input', () => { 
        clearTimeout(timeout); 
        timeout = setTimeout(() => { currentPage = 1; cargarAlertas(); }, 300); 
    });
    filtroEstado.addEventListener('change', () => { currentPage = 1; cargarAlertas(); });
    filtroPrioridad.addEventListener('change', () => { currentPage = 1; cargarAlertas(); });
    
    // Iniciar
    actualizarIconosOrden('fecha', 'DESC');
    cargarAlertas();
});
</script>

<style>
    .cursor-pointer { cursor: pointer; }
    .cursor-pointer:hover { color: var(--primary); }
    .input-group-text { font-size: 0.9rem; }
    .form-select { font-size: 0.9rem; }

    .user-select-none {
        -webkit-user-select: none; /* Safari */
        -moz-user-select: none;    /* Firefox */
        -ms-user-select: none;     /* IE/Edge */
        user-select: none;         /* Estándar */
    }

    .cursor-pointer { cursor: pointer; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>