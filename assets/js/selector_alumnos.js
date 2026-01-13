const SelectorAlumnos = {
    state: {
        page: 1,
        totalPages: 1,
        sortCol: 'apellido_paterno',
        sortOrder: 'ASC',
        callback: null
    },

    init: function() {
        fetch('../../api/api_filtros_dinamicos.php?accion=get_grados')
            .then(r => r.json())
            .then(grados => {
                const sel = document.getElementById('gs_filtroGrado');
                if(sel) {
                    grados.forEach(g => {
                        sel.innerHTML += `<option value="${g}">${g}°</option>`;
                    });
                }
            });

        const buscador = document.getElementById('gs_buscador');
        const fGrado = document.getElementById('gs_filtroGrado');
        const fGrupo = document.getElementById('gs_filtroGrupo');
        const fRiesgo = document.getElementById('gs_filtroRiesgo');
        let timeout;

        if(buscador) {
            buscador.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => { this.state.page = 1; this.cargar(); }, 300);
            });
        }

        if(fGrado) {
            fGrado.addEventListener('change', () => {
                const gradoVal = fGrado.value;
                fGrupo.innerHTML = '<option value="">Todos</option>';
                
                if(gradoVal) {
                    fGrupo.disabled = false;
                    fetch(`../../api/api_filtros_dinamicos.php?accion=get_grupos&grado=${gradoVal}`)
                        .then(r => r.json())
                        .then(grupos => {
                            grupos.forEach(g => {
                                fGrupo.innerHTML += `<option value="${g}">${g}</option>`;
                            });
                        });
                } else {
                    fGrupo.disabled = true;
                }
                this.state.page = 1;
                this.cargar();
            });
        }

        if(fGrupo) fGrupo.addEventListener('change', () => { this.state.page = 1; this.cargar(); });
        if(fRiesgo) fRiesgo.addEventListener('change', () => { this.state.page = 1; this.cargar(); });
    },

    abrir: function(funcionAlSeleccionar, textoInstruccion = "Selecciona un alumno para continuar.") {
        this.state.callback = funcionAlSeleccionar;
        
        const txtEl = document.getElementById('gs_instruccion');
        if(txtEl) txtEl.innerText = textoInstruccion;
        
        const modalElement = document.getElementById('modalSelectorAlumnos');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        this.cargar();
    },

    // Carga de datos
    cargar: function() {
        const tbody = document.getElementById('gs_tbody');
        const q = document.getElementById('gs_buscador').value;
        const g = document.getElementById('gs_filtroGrupo').value;
        const riesgo = document.getElementById('gs_filtroRiesgo') ? document.getElementById('gs_filtroRiesgo').value : '';
        
        tbody.style.opacity = '0.5'; 

        const url = `../../api/api_master_alumnos.php?page=${this.state.page}&q=${q}&grupo=${g}&riesgo=${riesgo}&sort=${this.state.sortCol}&order=${this.state.sortOrder}`;

        fetch(url)
            .then(r => r.json())
            .then(resp => {
                tbody.innerHTML = ''; 
                tbody.style.opacity = '1';

                this.state.totalPages = resp.pages;
                this.renderTabla(resp.data);
                this.updateControles(resp.total);
            })
            .catch(e => {
                console.error(e);
                tbody.style.opacity = '1';
            });
        
        this.updateIconos();
    },

    // Renderizado de la tabla
    renderTabla: function(data) {
        const tbody = document.getElementById('gs_tbody');
        
        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-5 text-muted">No se encontraron resultados.</td></tr>';
            return;
        }

        data.forEach(alum => {
            const tr = document.createElement('tr');
            
            // Calculo de probabilidad
            let probRaw = alum.probabilidad_ia_cache;
            let probabilidad = 0;
            
            // Aseguramos que sea numero
            if (probRaw !== null && probRaw !== undefined) {
                probabilidad = Math.round(parseFloat(probRaw));
            }

            let riesgoTexto = alum.riesgo_ia_cache || 'Pendiente';
            let colorClass = 'bg-secondary';
            let textClass = 'text-muted';
            
            // logic colores
            if (riesgoTexto.includes('TDAH')) {
                colorClass = 'bg-warning';      
                textClass = 'text-dark';
            } 
            else if (riesgoTexto.includes('TND') || riesgoTexto.includes('TD')) {
                colorClass = 'bg-danger';       
                textClass = 'text-danger';
            }
            else if (riesgoTexto.includes('Sin') || riesgoTexto.includes('Latente')) {
                colorClass = 'bg-success';
                textClass = 'text-success';
            }

            let riesgoHTML = `
                <div class="d-flex flex-column justify-content-center" style="min-width: 140px;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge ${colorClass} bg-opacity-25 ${textClass} border border-0" style="font-size: 0.7rem;">
                            ${riesgoTexto}
                        </span>
                        <span class="small fw-bold ${textClass}" style="font-size: 0.75rem;">${probabilidad}%</span>
                    </div>
                    <div class="progress" style="height: 4px; width: 100%; background-color: #e9ecef;">
                        <div class="progress-bar ${colorClass}" style="width: ${probabilidad}%"></div>
                    </div>
                </div>
            `;

            // HTML Fila
            tr.innerHTML = `
                <td class="text-center">
                    <span class="badge bg-light text-dark border fw-bold">${alum.grado}° ${alum.grupo}</span>
                </td>
                <td class="ps-3">
                    <div class="fw-bold text-dark text-truncate">${alum.nombre} ${alum.apellido_paterno}</div>
                </td>
                <td class="pe-3 py-2">
                    ${riesgoHTML}
                </td>
            `;
            
            tr.onclick = () => {
                if (this.state.callback) {
                    this.state.callback(alum);
                    const modalEl = document.getElementById('modalSelectorAlumnos');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                }
            };

            tbody.appendChild(tr);
        });
    },

    ordenar: function(col) {
        if(this.state.sortCol === col) {
            this.state.sortOrder = (this.state.sortOrder === 'ASC') ? 'DESC' : 'ASC';
        } else {
            this.state.sortCol = col;
            this.state.sortOrder = 'ASC';
        }
        this.cargar();
    },

    updateIconos: function() {
        ['nombre', 'grado', 'riesgo_ia_cache'].forEach(id => {
            const el = document.getElementById(`gs_icon_${id}`);
            if(el) el.className = 'fas fa-sort ms-1 small text-muted';
        });
        const icon = document.getElementById(`gs_icon_${this.state.sortCol}`);
        if(icon) {
            icon.className = `fas fa-sort-${this.state.sortOrder === 'ASC' ? 'up' : 'down'} ms-1 small text-primary`;
        }
    },

    updateControles: function(total) {
        const elInfo = document.getElementById('gs_infoPaginacion');
        if(elInfo) elInfo.innerText = `Página ${this.state.page} de ${this.state.totalPages} (${total})`;
        
        const btnPrev = document.getElementById('gs_btnPrev');
        const btnNext = document.getElementById('gs_btnNext');
        if(btnPrev) btnPrev.disabled = (this.state.page <= 1);
        if(btnNext) btnNext.disabled = (this.state.page >= this.state.totalPages);
    },

    nextPage: function() {
        if (this.state.page < this.state.totalPages) {
            this.state.page++;
            this.cargar();
        }
    },

    prevPage: function() {
        if (this.state.page > 1) {
            this.state.page--;
            this.cargar();
        }
    }
};

document.addEventListener('DOMContentLoaded', () => SelectorAlumnos.init());