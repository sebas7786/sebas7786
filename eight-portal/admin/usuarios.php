<?php
require_once '_auth.php';
require_once '../config/db.php';
require_once '_layout.php';

$db  = db();
$msg = '';
$err = '';

// ── Acciones POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $pass   = trim($_POST['password'] ?? '');
        $rol    = in_array($_POST['rol'] ?? '', ['admin','agente','cliente']) ? $_POST['rol'] : 'cliente';
        if (!$nombre || !$email || !$pass) {
            $err = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'El correo no es válido.';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO usuarios (nombre,email,password_hash,rol) VALUES (?,?,?,?)")
                   ->execute([$nombre, $email, $hash, $rol]);
                $msg = "Usuario <strong>" . htmlspecialchars($nombre) . "</strong> creado correctamente.";
            } catch (Exception $e) {
                $err = 'El correo ya existe o hubo un error al guardar.';
            }
        }
    }

    if ($accion === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ? AND id != ?")->execute([$id, $_SESSION['usuario_id']]);
        $msg = 'Estado del usuario actualizado.';
    }

    if ($accion === 'cambiar_pass') {
        $id   = (int)($_POST['id'] ?? 0);
        $pass = trim($_POST['nueva_pass'] ?? '');
        if (strlen($pass) < 6) {
            $err = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE usuarios SET password_hash=? WHERE id=?")->execute([$hash, $id]);
            $msg = 'Contraseña actualizada correctamente.';
        }
    }
}

$usuarios = $db->query("SELECT id,nombre,email,rol,activo,creado_en,ultimo_login FROM usuarios ORDER BY creado_en DESC")->fetchAll();

echo layout_head('Usuarios', 'usuarios');
?>
<div class="wrap">
  <div class="topbar">
    <div><div class="topbar-title">Usuarios</div><div class="topbar-sub">Gestión de cuentas del portal</div></div>
    <button class="btn btn-primary" onclick="document.getElementById('mCreate').classList.add('open')">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Nuevo usuario
    </button>
  </div>
  <div class="content">
    <?php if ($msg): ?><div class="alert alert-ok"><?= $msg ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card">
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr>
            <th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Último login</th><th>Creado</th><th>Acciones</th>
          </tr></thead>
          <tbody>
          <?php foreach ($usuarios as $u):
            $esYo = ($u['id'] == $_SESSION['usuario_id']);
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($u['nombre']) ?></strong><?= $esYo ? ' <span style="font-size:.65rem;color:var(--blue)">(vos)</span>' : '' ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
            <td><?php
              $rc = ['admin'=>'badge-blue','agente'=>'badge-orange','cliente'=>'badge-gray'];
              echo '<span class="badge ' . ($rc[$u['rol']] ?? 'badge-gray') . '">' . ucfirst($u['rol']) . '</span>';
            ?></td>
            <td><?= $u['activo'] ? '<span class="badge badge-green">Activo</span>' : '<span class="badge badge-red">Inactivo</span>' ?></td>
            <td style="color:var(--muted)"><?= $u['ultimo_login'] ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : '—' ?></td>
            <td style="color:var(--muted)"><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
            <td>
              <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                <?php if (!$esYo): ?>
                <form method="POST">
                  <input type="hidden" name="accion" value="toggle"/>
                  <input type="hidden" name="id" value="<?= $u['id'] ?>"/>
                  <button class="btn btn-ghost btn-sm" title="<?= $u['activo'] ? 'Dar de baja' : 'Activar' ?>">
                    <?= $u['activo'] ? 'Dar de baja' : 'Activar' ?>
                  </button>
                </form>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm" onclick="abrirPass(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')">Nueva contraseña</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal crear usuario -->
<div class="overlay" id="mCreate" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">Nuevo usuario</span>
      <button class="modal-x" onclick="document.getElementById('mCreate').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="crear"/>
      <div class="fgrid">
        <div class="fld" style="grid-column:1/-1">
          <label>Nombre completo *</label>
          <input class="inp" type="text" name="nombre" required/>
        </div>
        <div class="fld" style="grid-column:1/-1">
          <label>Correo electrónico *</label>
          <input class="inp" type="email" name="email" required/>
        </div>
        <div class="fld">
          <label>Contraseña *</label>
          <input class="inp" type="password" name="password" required/>
        </div>
        <div class="fld">
          <label>Rol</label>
          <select class="sel" name="rol">
            <option value="cliente">Cliente</option>
            <option value="agente">Agente</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
      </div>
      <div style="margin-top:1.5rem;display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('mCreate').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear usuario</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal cambiar contraseña -->
<div class="overlay" id="mPass" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal" style="max-width:380px">
    <div class="modal-head">
      <span class="modal-title" id="mPassTitle">Cambiar contraseña</span>
      <button class="modal-x" onclick="document.getElementById('mPass').classList.remove('open')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="accion" value="cambiar_pass"/>
      <input type="hidden" name="id" id="mPassId"/>
      <div class="fld">
        <label>Nueva contraseña (mín. 6 caracteres)</label>
        <input class="inp" type="password" name="nueva_pass" required minlength="6"/>
      </div>
      <div style="margin-top:1.25rem;display:flex;justify-content:flex-end;gap:.75rem">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('mPass').classList.remove('open')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirPass(id, nombre) {
  document.getElementById('mPassId').value = id;
  document.getElementById('mPassTitle').textContent = 'Contraseña de ' + nombre;
  document.getElementById('mPass').classList.add('open');
}
</script>
<?= layout_foot() ?>
