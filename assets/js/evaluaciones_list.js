document.addEventListener("DOMContentLoaded", function() {
    // Inicialización de Modales (Se mantiene)
    const modalReporte = new bootstrap.Modal(document.getElementById('modalReporte'));
    const modalEnviar = new bootstrap.Modal(document.getElementById('modalEnviarCorreo'));
    const contenido = document.getElementById('contenidoReporte');
    const listaTutores = document.getElementById('listaTutores');
    const searchInput = document.getElementById('search-evaluaciones');
    const evaluacionesContainer = document.getElementById('evaluaciones-list-container');
    const allCards = document.querySelectorAll('.eval-card');
    let currentEvalId = null;
    let openCardId = null;

    // --- Logica de colapso de tarjetas ---
    allCards.forEach(card => {
        const cardBody = card.querySelector('.eval-card-body');
        const targetId = cardBody.dataset.bsTarget;
        const targetElement = document.querySelector(targetId);
        const bsCollapse = new bootstrap.Collapse(targetElement, { toggle: false });

        cardBody.addEventListener('click', function(e) {
            if (e.target.closest('.card-footer')) {
                return;
            }

            const currentId = card.dataset.id;
            
            // Si hay una tarjeta abierta diferente dbe cerrarla
            if (openCardId && openCardId !== currentId) {
                const prevTargetId = '#actions-' + openCardId;
                const prevTargetEl = document.querySelector(prevTargetId);
                if (prevTargetEl) {
                    bootstrap.Collapse.getInstance(prevTargetEl).hide();
                }
            }

            // Alternar la tarjeta actual
            bsCollapse.toggle();
            openCardId = (targetElement.classList.contains('collapsing') || targetElement.classList.contains('show')) ? null : currentId;
        });
        
        // Sincronizar openCardId despues de que las animaciones terminen
        targetElement.addEventListener('hidden.bs.collapse', () => {
            if (openCardId === targetElement.id.replace('actions-', '')) {
                openCardId = null;
            }
        });
        
        targetElement.addEventListener('shown.bs.collapse', () => {
            openCardId = targetElement.id.replace('actions-', '');
        });
    });

    // Logica del buscador
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();

            allCards.forEach(card => {
                const alumnosData = card.dataset.alumnos.toLowerCase();
                const fechaData = card.dataset.fecha;
                
                // Se busca en el campo de alumnos y fecha
                const matches = alumnosData.includes(searchTerm) || fechaData.includes(searchTerm);

                if (matches) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                    // Asegurar que la tarjeta oculta también se colapse
                    const collapseElement = card.querySelector('.collapse');
                    if(collapseElement && collapseElement.classList.contains('show')) {
                         bootstrap.Collapse.getInstance(collapseElement).hide();
                    }
                }
            });
        });
    }

    // Logica de Botones

    // Boton Ver / Visualizar
    document.querySelectorAll('.btn-ver').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            contenido.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-custom-primary"></div><p class="text-custom-primary mt-2">Cargando detalles de evaluación...</p></div>';
            modalReporte.show();

            fetch(`evaluaciones_get_details.php?id=${id}`)
                .then(r => r.text())
                .then(data => { contenido.innerHTML = data; })
                .catch(err => { contenido.innerHTML = "<div class='alert alert-danger'>Error al cargar datos. Asegúrate que 'evaluaciones_get_details.php' está disponible.</div>"; });
        });
    });

    // Boton Descargar PDF
    document.querySelectorAll('.btn-imprimir-directo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando...';
            btn.disabled = true;

            fetch(`evaluaciones_get_details.php?id=${id}`)
                .then(r => r.text())
                .then(data => {
                    const element = document.createElement('div');
                    element.innerHTML = data;
                    
                    const opt = {
                        margin: 1, 
                        filename: `Reporte_Evaluacion_${id}.pdf`,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2 },
                        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };

                    html2pdf().from(element).set(opt).save()
                    .finally(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });

                })
                .catch(err => {
                    alert("Error al generar el PDF. Revisa la consola para detalles.");
                    console.error("PDF Error:", err);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        });
    });
    
    // Boton Enviar Notificación
    document.querySelectorAll('.btn-enviar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentEvalId = this.dataset.id;
            listaTutores.innerHTML = '<span class="spinner-border spinner-border-sm text-custom-primary"></span> Buscando correos de tutores...';
            document.getElementById('btnConfirmarEnvio').disabled = true;
            modalEnviar.show();

            fetch(`evaluaciones_get_tutors.php?id=${currentEvalId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.length === 0) {
                        listaTutores.innerHTML = '<div class="alert alert-warning">No se encontraron tutores con correo para notificar o el alumno no tiene tutor principal asignado.</div>';
                    } else {
                        let html = '<ul class="mb-0 ps-3 list-unstyled">';
                        data.forEach(t => {
                            // Usar text-danger si no hay email o text-success si hay
                            const emailClass = t.email ? 'text-dark' : 'text-danger';
                            html += `<li class="mb-1"><strong>${t.alumno}:</strong> ${t.tutor} (<span class="${emailClass}">${t.email || 'Sin email'}</span>)</li>`;
                        });
                        html += '</ul>';
                        listaTutores.innerHTML = html;
                        document.getElementById('btnConfirmarEnvio').disabled = false;
                    }
                })
                .catch(err => {
                    listaTutores.innerHTML = '<div class="alert alert-danger">Error al buscar datos de tutores.</div>';
                });
        });
    });

    // Logica de confirmacion de envio
    document.getElementById('btnConfirmarEnvio').addEventListener('click', function() {
        const btn = this; 
        btn.disabled = true; 
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';

        fetch('evaluaciones_send_logic.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + currentEvalId
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success) {
                alert("Éxito: " + resp.message); 
            } else {
                alert("Error: " + (resp.message || "Fallo desconocido en el servidor.")); 
            }
            
            modalEnviar.hide();
        })
        .catch(err => {
            console.error("Fetch error:", err);
            alert("Error de conexión al intentar enviar el correo. Revisa la consola para detalles."); 
        })
        .finally(() => { 
            btn.innerHTML = "Enviar Correo";
            btn.disabled = false;
        });
    });
});