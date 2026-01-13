<div class="modal fade" id="modalSelectorAlumnos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            
            <div class="modal-header border-bottom-0 pb-0 pt-3 px-3">
                <h5 class="modal-title fw-bold text-dark fs-5"><i class="fas fa-user-graduate me-2 text-primary"></i>Buscar Alumno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="px-3 pb-3 pt-2">
                <div class="bg-light p-3 rounded-3 border">
                    
                    <div class="mb-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-secondary ps-3">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="gs_buscador" class="form-control border-start-0 ps-2" placeholder="Escribe el nombre del alumno..." autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-secondary fw-bold border-end-0">Grado</span>
                                <select id="gs_filtroGrado" class="form-select border-start-0 ps-1 bg-white">
                                    <option value="">Todos</option>
                                    </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-secondary fw-bold border-end-0">Grupo</span>
                                <select id="gs_filtroGrupo" class="form-select border-start-0 ps-1 bg-white" disabled>
                                    <option value="">Todos</option>
                                    </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-secondary fw-bold border-end-0">Riesgo IA</span>
                                <select id="gs_filtroRiesgo" class="form-select border-start-0 ps-1 bg-white">
                                    <option value="">Cualquiera</option>
                                    <option value="ConRiesgo">Riesgo Detectado</option>
                                    <option value="Riesgo TDAH">Riesgo TDAH</option>
                                    <option value="Riesgo TND">Riesgo TND</option>
                                    <option value="Riesgo TD">Riesgo TD</option>
                                    <option value="Sin Riesgo">Sin Riesgo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-body p-0 border-top" style="height: 450px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle" id="gs_tabla" style="table-layout: fixed; width: 100%;">
                        <thead class="bg-white text-secondary small text-uppercase sticky-top shadow-sm">
                            <tr>
                                <th style="width: 15%; text-align: center;" class="sortable user-select-none py-2" onclick="SelectorAlumnos.ordenar('grado')">
                                    Grupo <i class="fas fa-sort ms-1 small" id="gs_icon_grado"></i>
                                </th>
                                <th style="width: 50%;" class="ps-3 sortable user-select-none py-2" onclick="SelectorAlumnos.ordenar('nombre')">
                                    Nombre del Alumno <i class="fas fa-sort ms-1 small" id="gs_icon_nombre"></i>
                                </th>
                                <th style="width: 35%;" class="sortable user-select-none py-2" onclick="SelectorAlumnos.ordenar('riesgo_ia_cache')">
                                    Diagnóstico IA <i class="fas fa-sort ms-1 small" id="gs_icon_riesgo_ia_cache"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="gs_tbody">
                            </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light flex-column align-items-stretch py-2 border-top-0">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <span class="text-muted small ms-1" id="gs_infoPaginacion">Cargando...</span>
                    
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-white border btn-sm px-3 text-secondary" onclick="SelectorAlumnos.prevPage()" id="gs_btnPrev">
                            <i class="fas fa-chevron-left me-1"></i> Anterior
                        </button>
                        <button class="btn btn-white border btn-sm px-3 text-secondary" onclick="SelectorAlumnos.nextPage()" id="gs_btnNext">
                            Siguiente <i class="fas fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Estilos para modal */
.user-select-none { user-select: none; }
.sortable { cursor: pointer; transition: color 0.2s; }
.sortable:hover { color: var(--primary, #0d6efd); }

.input-group-text.bg-white { border-right: none; }
.form-control.border-start-0:focus, .form-select.border-start-0:focus { 
    box-shadow: none; 
    border-color: #ced4da; 
}

/* Hover Fila */
#gs_tbody tr { cursor: pointer; transition: background 0.1s; }
#gs_tbody tr:hover { background-color: rgba(13, 110, 253, 0.08); } 

.btn-white { background-color: white; }
.btn-white:hover { background-color: #f8f9fa; }
</style>