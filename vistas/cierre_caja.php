<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/flujo_caja/configuracion/base_datos.php';

// Definir la URL base del proyecto para evitar rutas relativas problemáticas
if (!defined('BASE_URL')) {
    define('BASE_URL', '/flujo_caja/');
}

// Si no hay usuario autenticado, redirigir de forma absoluta
if (!isset($_SESSION['usuario_id'])) {
    header("Location: " . BASE_URL . "vistas/inicio_sesion.php");
    exit();
}

// Verificar la apertura de caja
$query = "SELECT * FROM apertura_caja WHERE estado = 'abierta' AND usuario_id = :usuario_id LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':usuario_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->execute();
$caja_abierta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caja_abierta) {
    echo "Error: No hay una apertura de caja activa.";
    exit();
}

// Establecer la variable de sesión para la apertura de caja
$_SESSION['apertura_id'] = $caja_abierta['id'];

// Obtener movimientos del día
$query = "SELECT * FROM movimientos_caja WHERE apertura_id = :apertura_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':apertura_id', $_SESSION['apertura_id'], PDO::PARAM_INT);
$stmt->execute();
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_efectivo = $caja_abierta['monto_apertura'];
$total_ingresos = 0;
$total_egresos  = 0;

foreach ($movimientos as $mov) {
    if ($mov['tipo'] === 'ingreso') {
        $total_ingresos += $mov['monto'];
        if ($mov['metodo_pago'] === 'efectivo') {
            $total_efectivo += $mov['monto'];
        }
    } elseif ($mov['tipo'] === 'egreso') {
        $total_egresos += $mov['monto'];
        if ($mov['metodo_pago'] === 'efectivo') {
            $total_efectivo -= $mov['monto'];
        }
    }
}

// Mensajes de estado (evitando XSS)
$status  = isset($_GET['status'])  ? htmlspecialchars($_GET['status'])  : '';
$mensaje = isset($_GET['mensaje']) ? htmlspecialchars($_GET['mensaje']) : '';
$arqueo  = isset($_GET['arqueo'])  ? htmlspecialchars($_GET['arqueo'])  : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cierre de Caja</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Hoja de estilos global usando ruta absoluta -->
  <link rel="stylesheet" href="<?= BASE_URL ?>publico/css/estilos.css">

  <!-- Font Awesome (iconos) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <script>
    function calcularArqueo() {
      const ventasDiarias = parseFloat(document.getElementById('total_ventas_diarias').value) || 0;
      const efectivoCaja  = parseFloat(document.getElementById('total_efectivo_caja').value) || 0;

      // Suma y resta según tus totales
      const totalEsperado = (
        <?= $caja_abierta['monto_apertura'] ?> + 
        <?= $total_ingresos ?> + 
        ventasDiarias - 
        <?= $total_egresos ?>
      );

      const diferencia      = efectivoCaja - totalEsperado;
      const estadoArqueo    = document.getElementById('estado_arqueo');
      const resultadoArqueo = document.getElementById('resultado_arqueo');

      // Quitar clases previas para reasignar luego
      estadoArqueo.classList.remove('resultado-correcto','resultado-sobra','resultado-falta');

      if (diferencia === 0) {
        estadoArqueo.textContent = 'CIERRE CORRECTO';
        estadoArqueo.classList.add('resultado-correcto');
        resultadoArqueo.textContent = 'S/0.00';
      } 
      else if (diferencia > 0) {
        estadoArqueo.textContent = 'SOBRANTE';
        estadoArqueo.classList.add('resultado-sobra');
        resultadoArqueo.textContent = 'S/' + diferencia.toFixed(2);
      } 
      else {
        estadoArqueo.textContent = 'FALTANTE';
        estadoArqueo.classList.add('resultado-falta');
        resultadoArqueo.textContent = 'S/' + Math.abs(diferencia).toFixed(2);
      }
    }

    // Calcula el total a partir del conteo de billetes (en la modal)
    function calcularTotalConteo() {
      let total = 0;
      document.querySelectorAll(".grupo-billetes input").forEach(input => {
        const cant = parseFloat(input.value) || 0;
        const den  = parseFloat(input.dataset.denominacion) || 0;
        total += cant * den;
      });
      document.getElementById('total_conteo').textContent = total.toFixed(2);
      // Asigna al campo "Total Efectivo en Caja"
      document.getElementById('total_efectivo_caja').value = total.toFixed(2);
      calcularArqueo(); 
    }

    // Confirmar cierre si falta dinero
    function confirmarCierre() {
      const totalVentasDiarias = parseFloat(document.getElementById('total_ventas_diarias').value) || 0;
      const totalEfectivoCaja  = parseFloat(document.getElementById('total_efectivo_caja').value)  || 0;
      const montoApertura      = <?= $caja_abierta['monto_apertura'] ?>;
      const totalIngresos      = <?= $total_ingresos ?>;
      const totalEgresos       = <?= $total_egresos ?>;

      const totalEsperado = montoApertura + totalIngresos + totalVentasDiarias - totalEgresos;
      const diferencia    = totalEfectivoCaja - totalEsperado;

      if (diferencia < 0) {
        const msg = "Atención: Faltan S/ " 
                    + Math.abs(diferencia).toFixed(2) 
                    + ". ¿Desea proceder?";
        if (!confirm(msg)) {
          return false;
        }
      }
      return true;
    }

    // Manejo de la Modal de Conteo
    function openConteoModal() {
      document.getElementById('modalConteo').style.display = 'block';
    }
    function closeConteoModal() {
      document.getElementById('modalConteo').style.display = 'none';
    }
  </script>
