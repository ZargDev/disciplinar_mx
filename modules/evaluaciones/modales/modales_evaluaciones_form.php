<div class="modal fade" id="modalAlumnos" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Seleccionar Alumnos</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-7">
            <input type="text" id="busquedaAlumno" class="form-control" placeholder="Buscar por nombre...">
          </div>
          <div class="col-md-5">
            <select id="filtroGrupo" class="form-select">
              <option value="">Todos los grupos</option>
              <?php
              foreach ($listaGrupos as $g) {
                echo "<option value=\"" . htmlspecialchars($g['grado_grupo']) . "\">" . htmlspecialchars($g['grado_grupo']) . "</option>";
              }
              ?>
            </select>
          </div>
        </div>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover table-sm">
              <thead class="table-light sticky-top"><tr><th>✓</th><th>Nombre</th><th>Apellidos</th><th>Grupo</th></tr></thead>
              <tbody id="tabla-alumnos-modal"></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="guardarAlumnosSeleccionados" class="btn btn-primary">Aplicar Selección</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalObservaciones" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-dark">
        <h5 class="modal-title">Códigos de Conducta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
        <?php foreach ($listaTiposConducta as $o): 
            $isChecked = in_array($o['id'], $obsSel) ? 'checked' : '';
        ?>
          <div class="form-check p-2 border-bottom">
            <input class="form-check-input chk-obs" type="checkbox" value="<?= $o['id'] ?>"
              data-nombre="<?= htmlspecialchars($o['nombre_tipo']) ?>" id="obs<?= $o['id'] ?>" <?= $isChecked ?>>
            <label class="form-check-label w-100" for="obs<?= $o['id'] ?>" style="cursor:pointer;">
                <?= htmlspecialchars($o['nombre_tipo']) ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" id="guardarObservaciones" class="btn btn-primary">Aplicar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalNuevaMateria" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nueva Materia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="text" id="nuevaMateriaNombre" class="form-control" placeholder="Nombre de materia">
      </div>
      <div class="modal-footer">
        <button type="button" id="btnGuardarNuevaMateria" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalConfirm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white"><h5 class="modal-title">Confirmar Envío</h5><button type="button" class="btn-close btn-close-with" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><p>Se guardará la evaluación y se notificará a los tutores. ¿Continuar?</p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmSend" class="btn btn-primary">Enviar</button>
      </div>
    </div>
  </div>
</div>