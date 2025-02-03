<?php
session_start();
require '../configuracion/base_datos.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

// Verificar la apertura de caja
$query = "SELECT * FROM apertura_caja WHERE estado = 'abierta' AND usuario_id = :usuario_id LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
$stmt->execute();
$caja_abierta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja_abierta) {
    echo "Error: No hay una apertura de caja activa para este usuario.";
    exit();
}

// Configurar la sesión con el ID de apertura
$_SESSION['apertura_id'] = $caja_abierta['id'];

// Obtener movimientos del día
$query = "SELECT * FROM movimientos_caja WHERE apertura_id = :apertura_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':apertura_id', $_SESSION['apertura_id']);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables para totales
$total_ingresos = 0;
$total_egresos  = 0;
$total_efectivo = $caja_abierta['monto_apertura'];
$total_virtual  = 0;

// Calcular totales
foreach ($movimientos as $movimiento) {
    $monto          = $movimiento['monto'];
    $metodo_pago    = $movimiento['metodo_pago'];
    $metodo_destino = $movimiento['metodo_pago_destino'];

    switch ($movimiento['tipo']) {
        case 'ingreso':
            $total_ingresos += $monto;
            if ($metodo_pago === 'efectivo') {
                $total_efectivo += $monto;
            }
            break;
        case 'egreso':
            $total_egresos += $monto;
            if ($metodo_pago === 'efectivo') {
                $total_efectivo -= $monto;
            }
            break;
        case 'cambio':
            // Ajusta según tu lógica de 'cambio'
            if ($metodo_pago === 'efectivo' && in_array($metodo_destino, ['yape', 'plin', 'transferencia'])) {
                $total_efectivo -= $monto;
                $total_virtual  += $monto;
            } elseif (in_array($metodo_pago, ['yape', 'plin', 'transferencia']) && $metodo_destino === 'efectivo') {
                $total_virtual  -= $monto;
                $total_efectivo += $monto;
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Flujo de Caja</title>

  <!-- Enlaza tu CSS global (el mismo que usas en cierre_caja) -->
  <link rel="stylesheet" href="../publico/css/estilos.css">
  <!-- Iconos Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Fuentes (opcional) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>

<?php include "menu.php"; ?>

<div class="container-flujo">
  <h1>Flujo de Caja</h1>
  
  <!-- Tarjetas de resumen (3 tarjetas) -->
  <div class="cards-resumen">
    <!-- Tarjeta 1: Monto Aperturado -->
    <div class="card-resumen">
      <div class="card-icon">
        <i class="fa-solid fa-cash-register"></i>
      </div>
      <div class="card-info">
        <p>Monto Aperturado</p>
        <h3>S/<?= number_format($caja_abierta['monto_apertura'], 2) ?></h3>
      </div>
    </div>

    <!-- Tarjeta 2: Total Ingresos -->
    <div class="card-resumen">
      <div class="card-icon" style="color: green;">
        <i class="fa-solid fa-arrow-up"></i>
      </div>
      <div class="card-info">
        <p>Total Ingresos</p>
        <h3>S/<?= number_format($total_ingresos, 2) ?></h3>
      </div>
    </div>

    <!-- Tarjeta 3: Total Egresos -->
    <div class="card-resumen">
      <div class="card-icon" style="color: red;">
        <i class="fa-solid fa-arrow-down"></i>
      </div>
      <div class="card-info">
        <p>Total Egresos</p>
        <h3>S/<?= number_format($total_egresos, 2) ?></h3>
      </div>
    </div>
  </div><!-- /cards-resumen -->

  <h2>Movimientos</h2>

  <!-- Botón para registrar movimiento (encima de la tabla) -->
  <div class="acciones-superiores">
    <button class="btn-registrar" onclick="abrirModal('modalRegistro')">
      <i class="fa-solid fa-plus"></i> Registrar Movimiento
    </button>
  </div>

  <!-- Tabla de Movimientos (usa .tabla-responsive + .tabla-movimientos) -->
  <div class="tabla-responsive">
    <table class="tabla-movimientos">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Método de Pago</th>
          <th>Método de Pago Destino</th>
          <th>Monto</th>
          <th>Descripción</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($movimientos as $movimiento): ?>
        <tr>
          <td><?= htmlspecialchars($movimiento['tipo']) ?></td>
          <td><?= htmlspecialchars($movimiento['metodo_pago'] ?? 'Efectivo') ?></td>
          <td><?= htmlspecialchars($movimiento['metodo_pago_destino'] ?? '-') ?></td>
          <td>S/<?= number_format($movimiento['monto'], 2) ?></td>
          <td><?= htmlspecialchars($movimiento['descripcion'] ?? '-') ?></td>
          <td>
            <!-- Formulario para eliminar el movimiento -->
            <form action="../controladores/FlujoCajaControlador.php"
                  method="POST"
                  onsubmit="return confirm('¿Estás seguro de eliminar este movimiento?');">
              <input type="hidden" name="movimiento_id" value="<?= htmlspecialchars($movimiento['id']) ?>">
              <button class="btn-eliminar" type="submit" name="accion" value="eliminar_movimiento">
                Eliminar
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div><!-- /.tabla-responsive -->
</div><!-- /.container-flujo -->

<!-- Modal para Registro de Movimiento -->
<div id="modalRegistro" class="modal">
  <div class="modal-content">
    <h2>Registrar Movimiento</h2>
    <form action="../controladores/FlujoCajaControlador.php" method="POST">
      
      <div class="campo-form">
        <label for="tipo">Tipo:</label>
        <select name="tipo" id="tipo" required>
          <option value="ingreso">Ingreso</option>
          <option value="egreso">Egreso</option>
          <option value="cambio">Cambio</option>
        </select>
      </div>
      
      <div class="campo-form">
        <label for="metodo_pago">Método de Pago:</label>
        <select name="metodo_pago" id="metodo_pago" required>
          <!-- Se llenará dinámicamente con JS según 'tipo' -->
        </select>
      </div>

      <div class="campo-form" id="metodo_pago_destino_div" style="display: none;">
        <label for="metodo_pago_destino">Método de Pago Destino:</label>
        <select name="metodo_pago_destino" id="metodo_pago_destino">
          <option value="efectivo">Efectivo</option>
          <option value="yape">Yape</option>
          <option value="plin">Plin</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      <div class="campo-form">
        <label for="monto">Monto:</label>
        <input type="number" step="0.01" name="monto" id="monto" required>
      </div>
      
      <div class="campo-form">
        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" id="descripcion"></textarea>
      </div>
      
      <div class="botones-form">
        <button type="submit" name="accion" value="registrar_movimiento">Registrar</button>
        <button type="button" onclick="cerrarModal('modalRegistro')">Cerrar</button>
      </div>
    </form>
  </div><!-- /.modal-content -->
</div><!-- /#modalRegistro -->

<script>
// --- Manejo del modal ---
function abrirModal(id) {
  document.getElementById(id).style.display = 'block';
}
function cerrarModal(id) {
  document.getElementById(id).style.display = 'none';
}

// --- Ajustar métodos de pago según 'tipo' ---
document.addEventListener('DOMContentLoaded', function () {
  const tipo            = document.getElementById('tipo');
  const metodoPago      = document.getElementById('metodo_pago');
  const destinoDiv      = document.getElementById('metodo_pago_destino_div');

  function cargarOpciones() {
    metodoPago.innerHTML = "";
    if (tipo.value === 'ingreso' || tipo.value === 'egreso') {
      metodoPago.add(new Option("Efectivo", "efectivo"));
    } else if (tipo.value === 'cambio') {
      metodoPago.add(new Option("Efectivo", "efectivo"));
      metodoPago.add(new Option("Yape", "yape"));
      metodoPago.add(new Option("Plin", "plin"));
      metodoPago.add(new Option("Transferencia", "transferencia"));
    }
    destinoDiv.style.display = (tipo.value === 'cambio') ? 'block' : 'none';
  }

  // Inicializar
  cargarOpciones();
  // Cuando cambia el 'tipo'
  tipo.addEventListener('change', cargarOpciones);
});
</script>

</body>
</html>
