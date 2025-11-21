<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
init_session();

// Verificar validez de la sesión si el usuario está logueado
if (is_logged_in() && function_exists('check_session_validity')) {
    check_session_validity($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SICOP Alertas - Sistema de Alertas de Licitaciones Públicas</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos para el header */
        :root {
          --primary-color: #4e73df;
          --primary-dark: #2e59d9;
          --primary-light: #e8eeff;
          --secondary-color: #858796;
          --success-color: #1cc88a;
          --info-color: #36b9cc;
          --warning-color: #f6c23e;
          --danger-color: #e74a3b;
          --light-color: #f8f9fc;
          --dark-color: #5a5c69;
          --white: #fff;
          --gray-100: #f8f9fc;
          --gray-200: #eaecf4;
          --gray-300: #dddfeb;
          --gray-400: #d1d3e2;
          --gray-500: #b7b9cc;
          --gray-600: #858796;
          --gray-700: #6e707e;
          --gray-800: #5a5c69;
          --gray-900: #3a3b45;
        }

        /* Estilos generales del header */
        header {
          background-color: var(--white);
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
          position: sticky;
          top: 0;
          z-index: 1000;
          padding: 1rem 0;
          border-bottom: 1px solid var(--gray-200);
        }

        header .container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 1rem;
        }

        /* Estilos del logo */
        .logo a {
          font-size: 1.5rem;
          font-weight: 700;
          color: var(--primary-color);
          text-decoration: none;
          display: flex;
          align-items: center;
          transition: color 0.3s ease;
        }

        .logo a:hover {
          color: var(--primary-dark);
        }

        .logo a::before {
          content: "\f0e7";
          font-family: "Font Awesome 6 Free";
          margin-right: 0.5rem;
          font-size: 1.2rem;
        }

        /* Estilos de la navegación */
        nav ul {
          display: flex;
          list-style: none;
          margin: 0;
          padding: 0;
          align-items: center;
        }

        nav ul li {
          margin-left: 1.5rem;
          position: relative;
        }

        nav ul li a {
          color: var(--gray-700);
          text-decoration: none;
          font-weight: 600;
          font-size: 0.95rem;
          padding: 0.5rem 0;
          transition: color 0.3s ease;
          display: block;
          position: relative;
        }

        nav ul li a::after {
          content: "";
          position: absolute;
          bottom: 0;
          left: 0;
          width: 0;
          height: 2px;
          background-color: var(--primary-color);
          transition: width 0.3s ease;
        }

        nav ul li a:hover {
          color: var(--primary-color);
        }

        nav ul li a:hover::after {
          width: 100%;
        }

        /* Estilo para el botón primario */
        .btn-primary {
          background-color: var(--primary-color);
          color: var(--white) !important;
          padding: 0.5rem 1rem;
          border-radius: 4px;
          transition: background-color 0.3s ease, transform 0.2s ease;
          box-shadow: 0 2px 4px rgba(78, 115, 223, 0.2);
        }

        .btn-primary:hover {
          background-color: var(--primary-dark);
          transform: translateY(-2px);
          box-shadow: 0 4px 6px rgba(78, 115, 223, 0.25);
        }

        .btn-primary::after {
          display: none;
        }

        /* Botón de IA especial */
        .btn-ai {
          background: linear-gradient(135deg, #8a5cf5, #5e72e4);
          color: var(--white) !important;
          padding: 0.5rem 1.2rem;
          border-radius: 50px;
          transition: all 0.3s ease;
          box-shadow: 0 4px 10px rgba(138, 92, 245, 0.3);
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          font-weight: 700;
          animation: pulse 2s infinite;
        }

        .btn-ai:hover {
          background: linear-gradient(135deg, #7a4ce5, #4e62d4);
          transform: translateY(-3px);
          box-shadow: 0 6px 15px rgba(138, 92, 245, 0.4);
        }

        .btn-ai::after {
          display: none;
        }

        .btn-ai i {
          font-size: 1.1rem;
        }

        @keyframes pulse {
          0%, 100% {
            box-shadow: 0 4px 10px rgba(138, 92, 245, 0.3);
          }
          50% {
            box-shadow: 0 4px 15px rgba(138, 92, 245, 0.5);
          }
        }

        /* Estilos para el dropdown */
        .dropdown {
          position: relative;
          display: inline-block;
        }

        .dropdown-toggle {
          background-color: var(--info-color);
          color: var(--white) !important;
          padding: 0.5rem 1rem;
          border-radius: 4px;
          transition: background-color 0.3s ease, transform 0.2s ease;
          box-shadow: 0 2px 4px rgba(54, 185, 204, 0.2);
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          border: none;
          font-weight: 600;
          font-size: 0.95rem;
        }

        .dropdown-toggle:hover {
          background-color: #2ba9bb;
          transform: translateY(-2px);
          box-shadow: 0 4px 6px rgba(54, 185, 204, 0.25);
        }

        .dropdown-toggle::after {
          display: none !important;
        }

        .dropdown-toggle i.fa-caret-down {
          transition: transform 0.3s ease;
        }

        .dropdown.active .dropdown-toggle i.fa-caret-down {
          transform: rotate(180deg);
        }

        .dropdown-menu {
          position: absolute;
          top: calc(100% + 0.5rem);
          left: 0;
          background-color: var(--white);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
          border-radius: 6px;
          min-width: 250px;
          opacity: 0;
          visibility: hidden;
          transform: translateY(-10px);
          transition: all 0.3s ease;
          z-index: 1000;
          overflow: hidden;
        }

        .dropdown.active .dropdown-menu {
          opacity: 1;
          visibility: visible;
          transform: translateY(0);
        }

        .dropdown-menu a {
          display: block;
          padding: 0.75rem 1rem;
          color: var(--gray-700);
          text-decoration: none;
          transition: all 0.2s ease;
          border-bottom: 1px solid var(--gray-200);
          font-weight: 500;
        }

        .dropdown-menu a:last-child {
          border-bottom: none;
        }

        .dropdown-menu a:hover {
          background-color: var(--light-color);
          color: var(--primary-color);
          padding-left: 1.25rem;
        }

        .dropdown-menu a i {
          margin-right: 0.5rem;
          width: 20px;
          text-align: center;
        }

        /* Botón de menú móvil */
        .mobile-menu-toggle {
          display: none;
          background: none;
          border: none;
          font-size: 1.5rem;
          color: var(--primary-color);
          cursor: pointer;
        }

        /* Estilos responsivos */
        @media (max-width: 768px) {
          .mobile-menu-toggle {
            display: block;
          }

          nav {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: var(--white);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            z-index: 1000;
          }

          nav.active {
            max-height: 600px;
            overflow-y: auto;
          }

          nav ul {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
            padding: 0.5rem 0;
          }

          nav ul li {
            margin: 0;
            width: 100%;
            border-top: 1px solid var(--gray-200);
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            transition-delay: calc(0.05s * var(--item-index, 0));
          }

          nav.active ul li {
            opacity: 1;
            transform: translateY(0);
          }

          nav ul li a {
            padding: 0.75rem 1rem;
            display: block;
            width: 100%;
          }

          .btn-primary, .btn-ai {
            display: inline-block;
            margin: 0.5rem 1rem;
          }

          /* Dropdown en móvil */
          .dropdown {
            width: 100%;
          }

          .dropdown-toggle {
            width: 100%;
            justify-content: space-between;
            margin: 0.5rem 1rem;
            width: calc(100% - 2rem);
          }

          .dropdown-menu {
            position: static;
            transform: none;
            box-shadow: none;
            border-left: 3px solid var(--info-color);
            margin-left: 1rem;
            width: calc(100% - 2rem);
          }

          .dropdown-menu a {
            padding-left: 2rem;
          }

          .dropdown-menu a:hover {
            padding-left: 2.25rem;
          }
        }

        /* Animación para el menú móvil */
        @keyframes fadeIn {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="/">LicitacionesYA</a>
            </div>
            <button class="mobile-menu-toggle" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </button>
            <nav>
                <ul>
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li><a href="/admin/dashboard.php">Panel Admin</a></li>
                            <li><a href="/admin/licitaciones_vencidas.php"><i class="fas fa-clock me-1"></i> Licitaciones Vencidas</a></li>
                            <li><a href="/admin/estadisticas_adjudicaciones.php"><i class="fas fa-chart-bar me-1"></i> Estadísticas</a></li>

                            <!-- Dropdown de Importadores -->
                            <li class="dropdown">
                                <button class="dropdown-toggle">
                                    <i class="fas fa-file-import"></i>
                                    Importadores
                                    <i class="fas fa-caret-down"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="/admin/importador_aclaraciones.php">
                                        <i class="fas fa-comments"></i>
                                        Importar Aclaraciones
                                    </a>
                                    <a href="/admin/importador_sicop_completo.php">
                                        <i class="fas fa-database"></i>
                                        Importador SICOP Completo
                                    </a>
                                    <a href="/admin/importar_lineas_adjudicadas.php">
                                        <i class="fas fa-award"></i>
                                        Importar Líneas Adjudicadas
                                    </a>
                                </div>
                            </li>

                        <?php else: ?>
                            <li><a href="/user/dashboard.php">Mis Licitaciones</a></li>
                            <li><a href="/user/profile.php">Mi Perfil</a></li>
                            <li><a href="/user/estadisticas_ganadores.php"><i class="fas fa-trophy me-1"></i> Estadísticas Ganadores</a></li>

                        <?php endif; ?>

                        <!-- ⭐ NUEVO BOTÓN DE IA -->
                        <li><a href="/user/lici-ai.php" class="btn-ai"><i class="fas fa-brain"></i> Lici IA</a></li>

                        <li><a href="/logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="/login.php">Iniciar Sesión</a></li>
                        <li><a href="/#contacto" class="btn-primary">Solicitar Información</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const nav = document.querySelector('nav');

            if (menuToggle && nav) {
                menuToggle.addEventListener('click', function() {
                    nav.classList.toggle('active');

                    // Añadir índices para la animación escalonada
                    const items = nav.querySelectorAll('li');
                    items.forEach((item, index) => {
                        item.style.setProperty('--item-index', index);
                    });
                });
            }

            // Manejar dropdown
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const dropdown = this.closest('.dropdown');
                    const isActive = dropdown.classList.contains('active');

                    // Cerrar todos los dropdowns
                    document.querySelectorAll('.dropdown').forEach(d => {
                        d.classList.remove('active');
                    });

                    // Abrir el dropdown actual si no estaba activo
                    if (!isActive) {
                        dropdown.classList.add('active');
                    }
                });
            });

            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });

            // Prevenir que el dropdown se cierre al hacer click dentro del menú
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>

    <main>
