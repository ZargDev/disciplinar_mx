<div class="modal fade" id="modalReporte" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Vista Previa de la Evaluacion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body bg-light">
        <div id="contenidoReporte" class="bg-white p-4 shadow-sm" style="min-height:300px;">Cargando...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEnviarCorreo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">Confirmar Envío de Reporte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Se enviará el reporte a los siguientes tutores:</p>
        <div id="listaTutores" class="alert alert-secondary py-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="btnConfirmarEnvio" class="btn btn-warning fw-bold">Enviar Correo</button>
      </div>
    </div>
  </div>
</div>