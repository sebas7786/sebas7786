<?php
// Usage: include '_layout.php'; then echo layout_head('Page Title'); ... echo layout_foot();
function layout_head(string $title, string $active = ''): string {
    $user = htmlspecialchars($_SESSION['nombre']);
    $rol  = $_SESSION['rol'];
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>{$title} — Eight Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#f1f5f9;--white:#fff;--ink:#0c1222;--ink2:#1e2d45;--muted:#64748b;--m2:#94a3b8;--bd:#e2e8f0;--blue:#1a5cff;--blues:rgba(26,92,255,.07);--sky:#0ea5e9;--green:#22c55e;--red:#ef4444;--orange:#f97316;--f:'Plus Jakarta Sans',sans-serif;--sidebar:240px}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:15px;-webkit-font-smoothing:antialiased}
body{font-family:var(--f);color:var(--ink);background:var(--bg);display:flex;min-height:100vh}
a{color:inherit;text-decoration:none}
/* Sidebar */
.sb{width:var(--sidebar);background:var(--ink2);color:#e2e8f0;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
.sb-logo{display:flex;align-items:center;gap:.6rem;padding:1.25rem 1.25rem 1rem;border-bottom:1px solid rgba(255,255,255,.08)}
.sb-lmark{width:32px;height:32px;border-radius:8px;background:var(--blue);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;color:#fff;flex-shrink:0}
.sb-lname{font-size:.82rem;font-weight:700;color:#f1f5f9}
.sb-lsub{font-size:.58rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em}
.sb-nav{flex:1;overflow-y:auto;padding:.75rem 0}
.sb-section{font-size:.6rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.08em;padding:.9rem 1.25rem .3rem}
.sb-link{display:flex;align-items:center;gap:.65rem;padding:.6rem 1.25rem;font-size:.82rem;font-weight:500;color:#94a3b8;transition:background .15s,color .15s;border-radius:0}
.sb-link svg{width:16px;height:16px;flex-shrink:0}
.sb-link:hover{background:rgba(255,255,255,.06);color:#e2e8f0}
.sb-link.active{background:rgba(26,92,255,.18);color:#7ca3ff;border-right:2px solid var(--blue)}
.sb-foot{padding:.9rem 1.25rem;border-top:1px solid rgba(255,255,255,.08);font-size:.75rem;color:#64748b}
.sb-user{font-size:.78rem;color:#94a3b8;margin-bottom:.5rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-out{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;color:#ef4444;font-weight:500;transition:opacity .2s}
.sb-out:hover{opacity:.75}
/* Main */
.wrap{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh}
.topbar{background:var(--white);border-bottom:1px solid var(--bd);padding:.85rem 2rem;display:flex;align-items:center;justify-content:space-between}
.topbar-title{font-size:1rem;font-weight:700;color:var(--ink)}
.topbar-sub{font-size:.75rem;color:var(--muted);margin-top:.1rem}
.content{flex:1;padding:2rem}
/* Cards */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:1rem;margin-bottom:2rem}
.stat{background:var(--white);border:1px solid var(--bd);border-radius:12px;padding:1.25rem;display:flex;flex-direction:column;gap:.5rem}
.stat-label{font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.stat-val{font-size:1.9rem;font-weight:800;color:var(--ink);line-height:1}
.stat-sub{font-size:.72rem;color:var(--muted)}
.stat-ico{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;margin-bottom:.25rem}
.stat-ico svg{width:17px;height:17px}
/* Table */
.card{background:var(--white);border:1px solid var(--bd);border-radius:12px;overflow:hidden}
.card-head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--bd)}
.card-title{font-size:.88rem;font-weight:700}
.tbl{width:100%;border-collapse:collapse;font-size:.82rem}
.tbl th{padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--bd);background:#f8fafc}
.tbl td{padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#f8fafc}
/* Badges */
.badge{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .6rem;border-radius:999px;font-size:.68rem;font-weight:600}
.badge-green{background:#dcfce7;color:#15803d}
.badge-red{background:#fee2e2;color:#b91c1c}
.badge-blue{background:#dbeafe;color:#1d4ed8}
.badge-orange{background:#ffedd5;color:#c2410c}
.badge-gray{background:#f1f5f9;color:#475569}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.52rem 1rem;border-radius:7px;font-family:var(--f);font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:all .18s}
.btn-primary{background:var(--blue);color:#fff}.btn-primary:hover{background:#1248d6}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--bd)}.btn-ghost:hover{border-color:var(--blue);color:var(--blue)}
.btn-danger{background:transparent;color:var(--red);border:1px solid #fecaca}.btn-danger:hover{background:#fee2e2}
.btn-sm{padding:.35rem .7rem;font-size:.74rem}
.btn svg{width:13px;height:13px}
/* Form */
.fgrid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fld{display:flex;flex-direction:column;gap:.35rem}
.fld label{font-size:.75rem;font-weight:600;color:var(--ink2)}
.inp,.sel,.ta{width:100%;padding:.65rem .9rem;border:1.5px solid var(--bd);border-radius:7px;font-family:var(--f);font-size:.84rem;color:var(--ink);background:#f8fafc;outline:none;transition:border-color .18s,box-shadow .18s}
.inp:focus,.sel:focus,.ta:focus{border-color:var(--blue);background:var(--white);box-shadow:0 0 0 3px var(--blues)}
.ta{resize:vertical;min-height:90px}
/* Modal */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:center;justify-content:center}
.overlay.open{display:flex}
.modal{background:var(--white);border-radius:14px;padding:2rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem}
.modal-title{font-size:1.05rem;font-weight:800}
.modal-x{background:none;border:none;cursor:pointer;color:var(--muted);font-size:1.2rem;line-height:1;padding:.2rem}
/* Alerts */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.82rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem}
.alert-ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.alert-err{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
</style>
</head>
<body>
<aside class="sb">
  <div class="sb-logo">
    <div class="sb-lmark">8T</div>
    <div><div class="sb-lname">Eight Admin</div><div class="sb-lsub">Panel de control</div></div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Principal</div>
    <a href="dashboard.php" class="sb-link {$active === 'dashboard' ? 'active' : ''}">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
      Dashboard
    </a>
    <div class="sb-section">Soporte</div>
    <a href="tickets.php" class="sb-link {$active === 'tickets' ? 'active' : ''}">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/></svg>
      Tickets
    </a>
    <div class="sb-section">Gestión</div>
    <a href="clientes.php" class="sb-link {$active === 'clientes' ? 'active' : ''}">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
      Clientes
    </a>
    <a href="usuarios.php" class="sb-link {$active === 'usuarios' ? 'active' : ''}">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
      Usuarios
    </a>
  </nav>
  <div class="sb-foot">
    <div class="sb-user">👤 {$user} <span style="font-size:.6rem;background:rgba(26,92,255,.2);color:#7ca3ff;padding:.1rem .4rem;border-radius:4px">{$rol}</span></div>
    <a href="../logout.php" class="sb-out">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
      Cerrar sesión
    </a>
  </div>
</aside>
HTML;
}

function layout_foot(): string {
    return '</div></body></html>';
}
