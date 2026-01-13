document.addEventListener("DOMContentLoaded", function() {
    
    // Logica de intensidad
    const btnMinus = document.getElementById('btn-minus');
    const btnPlus = document.getElementById('btn-plus');
    const inputIntensidad = document.getElementById('input-intensidad');

    if(btnMinus && btnPlus && inputIntensidad) {
        btnMinus.addEventListener('click', () => {
            let val = parseInt(inputIntensidad.value) || 1;
            if (val > 1) inputIntensidad.value = val - 1;
        });

        btnPlus.addEventListener('click', () => {
            let val = parseInt(inputIntensidad.value) || 1;
            if (val < 5) inputIntensidad.value = val + 1;
        });
    }

    // Descripcion dinamica de categorias
    const descBox = document.getElementById('categoria-descripcion');
    document.querySelectorAll('.chk-categoria').forEach(chk => {
        chk.addEventListener('mouseover', function() {
            const desc = this.dataset.desc || "Sin descripción disponible";
            descBox.textContent = desc;
        });
        chk.addEventListener('change', function() {
            if(this.checked) {
                descBox.textContent = this.dataset.desc;
                descBox.classList.add('fw-bold');
            } else {
                descBox.classList.remove('fw-bold');
            }
        });
    });

    // Logica de alumnos
    
    // Variables
    const modalAlumnosEl = document.getElementById("modalAlumnos");
    const tablaAlumnosModal = document.getElementById("tabla-alumnos-modal"); // Tbody del modal
    const inputBuscar = document.getElementById("busquedaAlumno");
    const btnGuardarSeleccion = document.getElementById("guardarAlumnosSeleccionados");
    
    // Contenedor en el formulario principal
    const wrapperAlumnosForm = document.getElementById("alumnos-rows-wrapper");
    const emptyState = document.getElementById("empty-state-alumnos");
    
    // Map para mantener estado temporal en el modal
    let mapAlumnosSeleccionados = new Map();

    // Inicializar el Map con lo que ya hay en el formulario
    // Dentro de la funcion syncMapFromForm()
    function syncMapFromForm() {
        mapAlumnosSeleccionados.clear();
        const rows = wrapperAlumnosForm.querySelectorAll('.alumno-row');
        rows.forEach(row => {
            const idInput = row.querySelector('input[name="alumnos_ids[]"]');
            if (idInput) {
                const id = idInput.value;
                const nombre = row.querySelector('.fw-bold').innerText;
                const grupoTxt = row.querySelector('small').innerText; 
                const rolElement = row.querySelector('select[name^="roles"]');
                // Si encuentra el elemento usa su valor y en caso contrario usa por defecto
                const rol = rolElement ? rolElement.value : 'Agresor';
                
                mapAlumnosSeleccionados.set(id, { 
                    nombre: nombre, 
                    grupo: grupoTxt,
                    rol: rol 
                });
            }
        });
    }

    if(modalAlumnosEl){
        modalAlumnosEl.addEventListener('show.bs.modal', () => {
            syncMapFromForm(); // Sincronizar antes de abrir
            cargarAlumnosEnModal(); // Funcion ajax para buscar/listar
        });
        
        // Limpiar busqueda al cerrar
        modalAlumnosEl.addEventListener('hidden.bs.modal', () => {
            if(inputBuscar) inputBuscar.value = '';
        });
    }

    // Buscar Alumnos ssando AJAX
    if(inputBuscar) {
        inputBuscar.addEventListener('keyup', (e) => {
             cargarAlumnosEnModal();
        });
    }

    function cargarAlumnosEnModal() {
    const filtro = inputBuscar ? inputBuscar.value : '';
    const selectFiltroGrupo = document.getElementById("filtroGrupo");
    const grupo = selectFiltroGrupo ? selectFiltroGrupo.value : '';
    
    if (tablaAlumnosModal) tablaAlumnosModal.innerHTML = "<tr><td colspan='4' class='text-center text-muted'>Buscando alumnos...</td></tr>";

    fetch("alumnos_search.php", {
        method: "POST", 
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ filtro: filtro, grupo: grupo })
    })
    .then(r => r.json())
    .then(data => {
        if(!tablaAlumnosModal) return;

        tablaAlumnosModal.innerHTML = "";
        
        if(data.length === 0) {
             tablaAlumnosModal.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No se encontraron alumnos</td></tr>';
             return;
        }

        data.forEach(alumno => {
            const id = String(alumno.id);
            const nombreCompleto = alumno.nombre_completo || `${alumno.nombre} ${alumno.apellidos}`;
            const apellidos = alumno.apellidos || '';
            const gradoGrupo = alumno.grado_grupo || 'N/A';
            const isSelected = mapAlumnosSeleccionados.has(id);
            const tr = document.createElement("tr");
            if(isSelected) tr.classList.add("fila-seleccionada");
            
            tr.innerHTML = `
                <td class="text-center">
                    <input class="form-check-input chk-alumno-modal" type="checkbox" value="${id}" 
                        data-nombre="${nombreCompleto}" data-grupo="${gradoGrupo}"
                        ${isSelected ? "checked" : ""}>
                </td>
                <td>${alumno.nombre}</td>
                <td>${apellidos}</td> 
                <td><span class="badge bg-light text-dark border">${gradoGrupo}</span></td>
            `;
            
            // Click en fila activa checkbox
            tr.addEventListener('click', (e) => {
                if(e.target.type !== 'checkbox') {
                    const chk = tr.querySelector('.chk-alumno-modal');
                    chk.checked = !chk.checked;
                    chk.dispatchEvent(new Event('change'));
                }
            });

            // Listener del checkbox
            tr.querySelector('.chk-alumno-modal').addEventListener('change', function() {
                if(this.checked) {
                    const storedData = mapAlumnosSeleccionados.get(this.value);
                    const rolToUse = (storedData && storedData.rol) ? storedData.rol : 'Agresor'; 

                    mapAlumnosSeleccionados.set(this.value, { 
                        nombre: this.dataset.nombre, 
                        grupo: this.dataset.grupo,
                        rol: rolToUse
                    });
                    tr.classList.add("fila-seleccionada");
                } else {
                    mapAlumnosSeleccionados.delete(this.value);
                    tr.classList.remove("fila-seleccionada");
                }
            });

            tablaAlumnosModal.appendChild(tr);
        });
    })
    .catch(err => {
        console.error("Error cargando alumnos:", err);
        if(tablaAlumnosModal) tablaAlumnosModal.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar datos.</td></tr>';
    });
    }

    // Guardar seleccion del modal al formulario principal
    if(btnGuardarSeleccion) {
        btnGuardarSeleccion.addEventListener('click', () => {
            renderizarAlumnosEnFormulario();
            const modalInstance = bootstrap.Modal.getInstance(modalAlumnosEl);
            modalInstance.hide();
        });
    }

    function renderizarAlumnosEnFormulario() {
        wrapperAlumnosForm.innerHTML = "";
        
        if(mapAlumnosSeleccionados.size === 0) {
            emptyState.classList.remove('d-none');
        } else {
            emptyState.classList.add('d-none');
            
            mapAlumnosSeleccionados.forEach((datos, id) => {
                const div = document.createElement('div');
                div.className = "alumno-row d-flex align-items-center flex-wrap gap-2 animate__animated animate__fadeIn";
                div.id = `row-alumno-${id}`;
                
                div.innerHTML = `
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark">${datos.nombre}</div>
                        <small class="text-muted"><i class="bi bi-mortarboard-fill me-1"></i>${datos.grupo}</small>
                        <input type="hidden" name="alumnos_ids[]" value="${id}">
                    </div>

                    <div class="me-2" style="width: 200px;">
                        <select name="roles[${id}]" class="form-select form-select-sm border-primary" required>
                            <option value="Agresor" ${datos.rol === 'Agresor' ? 'selected' : ''}>Rol: Agresor</option>
                            <option value="Victima" ${datos.rol === 'Victima' ? 'selected' : ''}>Rol: Victima</option>
                            <option value="Participante" ${datos.rol === 'Participante' ? 'selected' : ''}>Rol: Participante</option>
                            <option value="Testigo" ${datos.rol === 'Testigo' ? 'selected' : ''}>Rol: Testigo</option>
                        </select>
                    </div>

                    
                `;

        

                wrapperAlumnosForm.appendChild(div);
            });
        }
    }

    // Inicializar bootstrap
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (wrapperAlumnosForm.children.length === 0) {
                event.preventDefault();
                event.stopPropagation();
                document.getElementById('error-alumnos').innerText = "Debe seleccionar al menos un alumno.";
                alert("Por favor selecciona al menos un alumno.");
            }
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false)
    });
});