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

// Filtrar por rango de fecha o fecha única (columna fecha_cierre de cierre_caja)
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $whereClauses[] = "c.fecha_cierre BETWEEN :fecha_inicio AND :fecha_fin";
    $params[':fecha_inicio'] = $fecha_inicio . " 00:00:00";
    $params[':fecha_fin']    = $fecha_fin    . " 23:59:59";
} elseif (!empty($fecha_inicio)) {
    $whereClauses[] = "DATE(c.fecha_cierre) = :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
} elseif (!empty($fecha_fin)) {
    $whereClauses[] = "DATE(c.fecha_cierre) = :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

// Filtrar por saldos negativos (en cierre_caja, columna arqueo)
if ($saldo_negativo) {
    $whereClauses[] = "c.arqueo < 0";
}

// Combinar condiciones
$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Obtener el total de registros (JOIN con apertura_caja para disponer del monto_apertura)
$countQuery = "SELECT COUNT(*) AS total 
               FROM cierre_caja c 
               INNER JOIN apertura_caja a ON c.apertura_id = a.id 
               $whereSQL";
$stmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalRows  = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $limit);

// Consulta paginada con JOIN para obtener monto_apertura
$query = "SELECT c.*, a.monto_apertura 
          FROM cierre_caja c 
          INNER JOIN apertura_caja a ON c.apertura_id = a.id 
          $whereSQL 
          ORDER BY c.fecha_cierre DESC 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tu CSS global -->
    <link rel="stylesheet" href="../publico/css/estilos.css">
    <!-- Iconos (Font Awesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Fuentes (opcional) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include "menu.php"; ?>

    <div class="container-flujo"><!-- Contenedor principal -->
      <h1 class="titulo-principal">Reportes de Cierres de Caja</h1>

      <!-- FORMULARIO DE FILTROS -->
      <div class="form-filtros">
        <form action="../controladores/ReportesControlador.php" method="POST" class="formulario-inline">
          <input type="hidden" name="accion" value="generar_reporte">
          <div class="form-group">
            <label for="fecha_inicio">Fecha Inicio:</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
          </div>
          <div class="form-group">
            <label for="fecha_fin">Fecha Fin:</label>
            <input type="date" name="fecha_fin" id="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
          </div>
          <div class="form-group form-check">
            <input type="checkbox" name="saldo_negativo" id="saldo_negativo" value="1" <?= $saldo_negativo ? 'checked' : '' ?>>
            <label for="saldo_negativo">Solo saldos negativos</label>
          </div>
          <button type="submit" class="btn-filtrar">
            <i class="fa fa-search"></i> Filtrar
          </button>
        </form>
      </div><!-- /.form-filtros -->

      <!-- TABLA DE RESULTADOS CON IDENTIFICADORES ÚNICOS -->
      <div id="contenedor-tabla-reportes" class="tabla-responsive">
        <table id="tabla-reportes" class="tabla-movimientos">
          <thead>
            <tr>
              <th>Monto Apertura</th>
              <th>Total Ingresos</th>
              <th>Total Egresos</th>
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
                <tr id="reporte-<?= htmlspecialchars($cierre['id']) ?>">
                  <td><?= number_format($cierre['monto_apertura'], 2) ?></td>
                  <td><?= number_format($cierre['total_ingresos'], 2) ?></td>
                  <td><?= number_format($cierre['total_egresos'], 2) ?></td>
                  <td><?= number_format($cierre['total_ventas_diarias'], 2) ?></td>
                  <td><?= number_format($cierre['total_efectivo_caja'], 2) ?></td>
                  <td><?= number_format($cierre['total_calculado'], 2) ?></td>
                  <td><?= number_format($cierre['arqueo'], 2) ?></td>
                  <td><?= htmlspecialchars($cierre['fecha_cierre']) ?></td>
                  <td>
                    <a href="../vistas/detalle_cierre.php?id=<?= $cierre['id'] ?>" class="btn-detalle">
                      Ver detalle
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9">No se encontraron registros.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div><!-- /.tabla-responsive -->

      <!-- PAGINACIÓN -->
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn-paginacion">
            <i class="fa fa-chevron-left"></i> Anterior
          </a>
        <?php endif; ?>
        <span>Página <?= $page ?> de <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn-paginacion">
            Siguiente <i class="fa fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div><!-- /.pagination -->
    </div><!-- /.container-flujo -->
</body>
</html>
