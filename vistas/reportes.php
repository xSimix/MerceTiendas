<?php
session_start();
require_once "../configuracion/base_datos.php";

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_sesion.php");
    exit();
}

// Recuperar parámetros de filtro y paginación desde GET
$fecha_inicio   = isset($_GET['fecha_inicio'])    ? $_GET['fecha_inicio']    : '';
$fecha_fin      = isset($_GET['fecha_fin'])       ? $_GET['fecha_fin']       : '';
$saldo_negativo = isset($_GET['saldo_negativo'])  ? $_GET['saldo_negativo']  : 0;

// Parámetros de paginación
$limit = 20;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Construir la cláusula WHERE según los filtros
$whereClauses = [];
$params = [];

// Filtrar por rango de fecha o fecha única
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $whereClauses[] = "fecha_cierre BETWEEN :fecha_inicio AND :fecha_fin";
    $params[':fecha_inicio'] = $fecha_inicio . " 00:00:00";
    $params[':fecha_fin']    = $fecha_fin    . " 23:59:59";
} elseif (!empty($fecha_inicio)) {
    $whereClauses[] = "DATE(fecha_cierre) = :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
} elseif (!empty($fecha_fin)) {
    $whereClauses[] = "DATE(fecha_cierre) = :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

// Filtrar por saldos negativos
if ($saldo_negativo) {
    $whereClauses[] = "arqueo < 0";
}

// Combinar condiciones
$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Obtener el total de registros
$countQuery = "SELECT COUNT(*) AS total FROM cierre_caja $whereSQL";
$stmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalRows   = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages  = ceil($totalRows / $limit);

// Consulta paginada
$query = "SELECT * FROM cierre_caja $whereSQL 
          ORDER BY fecha_cierre DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$cierres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Cierres de Caja</title>
    <!-- Tu CSS global -->
    <link rel="stylesheet" href="../publico/css/estilos.css">
    <!-- Iconos (Font Awesome) -->
    <link rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Fuentes (opcional) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link 
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" 
      rel="stylesheet"
    >
</head>
<body>
    <?php include "menu.php"; ?>

    <div class="container-flujo"><!-- Contenedor principal que ya usas en tus vistas -->
      <h1 class="titulo-principal">Reportes de Cierres de Caja</h1>

      <!-- FORMULARIO DE FILTROS -->
      <div class="form-filtros"><!-- clase opcional para agrupar -->
        <form 
          action="../controladores/ReportesControlador.php" 
          method="POST"
          class="formulario-inline"
        >
          <input type="hidden" name="accion" value="generar_reporte">
          
          <div class="form-group">
            <label for="fecha_inicio">Fecha Inicio:</label>
            <input 
              type="date" 
              name="fecha_inicio" 
              id="fecha_inicio" 
              value="<?= htmlspecialchars($fecha_inicio) ?>"
            >
          </div>
          
          <div class="form-group">
            <label for="fecha_fin">Fecha Fin:</label>
            <input 
              type="date" 
              name="fecha_fin" 
              id="fecha_fin" 
              value="<?= htmlspecialchars($fecha_fin) ?>"
            >
          </div>
          
          <div class="form-group form-check">
            <input 
              type="checkbox" 
              name="saldo_negativo" 
              id="saldo_negativo" 
              value="1" 
              <?= $saldo_negativo ? 'checked' : '' ?>
            >
            <label for="saldo_negativo">Solo saldos negativos</label>
          </div>
          
          <button type="submit" class="btn-filtrar">
            <i class="fa fa-search"></i> Filtrar
          </button>
        </form>
      </div><!-- /.form-filtros -->

      <!-- TABLA DE RESULTADOS -->
      <div class="tabla-responsive">
        <table class="tabla-movimientos"><!-- Usa las clases genéricas de tu CSS -->
          <thead>
            <tr>
              <th>ID</th>
              <th>Apertura ID</th>
              <th>Total Ingresos</th>
              <th>Total Egresos</th>
              <th>Saldo Final</th>
              <th>Total Ventas Diarias</th>
              <th>Total Efectivo Caja</th>
              <th>Total Calculado</th>
              <th>Arqueo</th>
              <th>Fecha Cierre</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($cierres) > 0): ?>
              <?php foreach ($cierres as $cierre): ?>
                <tr>
                  <td><?= htmlspecialchars($cierre['id']) ?></td>
                  <td><?= htmlspecialchars($cierre['apertura_id']) ?></td>
                  <td><?= number_format($cierre['total_ingresos'], 2) ?></td>
                  <td><?= number_format($cierre['total_egresos'], 2) ?></td>
                  <td><?= number_format($cierre['saldo_final'], 2) ?></td>
                  <td><?= number_format($cierre['total_ventas_diarias'], 2) ?></td>
                  <td><?= number_format($cierre['total_efectivo_caja'], 2) ?></td>
                  <td><?= number_format($cierre['total_calculado'], 2) ?></td>
                  <td><?= number_format($cierre['arqueo'], 2) ?></td>
                  <td><?= htmlspecialchars($cierre['fecha_cierre']) ?></td>
                  <td>
                    <a 
                      href="../vistas/detalle_cierre.php?id=<?= $cierre['id'] ?>" 
                      class="btn-detalle"
                    >
                      Ver detalle
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="11">No se encontraron registros.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div><!-- /.tabla-responsive -->

      <!-- PAGINACIÓN -->
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a 
            href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
            class="btn-paginacion"
          >
            <i class="fa fa-chevron-left"></i> Anterior
          </a>
        <?php endif; ?>

        <span>Página <?= $page ?> de <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
          <a 
            href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
            class="btn-paginacion"
          >
            Siguiente <i class="fa fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div><!-- /.pagination -->
    </div><!-- /.container-flujo -->

</body>
</html>
