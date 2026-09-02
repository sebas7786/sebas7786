<?php
require_once '_auth.php';
require_once '../config/db.php';
require_once '_layout.php';

$db  = db();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $titulo      = trim($_POST['titulo']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $prioridad   = in_array($_POST['prioridad'] ?? '', ['baja','media','alta','urgente']) ? $_POST['prioridad'] : 'media';
        $cliente_id  = (int)($_POST['cliente_id'] ?? 0) ?: null;
        $asignado_a  = (int)($_POST['asignado_a'] ?? 0) ?: null;

        if (!$titulo || !$descripcion) {
            $err = 'Título y descripción son obligatorios.';
        } else {
            $numero = 'TK-' . strtoupper(substr(uniqid(), -6));
            $db->prepare("INSERT INTO tickets (numero,titulo,descripcion,prioridad,cliente_id,asignado_a,usuario_id) VALUES (?,?,?,?,?,?,?)")
               ->execute([$numero, $titulo, $descripcion, $prioridad, $cliente_id, $asignado_a, $_SESSION['usuario_id']]);
            $msg = "Ticket <strong>{$numero}</strong> creado.";
        }
    }

    if ($accion === 'estado') {
        $id     = (int)$_POST['id'];
        $estado = in_array($_POST['estado'] ?? '', ['abierto','en_progreso','resuelto','cerrado']) ? $_POST['estado'] : null;
        if ($id && $estado) {
            $db->prepare("UPDATE tickets SET estado=? WHERE id=?")->execute([$estado, $id]);
            $msg = 'Estado actualizado.';
        }
    }

    if ($accion === 'asignar') {
        $id  = (int)$_POST['id'];
        $uid = (int)$_POST['asignado_a'] ?: null;
        $db->prepare("UPDATE tickets SET asignado_a=? WHERE id=?")->execute([$uid, $id]);
        $msg = 'Ticket reasignado.';
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_prior  = $_GET['prioridad'] ?? '';
$where = ['1=1'];
$params = [];
if ($filtro_estado && in_array($filtro_estado, ['abierto','en_progreso','resuelto','cerrado'])) {
    $where[] = 't.estado = ?'; $params[] = $filtro_estado;
}
if ($filtro_prior && in_array($filtro_prior, ['baja','media','alta','urgente'])) {
    $where[] = 't.prioridad = ?'; $params[] = $filtro_prior;
}
$where_sql = implode(' AND ', $where);

$tickets  = $db->prepare("
    SELECT t.*, c.nombre AS cliente_nombre, u.nombre AS asignado_nombre
    FROM tickets t
    LEFT JOIN clientes c ON c.id = t.cliente_id
    LEFT JOIN usuarios u ON u.id = t.asignado_a
    WHERE {$where_sql}
    ORDER BY t.creado_en DESC
");
$tickets->execute($params);
$tickets = $tickets->fetchAll();

$clientes = $db->query("SELECT id,nombre FROM clientes WHERE activo=1 ORDER BY nombre")->fetchAll();
$agentes  = $db->query("SELECT id,nombre FROM usuarios WHERE activo=1 AND rol IN ('admin','agente') ORDER BY nombre")->fetchAll();

$estado_css = ['abierto'=>'badge-orange','en_progreso'=>'badge-blue','resuelto'=>'badge-green','cerrado'=>'badge-gray'];
$prior_css  = ['baja'=>'badge-gray','media'=>'badge-blue','alta'=>'badge-orange','urgente'=>'badge-red'];

echo layout_head('Tickets', 'tickets');
?>
<div class="wrap">
  <div class="topbar">
    <div><div class="topbar-title">Tickets de soporte</div><div class="topbar-sub"><?= count($tickets) ?> resultados</div></div>
    <button class="btn btn-primary" onclick="document.getElementById('mCreate').classList.add('open')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Nuevo ticket
    </button>
  </div>
  <div class="content">
    <?php if ($msg): ?><div class="alert alert-ok"><?= $msg ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- Filtros -->
    <form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
      <select class="sel" name="estado" style="width:auto;min-width:150px" onchange="this.form.submit()">
        <option value="">Todos los estados</option>
        <?php foreach (['abierto','en_progreso','resuelto','cerrado'] as $e): ?>
        <option value="<?= $e ?>" <?= $filtro_estado===$e?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$e)) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="sel" name="prioridad" style="width:auto;min-width:150px" onchange="this.form.submit()">
        <option value="">Todas las prioridades</option>
        <?php foreach (['baja','media','alta','urgente'] as $p): ?>
        <option value="<?= $p ?>" <?= $filtro_prior===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filtro_estado || $filtro_prior): ?>
      <a href="tickets.php" class="btn btn-ghost">Limpiar</a>
      <?php endif; ?>
    </form>

    <div class="card">
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>#</th><th>Título</th><th>Cliente</th><th>Estado</th><th>Prioridad</th><th>Asignado</th><th>Fecha</th><th>Acciones</th>
          </tr></thead>
          <tbody>
          <?php foreach ($tickets as $t): ?>
          <tr>
            <td><strong style="color:var(--blue)"><?= htmlspecialchars($t['numero']) ?></strong></td>
            <td><?= htmlspecialchars(mb_strimwidth($t['titulo'],0,48,'…')) ?></td>
            <td><?= htmlspecialchars($t['cliente_nombre'] ?? '—') ?></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="accion" value="estado"/>
                <input type="hidden" name="id" value="<?= $t['id'] ?>"/>
                <select class="sel" name="estado" onchange="this.form.submit()" style="padding:.25rem .5rem;font-size:.75rem;width:auto">
                  <?php foreach (['abierto','en_progreso','resuelto','cerrado'] as $e): ?>
                  <option value="<?= $e ?>" <?= $t['estado']===$e?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$e)) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><span class="badge <?= $prior_css[$t['prioridad']] ?? 'badge-gray' ?>"><?= ucfirst($t['prioridad']) ?></span></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="accion" value="asignar"/>
                <input type="hidden" name="id" value="<?= $t['id'] ?>"/>
                <select class="sel" name="asignado_a" onchange="this.form.submit()" style="padding:.25rem .5rem;font-size:.75rem;width:auto">
                  <option value="">Sin asignar</option>
                  <?php foreach ($agentes as $a): ?>
                  <option value="<?= $a['id'] ?>" <?= $t['asignado_a']==$a['id']?'selected':'' ?>><?= htmlspecialchars($a['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td style="color:var(--muted)"><?= date('d/m/Y', strtotime($t['creado_en'])) ?></td>
            <td><a href="ticket_ver.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">Ver</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$tickets): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">Sin tickets</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal nuevo ticket -->
<div class="overlay" id="mCreate" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Nuevo ticket</span>
      <button class="modal-x" onclick="document.getElementById('mCreate').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="crear"/>
      <div style="display:flex;flex-direction:column;gap:1rem">
        <div class="fld">
          <label>Título *</label>
          <input class="inp" type="text" name="titulo" required placeholder="Descripción breve del problema"/>
        </div>
        <div class="fld">
          <label>Descripción *</label>
          <textarea class="ta" name="descripcion" required placeholder="Detalle completo del problema…"></textarea>
        </div>
        <div class="fgrid">
          <div class="fld">
            <label>Prioridad</label>
            <select class="sel" name="prioridad">
              <option value="baja">Baja</option>
              <option value="media" selected>Media</option>
              <option value="alta">Alta</option>
              <option value="urgente">Urgente</option>
            </select>
          </div>
          <div class="fld">
            <label>Cliente</label>
            <select class="sel" name="cliente_id">
              <option value="">Sin cliente</option>
              <?php foreach ($clientes as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fld" style="grid-column:1/-1">
            <label>Asignar a</label>
            <select class="sel" name="asignado_a">
              <option value="">Sin asignar</option>
              <?php foreach ($agentes as $a): ?>
              <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('mCreate').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear ticket</button>
      </div>
    </form>
  </div>
</div>
<?= layout_foot() ?>
