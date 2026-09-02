<?php
require_once '_auth.php';
require_once '../config/db.php';
require_once '_layout.php';

$db = db();

$total_tickets  = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$abiertos       = $db->query("SELECT COUNT(*) FROM tickets WHERE estado='abierto'")->fetchColumn();
$en_progreso    = $db->query("SELECT COUNT(*) FROM tickets WHERE estado='en_progreso'")->fetchColumn();
$total_clientes = $db->query("SELECT COUNT(*) FROM clientes WHERE activo=1")->fetchColumn();
$total_usuarios = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn();

$ultimos = $db->query("
    SELECT t.id, t.numero, t.titulo, t.estado, t.prioridad, t.creado_en,
           c.nombre AS cliente_nombre,
           u.nombre AS asignado_nombre
    FROM tickets t
    LEFT JOIN clientes c ON c.id = t.cliente_id
    LEFT JOIN usuarios u ON u.id = t.asignado_a
    ORDER BY t.creado_en DESC LIMIT 8
")->fetchAll();

$estado_css = ['abierto'=>'badge-orange','en_progreso'=>'badge-blue','resuelto'=>'badge-green','cerrado'=>'badge-gray'];
$prior_css  = ['baja'=>'badge-gray','media'=>'badge-blue','alta'=>'badge-orange','urgente'=>'badge-red'];

echo layout_head('Dashboard', 'dashboard');
?>
<div class="wrap">
  <div class="topbar">
    <div><div class="topbar-title">Dashboard</div><div class="topbar-sub">Resumen general del portal</div></div>
  </div>
  <div class="content">
    <div class="stat-grid">
      <div class="stat">
        <div class="stat-ico" style="background:#dbeafe"><svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/></svg></div>
        <div class="stat-label">Total Tickets</div>
        <div class="stat-val"><?= $total_tickets ?></div>
        <div class="stat-sub"><?= $abiertos ?> abiertos · <?= $en_progreso ?> en progreso</div>
      </div>
      <div class="stat">
        <div class="stat-ico" style="background:#ffedd5"><svg fill="none" viewBox="0 0 24 24" stroke="#c2410c" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg></div>
        <div class="stat-label">Tickets Abiertos</div>
        <div class="stat-val"><?= $abiertos ?></div>
        <div class="stat-sub">Pendientes de atención</div>
      </div>
      <div class="stat">
        <div class="stat-ico" style="background:#dcfce7"><svg fill="none" viewBox="0 0 24 24" stroke="#15803d" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg></div>
        <div class="stat-label">Clientes Activos</div>
        <div class="stat-val"><?= $total_clientes ?></div>
        <div class="stat-sub">Empresas registradas</div>
      </div>
      <div class="stat">
        <div class="stat-ico" style="background:#ede9fe"><svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></div>
        <div class="stat-label">Usuarios</div>
        <div class="stat-val"><?= $total_usuarios ?></div>
        <div class="stat-sub">Cuentas activas</div>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <span class="card-title">Últimos tickets</span>
        <a href="tickets.php" class="btn btn-ghost btn-sm">Ver todos</a>
      </div>
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>#</th><th>Título</th><th>Cliente</th><th>Estado</th><th>Prioridad</th><th>Asignado</th><th>Fecha</th>
          </tr></thead>
          <tbody>
          <?php foreach ($ultimos as $t): ?>
          <tr>
            <td><a href="ticket_ver.php?id=<?= $t['id'] ?>" style="color:var(--blue);font-weight:600"><?= htmlspecialchars($t['numero']) ?></a></td>
            <td><?= htmlspecialchars(mb_strimwidth($t['titulo'],0,45,'…')) ?></td>
            <td><?= htmlspecialchars($t['cliente_nombre'] ?? '—') ?></td>
            <td><span class="badge <?= $estado_css[$t['estado']] ?? 'badge-gray' ?>"><?= ucfirst(str_replace('_',' ',$t['estado'])) ?></span></td>
            <td><span class="badge <?= $prior_css[$t['prioridad']] ?? 'badge-gray' ?>"><?= ucfirst($t['prioridad']) ?></span></td>
            <td><?= htmlspecialchars($t['asignado_nombre'] ?? '—') ?></td>
            <td style="color:var(--muted)"><?= date('d/m/Y', strtotime($t['creado_en'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$ultimos): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Sin tickets registrados</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?= layout_foot() ?>