</head>
<body>
  <!-- Se incluye el menú usando una ruta absoluta en el sistema de archivos -->
  <?php include $_SERVER['DOCUMENT_ROOT'] . BASE_URL . "vistas/menu.php"; ?>

  <div class="container-flujo">
    <h1 class="titulo-principal">Cierre de Caja</h1>

    <!-- Tarjetas de resumen -->
    <div class="cards-resumen">
      <div class="card-resumen">
        <div class="card-icon">
          <i class="fa fa-cash-register"></i>
        </div>
        <div class="card-info">
          <p>Monto Aperturado</p>
          <h3>S/<?= number_format($caja_abierta['monto_apertura'], 2) ?></h3>
        </div>
      </div>
      <div class="card-resumen">
        <div class="card-icon" style="color: green;">
          <i class="fa fa-arrow-up"></i>
        </div>
        <div class="card-info">
          <p>Total Ingresos</p>
          <h3>S/<?= number_format($total_ingresos, 2) ?></h3>
        </div>
      </div>
      <div class="card-resumen">
        <div class="card-icon" style="color: red;">
          <i class="fa fa-arrow-down"></i>
        </div>
        <div class="card-info">
          <p>Total Egresos</p>
          <h3>S/<?= number_format($total_egresos, 2) ?></h3>
        </div>
      </div>
      <div class="card-resumen">
        <div class="card-icon">
          <i class="fa fa-coins"></i>
        </div>
        <div class="card-info">
          <p>Total Efectivo Registrado</p>
          <h3>S/<?= number_format($total_efectivo, 2) ?></h3>
        </div>
      </div>
    </div>

    <!-- Tabla de Movimientos -->
    <h2>Movimientos</h2>
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
              <form 
                action="<?= BASE_URL ?>controladores/CierreCajaControlador.php"
                method="POST"
                onsubmit="return confirm('¿Estás seguro de eliminar este movimiento?');"
              >
                <input type="hidden" name="movimiento_id" value="<?= $movimiento['id'] ?>">
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

    <!-- Título de la sección Arqueo -->
    <h2>Arqueo</h2>

    <div class="arqueo-wrapper">
      <!-- Columna Izquierda: Formulario -->
      <div class="arqueo-col left">
        <form 
          action="<?= BASE_URL ?>controladores/CierreCajaControlador.php" 
          method="POST"
          onsubmit="return confirmarCierre();"
        >
          <div class="arqueo-col left">
            <div class="form-group">
              <label for="total_ventas_diarias">Total Ventas del Día:</label>
              <input 
                type="number"
                step="0.01"
                id="total_ventas_diarias"
                name="total_ventas_diarias"
                oninput="calcularArqueo()"
                required
              >
            </div>

            <div class="form-group">
              <label for="total_efectivo_caja">Total Efectivo en Caja:</label>
              <div class="input-with-button">
                <input 
                  type="number"
                  step="0.01"
                  id="total_efectivo_caja"
                  name="total_efectivo_caja"
                  oninput="calcularArqueo()"
                  required
                >
                <!-- Botón CONTEO DE BILLETES -->
                <button 
                  type="button" 
                  class="btn-conteo"
                  onclick="openConteoModal()"
                >
                  <i class="fa fa-money-bill-wave"></i>&nbsp;
                  CONTEO DE BILLETES
                </button>
              </div>
            </div>

            <div class="form-group">
              <label for="codigo_cierre">Código de Cierre:</label>
              <input 
                type="password"
                name="codigo_cierre"
                id="codigo_cierre"
                maxlength="6"
                required
              >
            </div>

            <button 
              class="btn-cerrar-caja" 
              type="submit" 
              name="accion" 
              value="cerrar_caja"
            >
              CIERRE CAJA
            </button>
          </div>
        </form>
      </div>
      <!-- /.arqueo-col left -->

      <!-- Columna Derecha: Resultado -->
      <div class="arqueo-col right">
        <h2 class="resultado-title">RESULTADO</h2>
        <p class="resultado-sub" id="estado_arqueo">CIERRE CORRECTO</p>
        <p class="resultado-monto" id="resultado_arqueo">S/0</p>
      </div><!-- /.arqueo-col right -->
    </div><!-- /.arqueo-wrapper -->

  </div><!-- /container-flujo -->

  <!-- Modal para conteo de billetes -->
  <div class="modal" id="modalConteo">
    <div class="modal-content">
      <span class="close" onclick="closeConteoModal()">&times;</span>
      <h3 style="text-align:center;">Conteo de Billetes</h3>

      <div class="grupo-billetes">
        <?php
          $denominaciones = [200, 100, 50, 20, 10, 5, 2, 1, 0.50, 0.20];
          foreach ($denominaciones as $den):
        ?>
          <label>S/<?= $den ?>:</label>
          <input 
            type="number"
            min="0"
            data-denominacion="<?= $den ?>"
            oninput="calcularTotalConteo()"
            value="0"
          >
        <?php endforeach; ?>
      </div>
      <p style="margin-top:1rem;"><strong>Total Conteo:</strong> S/<span id="total_conteo">0.00</span></p>

      <div style="text-align: center;">
        <button class="btn-cerrar-caja" type="button" onclick="closeConteoModal()">
          Aceptar
        </button>
      </div>
    </div>
  </div>

</body>
</html>
