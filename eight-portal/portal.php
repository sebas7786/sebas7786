<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['usuario_id'])) {
    $dest = ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'agente')
        ? 'admin/dashboard.php' : 'cliente/dashboard.php';
    header("Location: $dest"); exit;
}

$year  = date('Y');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if (empty($email) || empty($pass)) {
        $error = 'Completá todos los campos.';
    } else {
        try {
            $st = db()->prepare('SELECT id, nombre, password_hash, rol, activo FROM usuarios WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $user = $st->fetch();

            if ($user && $user['activo'] && password_verify($pass, $user['password_hash'])) {
                db()->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')->execute([$user['id']]);
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre']     = $user['nombre'];
                $_SESSION['rol']        = $user['rol'];

                $dest = ($user['rol'] === 'admin' || $user['rol'] === 'agente')
                    ? 'admin/dashboard.php' : 'cliente/dashboard.php';
                header("Location: $dest"); exit;
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión. Intentá más tarde.';
        }
    }
}

$email_val = htmlspecialchars($_POST['email'] ?? '');
$err_html  = $error
    ? '<div class="err"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>' . htmlspecialchars($error) . '</div>'
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Portal — Eight Technologies</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#f7f8fb;--white:#fff;--ink:#0c1222;--ink2:#1e2d45;--muted:#64748b;--m2:#94a3b8;--bd:#e2e8f0;--bd2:#cbd5e1;--blue:#1a5cff;--blues:rgba(26,92,255,.07);--bluem:rgba(26,92,255,.14);--sky:#0ea5e9;--red:#ef4444;--reds:rgba(239,68,68,.07);--f:'Plus Jakarta Sans',sans-serif;--ease:cubic-bezier(.16,1,.3,1)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px;-webkit-font-smoothing:antialiased}
body{font-family:var(--f);color:var(--ink);min-height:100svh;display:flex;flex-direction:column;background:var(--bg);position:relative;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;background-image:linear-gradient(#e2e8f0 1px,transparent 1px),linear-gradient(90deg,#e2e8f0 1px,transparent 1px);background-size:60px 60px;opacity:.55}
body::after{content:'';position:fixed;pointer-events:none;z-index:0;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(26,92,255,.06) 0%,transparent 65%);top:-180px;right:-180px}
a{color:inherit;text-decoration:none}
.top{position:relative;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:1.1rem 2rem;background:rgba(247,248,251,.85);backdrop-filter:blur(14px);border-bottom:1px solid #e2e8f0}
.logo{display:flex;align-items:center;gap:.7rem}
.lmark{width:32px;height:32px;border-radius:8px;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.95rem}
.lname{font-size:.92rem;font-weight:700;color:var(--ink)}
.lsub{font-size:.58rem;color:var(--muted);letter-spacing:.05em;text-transform:uppercase}
.back{font-size:.82rem;font-weight:500;color:var(--muted);display:flex;align-items:center;gap:.35rem;transition:color .2s}
.back:hover{color:var(--blue)}
.back svg{width:14px;height:14px}
.main{flex:1;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;padding:2.5rem 1.5rem}
.card{width:100%;max-width:400px;background:var(--white);border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 4px 6px rgba(12,18,34,.04),0 16px 48px rgba(12,18,34,.08);overflow:hidden}
.cbar{height:3px;background:linear-gradient(90deg,var(--blue) 0%,var(--sky) 100%)}
.cbody{padding:2.5rem 2.25rem}
.cico{width:46px;height:46px;border-radius:11px;background:var(--blues);border:1px solid var(--bluem);display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem}
.cico svg{width:21px;height:21px;color:var(--blue)}
.ctitle{font-size:1.45rem;font-weight:800;letter-spacing:-.03em;color:var(--ink);margin-bottom:.35rem}
.csub{font-size:.875rem;color:var(--muted);margin-bottom:2rem;line-height:1.6}
.err{display:flex;align-items:center;gap:.55rem;background:var(--reds);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:.7rem .9rem;font-size:.82rem;color:var(--red);margin-bottom:1.4rem}
.err svg{width:15px;height:15px;flex-shrink:0}
.fld{margin-bottom:1rem}
.lbl{display:block;font-size:.77rem;font-weight:600;color:var(--ink2);margin-bottom:.4rem}
.inp{width:100%;padding:.78rem .95rem;border:1.5px solid #e2e8f0;border-radius:8px;font-family:var(--f);font-size:.88rem;color:var(--ink);background:#f7f8fb;outline:none;transition:border-color .2s,box-shadow .2s,background .2s}
.inp::placeholder{color:var(--m2)}
.inp:focus{border-color:var(--blue);background:var(--white);box-shadow:0 0 0 3px var(--blues)}
.inp.ef{border-color:var(--red)}
.pwr{position:relative}
.pwe{position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:.2rem;color:var(--m2);display:flex;align-items:center;transition:color .2s}
.pwe:hover{color:var(--ink2)}
.pwe svg{width:16px;height:16px}
.fr{display:flex;justify-content:flex-end;margin-top:-.2rem;margin-bottom:1rem}
.fr a{font-size:.77rem;color:var(--blue);font-weight:500}
.fr a:hover{color:#1248d6}
.bsub{width:100%;padding:.84rem;background:var(--blue);color:#fff;border:none;border-radius:8px;font-family:var(--f);font-size:.88rem;font-weight:700;cursor:pointer;transition:all .22s var(--ease);display:flex;align-items:center;justify-content:center;gap:.5rem}
.bsub:hover{background:#1248d6;transform:translateY(-1px);box-shadow:0 8px 24px rgba(26,92,255,.28)}
.bsub svg{width:15px;height:15px}
.sep{display:flex;align-items:center;gap:.65rem;font-size:.73rem;color:var(--m2);margin:.8rem 0}
.sep::before,.sep::after{content:'';flex:1;height:1px;background:#e2e8f0}
.bguest{width:100%;padding:.78rem;background:transparent;color:var(--ink2);border:1.5px solid #cbd5e1;border-radius:8px;font-family:var(--f);font-size:.85rem;font-weight:600;cursor:pointer;transition:all .22s var(--ease);display:flex;align-items:center;justify-content:center;gap:.45rem;text-decoration:none}
.bguest:hover{border-color:var(--blue);color:var(--blue);background:var(--blues)}
.bguest svg{width:14px;height:14px}
.cfoot{margin-top:1.6rem;padding-top:1.4rem;border-top:1px solid #e2e8f0;text-align:center;font-size:.78rem;color:var(--muted)}
.cfoot a{color:var(--blue);font-weight:500}
.chips{position:relative;z-index:1;display:flex;justify-content:center;gap:.5rem;flex-wrap:wrap;padding:.6rem 1.5rem 2rem}
.chip{display:inline-flex;align-items:center;gap:.35rem;font-size:.71rem;font-weight:500;color:var(--muted);background:var(--white);border:1px solid #e2e8f0;border-radius:999px;padding:.28rem .8rem}
.chip svg{width:11px;height:11px;color:var(--blue)}
.foot{position:relative;z-index:1;border-top:1px solid #e2e8f0;background:var(--white);padding:.85rem 2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;font-size:.74rem;color:var(--m2)}
.foot a{color:var(--muted);transition:color .2s}
.foot a:hover{color:var(--blue)}
.fcr{display:flex;align-items:center;gap:.4rem}
.fcr::before{content:'';width:5px;height:5px;border-radius:50%;background:var(--sky);box-shadow:0 0 5px var(--sky)}
</style>
</head>
<body>

<div class="top">
  <a href="index.php" class="logo">
    <div class="lmark">8T</div>
    <div><div class="lname">Eight Technologies</div><div class="lsub">Portal de Soporte</div></div>
  </a>
  <a href="index.php" class="back">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
    Volver al sitio
  </a>
</div>

<div class="main">
  <div class="card">
    <div class="cbar"></div>
    <div class="cbody">
      <div class="cico">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
      </div>
      <div class="ctitle">Iniciar sesión</div>
      <div class="csub">Accedé al portal para gestionar tus tickets de soporte.</div>
      <?= $err_html ?>
      <form method="POST" action="portal.php" novalidate id="lf">
        <div class="fld">
          <label class="lbl" for="email">Correo electrónico</label>
          <input class="inp" type="email" id="email" name="email" placeholder="tu@empresa.com" value="<?= $email_val ?>" autocomplete="email" required/>
        </div>
        <div class="fld">
          <label class="lbl" for="pw">Contraseña</label>
          <div class="pwr">
            <input class="inp" type="password" id="pw" name="password" placeholder="••••••••" autocomplete="current-password" required style="padding-right:2.4rem"/>
            <button type="button" class="pwe" id="pwBtn">
              <svg id="eA" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <svg id="eB" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
            </button>
          </div>
        </div>
        <div class="fr"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></div>
        <button type="submit" class="bsub">
          Ingresar
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
        </button>
      </form>
      <div class="sep">o</div>
      <a href="contacto.php" class="bguest">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
        Enviar solicitud sin cuenta
      </a>
      <div class="cfoot">¿No tenés acceso? <a href="contacto.php">Contactá a soporte</a></div>
    </div>
  </div>
</div>

<div class="chips">
  <div class="chip"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Seguimiento en tiempo real</div>
  <div class="chip"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>Historial completo</div>
  <div class="chip"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>Notificaciones por correo</div>
  <div class="chip"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Soporte 24/7</div>
</div>

<footer class="foot">
  <span>© <?= $year ?> Eight Technologies</span>
  <span class="fcr">Soporte disponible 24/7</span>
  <span><a href="privacidad.php">Privacidad</a> &ensp;·&ensp; <a href="index.php">Inicio</a></span>
</footer>

<script>
(function(){
  const b=document.getElementById('pwBtn'),p=document.getElementById('pw'),eA=document.getElementById('eA'),eB=document.getElementById('eB');
  b.addEventListener('click',()=>{const s=p.type==='password';p.type=s?'text':'password';eA.style.display=s?'none':'block';eB.style.display=s?'block':'none';});
  document.getElementById('lf').addEventListener('submit',e=>{
    const em=document.getElementById('email'),pw=document.getElementById('pw');let ok=true;
    [em,pw].forEach(f=>f.classList.remove('ef'));
    if(!em.value.trim()||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value)){em.classList.add('ef');ok=false;}
    if(!pw.value.trim()){pw.classList.add('ef');ok=false;}
    if(!ok)e.preventDefault();
  });
})();
</script>
</body>
</html>
