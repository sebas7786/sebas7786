<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
require_admin();

// ================================
// PAGINACIÓN Y FILTROS
// ================================
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$offset = ($page - 1) * $per_page;

// Filtros
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filter_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filter_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filter_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// ================================
// ESTADÍSTICAS
// ================================
$stats = [
    'users' => 0,
    'tenders' => 0,
    'active_users' => 0,
    'categories' => 0
];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0");
$stats['users'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM licitaciones");
$stats['tenders'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM usuarios WHERE es_admin = 0 AND estado_pago = 1");
$stats['active_users'] = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(DISTINCT categoria) as count FROM licitaciones");
$stats['categories'] = $stmt->fetch()['count'];

// ================================
// CONSULTA DE LICITACIONES CON FILTROS
// ================================
$where_conditions = [];
$params = [];

if (!empty($search_query)) {
    $where_conditions[] = "(l.numero_procedimiento LIKE ? OR l.titulo LIKE ? OR l.institucion LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filter_estado)) {
    $where_conditions[] = "l.estado = ?";
    $params[] = $filter_estado;
}

if (!empty($filter_categoria)) {
    $where_conditions[] = "l.categoria = ?";
    $params[] = $filter_categoria;
}

if (!empty($filter_fecha_desde)) {
    $where_conditions[] = "l.fecha_publicacion >= ?";
    $params[] = $filter_fecha_desde;
}

if (!empty($filter_fecha_hasta)) {
    $where_conditions[] = "l.fecha_publicacion <= ?";
    $params[] = $filter_fecha_hasta;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Contar total de licitaciones con filtros
$count_sql = "SELECT COUNT(*) as total FROM licitaciones l {$where_clause}";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_licitaciones = $stmt->fetch()['total'];
$total_pages = ceil($total_licitaciones / $per_page);

// Obtener licitaciones con filtros y paginación
$sql = "SELECT l.*, u.nombre as creador
        FROM licitaciones l
        LEFT JOIN usuarios u ON l.creada_por = u.id
        {$where_clause}
        ORDER BY l.fecha_publicacion DESC, l.fecha_creacion DESC
        LIMIT {$per_page} OFFSET {$offset}";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$licitaciones = $stmt->fetchAll();

// Obtener todas las categorías para el filtro
$categorias_stmt = $conn->query("SELECT DISTINCT categoria FROM licitaciones WHERE categoria IS NOT NULL ORDER BY categoria");
$categorias = $categorias_stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener licitaciones recientes (sin filtros)
$stmt = $conn->query("SELECT l.*, u.nombre as creador FROM licitaciones l
                     LEFT JOIN usuarios u ON l.creada_por = u.id
                     ORDER BY l.fecha_creacion DESC LIMIT 5");
$recent_tenders = $stmt->fetchAll();

include '../includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/admin-styles.css">
<style>
    /* Estilos mejorados para el dashboard */
    .filters-container {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .filters-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .filter-toggle {
        color: #2563eb;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    .filter-input,
    .filter-select {
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .filters-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        text-decoration: none;
    }

    .btn-primary {
        background: #2563eb;
        color: white;
    }

    .btn-primary:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .results-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .results-count {
        font-size: 1rem;
        color: #64748b;
    }

    .results-count strong {
        color: #1e293b;
        font-weight: 600;
    }

    .per-page-selector {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .per-page-selector select {
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead {
        background: #f8fafc;
    }

    table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #1e293b;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
    }

    table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    table tr:hover {
        background: #f8fafc;
    }

    .tender-title {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #1e293b;
        font-weight: 500;
    }

    .tender-code {
        font-family: 'Courier New', monospace;
        color: #2563eb;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .tender-category {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #f1f5f9;
        color: #475569;
        border-radius: 6px;
        font-size: 0.85rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-badge.abierta {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge.cerrada {
        background: #fee2e2;
        color: #991b1b;
    }

    .status-badge.en-evaluacion {
        background: #fef3c7;
        color: #92400e;
    }

    .table-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .btn-icon.view {
        background: #10b981;
    }

    .btn-icon.edit {
        background: #3b82f6;
    }

    .btn-icon.delete {
        background: #ef4444;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .pagination-info {
        color: #64748b;
        font-size: 0.875rem;
        margin: 0 1rem;
    }

    .pagination-btn {
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: white;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
        padding: 0 0.75rem;
    }

    .pagination-btn:hover:not(.disabled):not(.active) {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .pagination-btn.active {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        color: #1e293b;
        margin-bottom: 0.75rem;
    }

    .empty-state p {
        color: #64748b;
        margin-bottom: 2rem;
    }

    .active-filters {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .filter-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 6px;
        font-size: 0.85rem;
    }

    .filter-tag button {
        background: none;
        border: none;
        color: #1e40af;
        cursor: pointer;
        padding: 0;
        margin-left: 0.25rem;
    }

    .section-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .section-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .results-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .pagination {
            flex-wrap: wrap;
        }
    }
</style>

<div class="admin-container">
    <div class="dashboard-header">
        <h1>Panel de Administración</h1>
        <div class="dashboard-actions">
            <a href="users.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="tenders.php" class="btn btn-primary">
                <i class="fas fa-file-contract"></i> Licitaciones
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Usuarios</h3>
                <p class="stat-number"><?php echo $stats['users']; ?></p>
                <div class="stat-progress">
                    <i class="fas fa-user-check"></i> <?php echo $stats['active_users']; ?> activos
                </div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-file-contract"></i>
            </div>
            <div class="stat-content">
                <h3>Licitaciones</h3>
                <p class="stat-number"><?php echo number_format($stats['tenders']); ?></p>
                <div class="stat-progress">
                    <i class="fas fa-tag"></i> <?php echo $stats['categories']; ?> categorías
                </div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Usuarios Activos</h3>
                <p class="stat-number"><?php echo $stats['active_users']; ?></p>
                <div class="stat-progress">
                    <?php
                    $percentage = $stats['users'] > 0 ? round(($stats['active_users'] / $stats['users']) * 100) : 0;
                    echo "<i class=\"fas fa-percentage\"></i> {$percentage}% del total";
                    ?>
                </div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Fecha</h3>
                <p class="stat-number" style="font-size: 1.5rem;"><?php echo date('d'); ?></p>
                <div class="stat-progress">
                    <i class="fas fa-clock"></i> <?php echo date('M Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="filters-container">
        <div class="filters-header">
            <h3><i class="fas fa-filter"></i> Filtros y Búsqueda</h3>
        </div>

        <form method="GET" action="">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Buscar</label>
                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Código, título o institución..."
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label class="filter-label">Estado</label>
                    <select name="estado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="abierta" <?php echo $filter_estado === 'abierta' ? 'selected' : ''; ?>>Abierta</option>
                        <option value="cerrada" <?php echo $filter_estado === 'cerrada' ? 'selected' : ''; ?>>Cerrada</option>
                        <option value="en-evaluacion" <?php echo $filter_estado === 'en-evaluacion' ? 'selected' : ''; ?>>En Evaluación</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Categoría</label>
                    <select name="categoria" class="filter-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_categoria === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Fecha Desde</label>
                    <input
                        type="date"
                        name="fecha_desde"
                        class="filter-input"
                        value="<?php echo htmlspecialchars($filter_fecha_desde); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label class="filter-label">Fecha Hasta</label>
                    <input
                        type="date"
                        name="fecha_hasta"
                        class="filter-input"
                        value="<?php echo htmlspecialchars($filter_fecha_hasta); ?>"
                    >
                </div>

                <div class="filter-group">
                    <label class="filter-label">Resultados por página</label>
                    <select name="per_page" class="filter-select">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>

            <?php if (!empty($search_query) || !empty($filter_estado) || !empty($filter_categoria) || !empty($filter_fecha_desde) || !empty($filter_fecha_hasta)): ?>
            <div class="active-filters">
                <strong style="color: #475569; font-size: 0.875rem;">Filtros activos:</strong>
                <?php if (!empty($search_query)): ?>
                    <span class="filter-tag">
                        Búsqueda: "<?php echo htmlspecialchars($search_query); ?>"
                    </span>
                <?php endif; ?>
                <?php if (!empty($filter_estado)): ?>
                    <span class="filter-tag">
                        Estado: <?php echo htmlspecialchars($filter_estado); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($filter_categoria)): ?>
                    <span class="filter-tag">
                        Categoría: <?php echo htmlspecialchars($filter_categoria); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($filter_fecha_desde)): ?>
                    <span class="filter-tag">
                        Desde: <?php echo htmlspecialchars($filter_fecha_desde); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($filter_fecha_hasta)): ?>
                    <span class="filter-tag">
                        Hasta: <?php echo htmlspecialchars($filter_fecha_hasta); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla de Licitaciones -->
    <div class="section-card">
        <div class="results-header">
            <div class="results-info">
                <h2 style="margin: 0;">Todas las Licitaciones</h2>
                <span class="results-count">
                    Mostrando <strong><?php echo count($licitaciones); ?></strong> de <strong><?php echo number_format($total_licitaciones); ?></strong> licitaciones
                </span>
            </div>
            <div>
                <a href="tenders.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Licitación
                </a>
            </div>
        </div>

        <?php if (empty($licitaciones)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>No se encontraron licitaciones</h3>
                <p>No hay licitaciones que coincidan con los criterios de búsqueda.</p>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Limpiar filtros
                </a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>Categoría</th>
                                <th>Institución</th>
                                <th>Estado</th>
                                <th>Fecha Publicación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($licitaciones as $lic): ?>
                                <tr>
                                    <td>
                                        <span class="tender-code">
                                            <?php echo htmlspecialchars($lic['numero_procedimiento'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="tender-title" title="<?php echo htmlspecialchars($lic['titulo']); ?>">
                                            <?php echo htmlspecialchars($lic['titulo']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="tender-category">
                                            <?php echo htmlspecialchars($lic['categoria'] ?? 'Sin categoría'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($lic['institucion'] ?? 'N/A', 0, 30)); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($lic['estado'] ?? ''); ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($lic['estado'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($lic['fecha_publicacion'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="tenders.php?action=view&id=<?php echo $lic['id']; ?>" class="btn-icon view" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="tenders.php?action=edit&id=<?php echo $lic['id']; ?>" class="btn-icon edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="tenders.php?action=delete&id=<?php echo $lic['id']; ?>"
                                               class="btn-icon delete"
                                               title="Eliminar"
                                               onclick="return confirm('¿Seguro que desea eliminar esta licitación?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Primera página -->
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_estado) ? '&estado='.$filter_estado : ''; ?><?php echo !empty($filter_categoria) ? '&categoria='.urlencode($filter_categoria) : ''; ?>&per_page=<?php echo $per_page; ?>" class="pagination-btn">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_estado) ? '&estado='.$filter_estado : ''; ?><?php echo !empty($filter_categoria) ? '&categoria='.urlencode($filter_categoria) : ''; ?>&per_page=<?php echo $per_page; ?>" class="pagination-btn">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Páginas -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_estado) ? '&estado='.$filter_estado : ''; ?><?php echo !empty($filter_categoria) ? '&categoria='.urlencode($filter_categoria) : ''; ?>&per_page=<?php echo $per_page; ?>"
                           class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Última página -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_estado) ? '&estado='.$filter_estado : ''; ?><?php echo !empty($filter_categoria) ? '&categoria='.urlencode($filter_categoria) : ''; ?>&per_page=<?php echo $per_page; ?>" class="pagination-btn">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search_query) ? '&search='.urlencode($search_query) : ''; ?><?php echo !empty($filter_estado) ? '&estado='.$filter_estado : ''; ?><?php echo !empty($filter_categoria) ? '&categoria='.urlencode($filter_categoria) : ''; ?>&per_page=<?php echo $per_page; ?>" class="pagination-btn">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>

                    <span class="pagination-info">
                        Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                    </span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
