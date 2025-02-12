<?php
session_start();
require '../configuracion/base_datos.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

$cierre_id = (int)$_GET['id'];

// Obtener los datos del cierre de caja
$query = "SELECT * FROM cierre_caja WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(":id", $cierre_id, PDO::PARAM_INT);
$stmt->execute();
$cierre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cierre) {
    echo "Cierre de caja no encontrado.";
    exit();
}

// Usamos el apertura_id del cierre, ya que la sesión podría no tener el valor correcto
$apertura_id = $cierre['apertura_id'];

// Recuperar los movimientos asociados a la apertura de caja correspondiente
$queryMov = "SELECT * FROM movimientos_caja WHERE apertura_id = :apertura_id ORDER BY fecha_movimiento ASC";
$stmtMov = $pdo->prepare($queryMov);
$stmtMov->bindParam(":apertura_id", $apertura_id, PDO::PARAM_INT);
$stmtMov->execute();
$movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Cierre de Caja</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../publico/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Se incluye jQuery para facilitar la implementación AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
      /* Estilos básicos para las ventanas modales */
      .modal {
          display: none; 
          position: fixed;
          z-index: 9999;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          overflow: auto;
          background-color: rgba(0, 0, 0, 0.4);
      }
      .modal-content {
          background-color: #fefefe;
          margin: 5% auto;
          padding: 20px;
          border: 1px solid #888;
          width: 50%;
      }
      .close {
          color: #aaa;
          float: right;
          font-size: 28px;
          font-weight: bold;
      }
      .close:hover,
      .close:focus {
          color: black;
          text-decoration: none;
          cursor: pointer;
      }
    </style>
