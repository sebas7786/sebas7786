<?php
require_once '_auth.php';
require_once '../config/db.php';
require_once '_layout.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: tickets.php'); exit; }

$ticket = $db->prepare("
    SELECT t.*, c.nombre AS cliente_nombre, u.nombre AS creador_nombre, a.nombre AS asignado_nombre
    FROM tickets t
    LEFT JOIN clientes c ON c.id = t.cliente_id
    LEFT JOIN usuarios u ON u.id = t.usuario_id
    LEFT JOIN usuarios a ON a.id = t.asignado_a
    WHERE t.id = ?
");
$ticket->execute([$id]);
$ticket = $ticket->fetch();
if (!$ticket) { header('Location: tickets.php'); exit; }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'mensaje') {
        $texto = trim($_POST['mensaje'] ?? '');
        if ($texto) {
            $db->prepare("INSERT INTO ticket_mensajes (ticket_id,usuario_id,mensaje) VALUES (?,?,?)")
               ->execute([$id, $_SESSION['usuario_id'], $texto]);
            $db->prepare("UPDATE tickets SET actualizado_en=NOW() WHERE id=?")->execute([$id]);
            $msg = 'Mensaje enviado.';
            header("Location: ticket_ver.php?id={$id}&ok=1"); exit;
        }
    }
    if ($accion === 'estado') {
        $estado = $_POST['estado'] ?? '';
        if (in_array($estado, ['abierto','en_progreso','resuelto','cerrado'])) {
            $db->prepare("UPDATE tickets SET estado=? WHERE id=?")->execute([$estado, $id]);
            header("Location: ticket_ver.php?id={$id}&ok=1"); exit;
        }
    }
}

$mensajes = $db->prepare("
    SELECT m.*, u.nombre AS autor
    FROM ticket_mensajes m
    LEFT JOIN usuarios u ON u.id = m.usuario_id
    WHERE m.ticket_id = ?
    ORDER BY m.creado_en ASC
");
$mensajes->execute([$id]);
$mensajes = $mensajes->fetchAll();

$estado_css = ['abierto'=>'badge-orange','en_progreso'=>'badge-blue','resuelto'=>'badge-green','cerrado'=>'badge-gray'];
$prior_css  = ['baja'=>'badge-gray','media'=>'badge-blue','alta'=>'badge-orange','urgente'=>'badge-red'];

echo layout_head('Ticket ' . $ticket['numero'], 'tickets');
?>
<div class="wrap">
  <div class="topbar">
    <div>
      <div class="topbar-title"><?= htmlspecialchars($ticket['numero']) ?> — <?= htmlspecialchars(mb_strimwidth($ticket['titulo'],0,60,'…')) ?></div>
      <div class="topbar-sub"><a href="tickets.php" style="color:var(--blue)">← Volver a tickets</a></div>
    </div>
    <form method="POST" style="display:flex;align-items:center;gap:.6rem">
      <input type="hidden" name="accion" value="estado"/>
      <select class="sel" name="estado" style="padding:.45rem .75rem;font-size:.82rem">
        <?php foreach (['abierto','en_progreso','resuelto','cerrado'] as $e): ?>
        <option value="<?= $e ?>" <?= $ticket['estado']===$e?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$e)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary btn-sm">Cambiar estado</button>
    </form>
  </div>
  <div class="content">
    <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-ok">Cambio guardado correctamente.</div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start">
      <!-- Conversación -->
      <div>
        <div class="card" style="margin-bottom:1rem">
          <div class="card-head"><span class="card-title">Descripción del ticket</span></div>
          <div style="padding:1.25rem;font-size:.85rem;line-height:1.7;color:var(--ink2);white-space:pre-wrap"><?= htmlspecialchars($ticket['descripcion']) ?></div>
        </div>

        <div class="card" style="margin-bottom:1rem">
          <div class="card-head"><span class="card-title">Mensajes (<?= count($mensajes) ?>)</span></div>
          <div style="padding:1rem;display:flex;flex-direction:column;gap:.75rem">
            <?php if (!$mensajes): ?>
            <p style="color:var(--muted);font-size:.83rem;text-align:center;padding:.5rem">Sin mensajes aún.</p>
            <?php endif; ?>
            <?php foreach ($mensajes as $m):
              $esAdmin = true;
            ?>
            <div style="background:#f8fafc;border:1px solid var(--bd);border-radius:8px;padding:.85rem">
              <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                <span style="font-size:.75rem;font-weight:700;color:var(--ink2)"><?= htmlspecialchars($m['autor'] ?? 'Sistema') ?></span>
                <span style="font-size:.7rem;color:var(--muted)"><?= date('d/m/Y H:i', strtotime($m['creado_en'])) ?></span>
              </div>
              <p style="font-size:.84rem;line-height:1.6;white-space:pre-wrap;color:var(--ink)"><?= htmlspecialchars($m['mensaje']) ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><span class="card-title">Agregar respuesta</span></div>
          <div style="padding:1.25rem">
            <form method="POST">
              <input type="hidden" name="accion" value="mensaje"/>
              <div class="fld" style="margin-bottom:1rem">
                <textarea class="ta" name="mensaje" rows="4" placeholder="Escribí tu respuesta o comentario…" required></textarea>
              </div>
              <button class="btn btn-primary">Enviar mensaje</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Panel lateral -->
      <div style="display:flex;flex-direction:column;gap:1rem">
        <div class="card">
          <div class="card-head"><span class="card-title">Información</span></div>
          <div style="padding:1rem;display:flex;flex-direction:column;gap:.65rem;font-size:.82rem">
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Estado</span>
              <span class="badge <?= $estado_css[$ticket['estado']] ?? 'badge-gray' ?>"><?= ucfirst(str_replace('_',' ',$ticket['estado'])) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Prioridad</span>
              <span class="badge <?= $prior_css[$ticket['prioridad']] ?? 'badge-gray' ?>"><?= ucfirst($ticket['prioridad']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Cliente</span>
              <span style="font-weight:600"><?= htmlspecialchars($ticket['cliente_nombre'] ?? '—') ?></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Asignado a</span>
              <span><?= htmlspecialchars($ticket['asignado_nombre'] ?? 'Sin asignar') ?></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Creado por</span>
              <span><?= htmlspecialchars($ticket['creador_nombre'] ?? '—') ?></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--muted)">Fecha</span>
              <span><?= date('d/m/Y H:i', strtotime($ticket['creado_en'])) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?= layout_foot() ?>
