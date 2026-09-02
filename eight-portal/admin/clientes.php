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
        $nombre    = trim($_POST['nombre']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        if (!$nombre) {
            $err = 'El nombre del cliente es obligatorio.';
        } else {
            $db->prepare("INSERT INTO clientes (nombre,email,telefono,direccion) VALUES (?,?,?,?)")
               ->execute([$nombre, $email ?: null, $telefono ?: null, $direccion ?: null]);
            $msg = "Cliente <strong>" . htmlspecialchars($nombre) . "</strong> agregado correctamente.";
        }
    }

    if ($accion === 'editar') {
        $id        = (int)$_POST['id'];
        $nombre    = trim($_POST['nombre']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        if ($nombre && $id) {
            $db->prepare("UPDATE clientes SET nombre=?,email=?,telefono=?,direccion=? WHERE id=?")
               ->execute([$nombre, $email ?: null, $telefono ?: null, $direccion ?: null, $id]);
            $msg = 'Cliente actualizado.';
        }
    }

    if ($accion === 'toggle') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE clientes SET activo = NOT activo WHERE id=?")->execute([$id]);
        $msg = 'Estado del cliente actualizado.';
    }
}

$clientes = $db->query("
    SELECT c.*, COUNT(t.id) AS total_tickets
    FROM clientes c
    LEFT JOIN tickets t ON t.cliente_id = c.id
    GROUP BY c.id
    ORDER BY c.creado_en DESC
")->fetchAll();

echo layout_head('Clientes', 'clientes');
?>
<div class="wrap">
  <div class="topbar">
    <div><div class="topbar-title">Clientes</div><div class="topbar-sub">Administración de empresas y organizaciones</div></div>
    <button class="btn btn-primary" onclick="document.getElementById('mCreate').classList.add('open')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Nuevo cliente
    </button>
  </div>
  <div class="content">
    <?php if ($msg): ?><div class="alert alert-ok"><?= $msg ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card">
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Tickets</th><th>Estado</th><th>Registrado</th><th>Acciones</th>
          </tr></thead>
          <tbody>
          <?php foreach ($clientes as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($c['email'] ?? '—') ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
            <td><span class="badge badge-blue"><?= $c['total_tickets'] ?></span></td>
            <td><?= $c['activo'] ? '<span class="badge badge-green">Activo</span>' : '<span class="badge badge-red">Inactivo</span>' ?></td>
            <td style="color:var(--muted)"><?= date('d/m/Y', strtotime($c['creado_en'])) ?></td>
            <td>
              <div style="display:flex;gap:.4rem">
                <button class="btn btn-ghost btn-sm" onclick='abrirEditar(<?= json_encode($c) ?>)'>Editar</button>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="accion" value="toggle"/>
                  <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
                  <button class="btn btn-ghost btn-sm"><?= $c['activo'] ? 'Dar de baja' : 'Activar' ?></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$clientes): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Sin clientes registrados</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal crear -->
<div class="overlay" id="mCreate" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Nuevo cliente</span>
      <button class="modal-x" onclick="document.getElementById('mCreate').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="crear"/>
      <div class="fgrid">
        <div class="fld" style="grid-column:1/-1">
          <label>Nombre / Empresa *</label>
          <input class="inp" type="text" name="nombre" required/>
        </div>
        <div class="fld">
          <label>Correo electrónico</label>
          <input class="inp" type="email" name="email"/>
        </div>
        <div class="fld">
          <label>Teléfono</label>
          <input class="inp" type="text" name="telefono"/>
        </div>
        <div class="fld" style="grid-column:1/-1">
          <label>Dirección</label>
          <textarea class="ta" name="direccion" rows="2"></textarea>
        </div>
      </div>
      <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('mCreate').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cliente</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal editar -->
<div class="overlay" id="mEdit" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Editar cliente</span>
      <button class="modal-x" onclick="document.getElementById('mEdit').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="editar"/>
      <input type="hidden" name="id" id="editId"/>
      <div class="fgrid">
        <div class="fld" style="grid-column:1/-1">
          <label>Nombre / Empresa *</label>
          <input class="inp" type="text" name="nombre" id="editNombre" required/>
        </div>
        <div class="fld">
          <label>Correo electrónico</label>
          <input class="inp" type="email" name="email" id="editEmail"/>
        </div>
        <div class="fld">
          <label>Teléfono</label>
          <input class="inp" type="text" name="telefono" id="editTel"/>
        </div>
        <div class="fld" style="grid-column:1/-1">
          <label>Dirección</label>
          <textarea class="ta" name="direccion" id="editDir" rows="2"></textarea>
        </div>
      </div>
      <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('mEdit').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirEditar(c) {
  document.getElementById('editId').value      = c.id;
  document.getElementById('editNombre').value  = c.nombre || '';
  document.getElementById('editEmail').value   = c.email  || '';
  document.getElementById('editTel').value     = c.telefono  || '';
  document.getElementById('editDir').value     = c.direccion || '';
  document.getElementById('mEdit').classList.add('open');
}
</script>
<?= layout_foot() ?>