</head>
<body>
    <?php include "menu.php"; ?>
    <h1>Detalle del Cierre de Caja #<?= htmlspecialchars($cierre['id']) ?></h1>
    
    <!-- Botón para editar los detalles del cierre de caja -->
    <button onclick="abrirEditarCierreModal()">Editar Cierre</button>
    
    <h2>Información del Cierre</h2>
    <ul>
        <li><strong>Apertura ID:</strong> <?= htmlspecialchars($cierre['apertura_id']) ?></li>
        <li><strong>Total Ingresos:</strong> S/<span id="total_ingresos"><?= number_format($cierre['total_ingresos'], 2) ?></span></li>
        <li><strong>Total Egresos:</strong> S/<span id="total_egresos"><?= number_format($cierre['total_egresos'], 2) ?></span></li>
        <li><strong>Saldo Final:</strong> S/<span id="saldo_final"><?= number_format($cierre['saldo_final'], 2) ?></span></li>
        <li><strong>Total Ventas Diarias:</strong> S/<span id="total_ventas_diarias"><?= number_format($cierre['total_ventas_diarias'], 2) ?></span></li>
        <li><strong>Total Efectivo Caja:</strong> S/<span id="total_efectivo_caja"><?= number_format($cierre['total_efectivo_caja'], 2) ?></span></li>
        <li><strong>Total Calculado:</strong> S/<span id="total_calculado"><?= number_format($cierre['total_calculado'], 2) ?></span></li>
        <li><strong>Arqueo:</strong> S/<span id="arqueo"><?= number_format($cierre['arqueo'], 2) ?></span></li>
        <li><strong>Fecha de Cierre:</strong> <?= htmlspecialchars($cierre['fecha_cierre']) ?></li>
    </ul>
    
    <h2>Movimientos Asociados (Apertura ID: <?= htmlspecialchars($apertura_id) ?>)</h2>
    
    <!-- Botón para crear nuevo movimiento -->
    <button onclick="abrirModal('modalNuevoMovimiento')">Nuevo Movimiento</button>
    
    <?php if (count($movimientos) > 0): ?>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Método de Pago</th>
                <th>Método de Pago Destino</th>
                <th>Monto</th>
                <th>Descripción</th>
                <th>Fecha Movimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $mov): ?>
                <tr>
                    <td><?= htmlspecialchars($mov['id']) ?></td>
                    <td><?= htmlspecialchars($mov['tipo']) ?></td>
                    <td><?= htmlspecialchars($mov['metodo_pago']) ?></td>
                    <td><?= htmlspecialchars($mov['metodo_pago_destino']) ?></td>
                    <td><?= number_format($mov['monto'], 2) ?></td>
                    <td><?= htmlspecialchars($mov['descripcion']) ?></td>
                    <td><?= htmlspecialchars($mov['fecha_movimiento']) ?></td>
                    <td>
                        <!-- Botón para editar movimiento -->
                        <button onclick='abrirEditarModal(<?= json_encode($mov["id"]) ?>, <?= json_encode($mov["tipo"]) ?>, <?= json_encode($mov["metodo_pago"]) ?>, <?= json_encode($mov["metodo_pago_destino"]) ?>, <?= json_encode($mov["monto"]) ?>, <?= json_encode($mov["descripcion"]) ?>)'>Editar</button>
                        <!-- Formulario para eliminar movimiento -->
                        <form action="../controladores/EdicionCierreCajaControlador.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que desea eliminar este movimiento?');">
                            <input type="hidden" name="apertura_id" value="<?= htmlspecialchars($apertura_id) ?>">
                            <input type="hidden" name="cierre_id" value="<?= htmlspecialchars($cierre['id']) ?>">
                            <input type="hidden" name="movimiento_id" value="<?= htmlspecialchars($mov['id']) ?>">
                            <button type="submit" name="accion" value="eliminar_movimiento">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No hay movimientos asociados a esta apertura.</p>
    <?php endif; ?>
    
    <p><a href="reportes.php">Volver a Reportes</a></p>
    
    <!-- Modal para crear nuevo movimiento -->
    <div id="modalNuevoMovimiento" class="modal">
      <div class="modal-content">
        <span class="close" onclick="cerrarModal('modalNuevoMovimiento')">&times;</span>
        <h2>Nuevo Movimiento</h2>
        <form action="../controladores/EdicionCierreCajaControlador.php" method="POST">
            <input type="hidden" name="apertura_id" value="<?= htmlspecialchars($apertura_id) ?>">
            <input type="hidden" name="cierre_id" value="<?= htmlspecialchars($cierre['id']) ?>">
            <input type="hidden" name="redirect" value="detalle">
            
            <label for="nuevo_tipo">Tipo:</label>
            <select name="tipo" id="nuevo_tipo" required>
                <option value="ingreso">Ingreso</option>
                <option value="egreso">Egreso</option>
            </select><br><br>
            
            <label for="nuevo_metodo_pago">Método de Pago:</label>
            <input type="text" name="metodo_pago" id="nuevo_metodo_pago" required><br><br>
            
            <label for="nuevo_metodo_pago_destino">Método de Pago Destino:</label>
            <input type="text" name="metodo_pago_destino" id="nuevo_metodo_pago_destino"><br><br>
            
            <label for="nuevo_monto">Monto:</label>
            <input type="number" step="0.01" name="monto" id="nuevo_monto" required><br><br>
            
            <label for="nuevo_descripcion">Descripción:</label>
            <textarea name="descripcion" id="nuevo_descripcion"></textarea><br><br>
            
            <button type="submit" name="accion" value="crear_movimiento_detalle">Crear Movimiento</button>
            <button type="button" onclick="cerrarModal('modalNuevoMovimiento')">Cancelar</button>
        </form>
      </div>
    </div>
    
    <!-- Modal para editar movimiento -->
    <div id="modalEditarMovimiento" class="modal">
      <div class="modal-content">
        <span class="close" onclick="cerrarModal('modalEditarMovimiento')">&times;</span>
        <h2>Editar Movimiento</h2>
        <form action="../controladores/EdicionCierreCajaControlador.php" method="POST">
            <input type="hidden" name="apertura_id" id="editar_apertura_id" value="<?= htmlspecialchars($apertura_id) ?>">
            <input type="hidden" name="cierre_id" id="editar_cierre_id" value="<?= htmlspecialchars($cierre['id']) ?>">
            <input type="hidden" name="movimiento_id" id="editar_movimiento_id">
            
            <label for="editar_tipo">Tipo:</label>
            <select name="tipo" id="editar_tipo" required>
                <option value="ingreso">Ingreso</option>
                <option value="egreso">Egreso</option>
            </select><br><br>
            
            <label for="editar_metodo_pago">Método de Pago:</label>
            <input type="text" name="metodo_pago" id="editar_metodo_pago" required><br><br>
            
            <label for="editar_metodo_pago_destino">Método de Pago Destino:</label>
            <input type="text" name="metodo_pago_destino" id="editar_metodo_pago_destino"><br><br>
            
            <label for="editar_monto">Monto:</label>
            <input type="number" step="0.01" name="monto" id="editar_monto" required><br><br>
            
            <label for="editar_descripcion">Descripción:</label>
            <textarea name="descripcion" id="editar_descripcion"></textarea><br><br>
            
            <button type="submit" name="accion" value="editar_movimiento">Guardar Cambios</button>
            <button type="button" onclick="cerrarModal('modalEditarMovimiento')">Cancelar</button>
        </form>
      </div>
    </div>
    
    <!-- Modal para editar detalles del cierre de caja -->
    <div id="modalEditarCierre" class="modal">
      <div class="modal-content">
        <span class="close" onclick="cerrarModal('modalEditarCierre')">&times;</span>
        <h2>Editar Detalles del Cierre de Caja #<?= htmlspecialchars($cierre['id']) ?></h2>
        <form id="formEditarCierre" action="../controladores/EdicionCierreCajaControlador.php" method="POST">
          <!-- Campos ocultos -->
          <input type="hidden" name="cierre_id" value="<?= htmlspecialchars($cierre['id']) ?>">
          <input type="hidden" name="ajax" value="true">
          
          <div class="form-group">
            <label for="editar_total_ingresos">Total Ingresos:</label>
            <input type="number" step="0.01" name="total_ingresos" id="editar_total_ingresos" required
                   value="<?= htmlspecialchars($cierre['total_ingresos']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_total_egresos">Total Egresos:</label>
            <input type="number" step="0.01" name="total_egresos" id="editar_total_egresos" required
                   value="<?= htmlspecialchars($cierre['total_egresos']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_saldo_final">Saldo Final:</label>
            <input type="number" step="0.01" name="saldo_final" id="editar_saldo_final" required
                   value="<?= htmlspecialchars($cierre['saldo_final']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_total_ventas_diarias">Total Ventas Diarias:</label>
            <input type="number" step="0.01" name="total_ventas_diarias" id="editar_total_ventas_diarias" required
                   value="<?= htmlspecialchars($cierre['total_ventas_diarias']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_total_efectivo_caja">Total Efectivo Caja:</label>
            <input type="number" step="0.01" name="total_efectivo_caja" id="editar_total_efectivo_caja" required
                   value="<?= htmlspecialchars($cierre['total_efectivo_caja']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_total_calculado">Total Calculado:</label>
            <input type="number" step="0.01" name="total_calculado" id="editar_total_calculado" required
                   value="<?= htmlspecialchars($cierre['total_calculado']) ?>">
          </div>
          
          <div class="form-group">
            <label for="editar_arqueo">Arqueo:</label>
            <input type="number" step="0.01" name="arqueo" id="editar_arqueo" required
                   value="<?= htmlspecialchars($cierre['arqueo']) ?>">
          </div>
          
          <div class="form-group" style="text-align: center;">
            <button type="submit" name="accion" value="editar_cierre">Guardar Cambios</button>
            <button type="button" onclick="cerrarModal('modalEditarCierre')">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
    
    <script>
      // Funciones para abrir y cerrar modales
      function abrirModal(modalID) {
          document.getElementById(modalID).style.display = "block";
      }
      function cerrarModal(modalID) {
          document.getElementById(modalID).style.display = "none";
      }
      
      // Función para abrir el modal de edición de movimiento y rellenar los campos
      function abrirEditarModal(id, tipo, metodo_pago, metodo_pago_destino, monto, descripcion) {
          document.getElementById('editar_movimiento_id').value = id;
          document.getElementById('editar_tipo').value = tipo;
          document.getElementById('editar_metodo_pago').value = metodo_pago;
          document.getElementById('editar_metodo_pago_destino').value = metodo_pago_destino;
          document.getElementById('editar_monto').value = monto;
          document.getElementById('editar_descripcion').value = descripcion;
          abrirModal('modalEditarMovimiento');
      }
      
      // Función para abrir el modal de edición del cierre
      function abrirEditarCierreModal() {
          abrirModal('modalEditarCierre');
      }
      
      // Interceptar el envío del formulario de edición del cierre para actualizar vía AJAX
      $("#formEditarCierre").submit(function(event) {
          event.preventDefault();
          $.ajax({
              type: "POST",
              url: $(this).attr("action"),
              data: $(this).serialize(),
              dataType: "json",
              success: function(response) {
                  if(response.status === "success") {
                      // Actualizar en vivo los valores mostrados en la página
                      $("#total_ingresos").text(parseFloat(response.data.total_ingresos).toFixed(2));
                      $("#total_egresos").text(parseFloat(response.data.total_egresos).toFixed(2));
                      $("#saldo_final").text(parseFloat(response.data.saldo_final).toFixed(2));
                      $("#total_ventas_diarias").text(parseFloat(response.data.total_ventas_diarias).toFixed(2));
                      $("#total_efectivo_caja").text(parseFloat(response.data.total_efectivo_caja).toFixed(2));
                      $("#total_calculado").text(parseFloat(response.data.total_calculado).toFixed(2));
                      $("#arqueo").text(parseFloat(response.data.arqueo).toFixed(2));
                      alert(response.mensaje);
                      cerrarModal("modalEditarCierre");
                  } else {
                      alert("Error: " + response.mensaje);
                  }
              },
              error: function(error) {
                  console.error("Error en la petición AJAX:", error);
                  alert("Error al actualizar los detalles.");
              }
          });
      });
      
      // Cerrar el modal si se hace clic fuera del contenido (para todos los modales)
      window.onclick = function(event) {
          var modales = ['modalNuevoMovimiento', 'modalEditarMovimiento', 'modalEditarCierre'];
          modales.forEach(function(modalID) {
              var modal = document.getElementById(modalID);
              if (event.target == modal) {
                  modal.style.display = "none";
              }
          });
      }
    </script>
</body>
</html>
