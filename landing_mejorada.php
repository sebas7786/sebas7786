<?php include 'includes/header.php'; ?>

<style>
/* ===================================
   VARIABLES - Paleta minimalista
   =================================== */
:root {
    /* Colores principales - Escala de grises + acento azul */
    --primary: #1e293b;
    --primary-light: #334155;
    --accent: #3b82f6;
    --accent-hover: #2563eb;

    /* Grises */
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;

    --white: #ffffff;
    --text-dark: #1e293b;
    --text-muted: #64748b;

    /* Sombras sutiles */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);

    /* Bordes */
    --radius: 0.5rem;
    --radius-lg: 0.75rem;

    /* Transiciones */
    --transition: all 0.2s ease;
}

/* ===================================
   ESTILOS GENERALES
   =================================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: var(--text-dark);
    line-height: 1.6;
    background-color: var(--white);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* ===================================
   TIPOGRAFÍA
   =================================== */
h1, h2, h3, h4, h5, h6 {
    font-weight: 600;
    line-height: 1.2;
    color: var(--primary);
}

h1 { font-size: 2.5rem; margin-bottom: 1rem; }
h2 { font-size: 2rem; margin-bottom: 1rem; }
h3 { font-size: 1.5rem; margin-bottom: 0.75rem; }

p {
    margin-bottom: 1rem;
    color: var(--text-muted);
}

/* ===================================
   BOTONES
   =================================== */
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    cursor: pointer;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background-color: var(--accent);
    color: var(--white);
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    background-color: var(--accent-hover);
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: var(--white);
    color: var(--text-dark);
    border: 1px solid var(--gray-300);
}

.btn-secondary:hover {
    background-color: var(--gray-50);
    border-color: var(--gray-400);
}

/* ===================================
   HERO SECTION
   =================================== */
.hero {
    background: linear-gradient(to bottom, var(--white) 0%, var(--gray-50) 100%);
    padding: 5rem 0;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: var(--gray-100);
    color: var(--text-muted);
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    margin-bottom: 1.5rem;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--primary);
    line-height: 1.1;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--text-muted);
    margin-bottom: 2rem;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.hero-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 3rem;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    max-width: 700px;
    margin: 3rem auto 0;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);
}

.hero-stat {
    text-align: center;
}

.hero-stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.hero-stat-label {
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* ===================================
   SECCIONES
   =================================== */
section {
    padding: 5rem 0;
}

section h2 {
    text-align: center;
    margin-bottom: 3rem;
}

/* ===================================
   CÓMO FUNCIONA
   =================================== */
.how-it-works {
    background-color: var(--white);
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
}

.step-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 2rem;
    transition: var(--transition);
    position: relative;
}

.step-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--gray-300);
}

.step-number {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--gray-200);
    line-height: 1;
}

.step-icon {
    width: 60px;
    height: 60px;
    background-color: var(--gray-100);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.step-icon i {
    font-size: 1.5rem;
    color: var(--accent);
}

.step-card h3 {
    margin-bottom: 0.75rem;
}

.step-card p {
    margin: 0;
}

/* ===================================
   BENEFICIOS
   =================================== */
.benefits {
    background-color: var(--gray-50);
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
}

.benefit-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 2rem;
    transition: var(--transition);
}

.benefit-card:hover {
    box-shadow: var(--shadow);
    border-color: var(--gray-300);
}

.benefit-card i {
    font-size: 2rem;
    color: var(--accent);
    margin-bottom: 1rem;
}

.benefit-card h3 {
    margin-bottom: 0.75rem;
}

.benefit-card p {
    margin: 0;
}

/* ===================================
   CTA SECTION
   =================================== */
.cta-section {
    background-color: var(--primary);
    color: var(--white);
    text-align: center;
    padding: 4rem 0;
}

.cta-section h2 {
    color: var(--white);
    margin-bottom: 1rem;
}

.cta-section p {
    color: var(--gray-300);
    font-size: 1.125rem;
    max-width: 600px;
    margin: 0 auto 2rem;
}

/* ===================================
   CONTACTO
   =================================== */
.contact {
    background-color: var(--white);
}

.contact-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
    margin-top: 3rem;
}

.contact-form {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    font-family: inherit;
    font-size: 1rem;
    transition: var(--transition);
    background-color: var(--white);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.contact-info {
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    padding: 2rem;
    height: fit-content;
}

.contact-info h3 {
    margin-bottom: 1.5rem;
}

.contact-info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    color: var(--text-muted);
}

.contact-info-item i {
    width: 24px;
    text-align: center;
    color: var(--accent);
}

.social-links {
    display: flex;
    gap: 0.75rem;
    margin-top: 2rem;
}

.social-links a {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--white);
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    color: var(--text-muted);
    transition: var(--transition);
}

.social-links a:hover {
    background-color: var(--accent);
    border-color: var(--accent);
    color: var(--white);
}

/* ===================================
   RESPONSIVE
   =================================== */
@media (max-width: 992px) {
    .hero-title {
        font-size: 2.5rem;
    }

    .steps-grid,
    .benefits-grid {
        grid-template-columns: 1fr;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .contact-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    section {
        padding: 3rem 0;
    }

    .hero {
        padding: 3rem 0;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-buttons {
        flex-direction: column;
    }

    .hero-stats {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    h2 {
        font-size: 1.75rem;
    }
}

/* ===================================
   ANIMACIONES SUTILES
   =================================== */
section {
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

section:nth-child(1) { animation-delay: 0.1s; }
section:nth-child(2) { animation-delay: 0.2s; }
section:nth-child(3) { animation-delay: 0.3s; }
section:nth-child(4) { animation-delay: 0.4s; }
section:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-bolt"></i>
                Sistema inteligente de alertas
            </div>

            <h1 class="hero-title">Recibí alertas de licitaciones públicas según tus intereses</h1>

            <p class="hero-subtitle">
                Sin perder tiempo buscando en páginas. Optimizamos el proceso para que te enfoques en lo que realmente importa.
            </p>

            <div class="hero-buttons">
                <a href="#contacto" class="btn btn-primary">Solicitar acceso</a>
                <a href="#como-funciona" class="btn btn-secondary">Conocer más</a>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-number">+150</div>
                    <div class="hero-stat-label">Licitaciones diarias</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-number">98%</div>
                    <div class="hero-stat-label">Precisión</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-number">+50</div>
                    <div class="hero-stat-label">Empresas confían</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CÓMO FUNCIONA -->
<section id="como-funciona" class="how-it-works">
    <div class="container">
        <h2>Proceso optimizado</h2>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <div class="step-icon">
                    <i class="fas fa-list-check"></i>
                </div>
                <h3>Seleccioná tus intereses</h3>
                <p>Configura tu perfil con las categorías específicas que son relevantes para tu empresa y actividad comercial.</p>
            </div>

            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Recibí alertas filtradas</h3>
                <p>Nuestro sistema analiza y filtra automáticamente las licitaciones, enviándote solo las que cumplen con tus criterios.</p>
            </div>

            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Participá en licitaciones</h3>
                <p>Accede a oportunidades de negocio de forma eficiente, maximizando tus posibilidades de éxito en el sector público.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section id="beneficios" class="benefits">
    <div class="container">
        <h2>Ventajas competitivas</h2>

        <div class="benefits-grid">
            <div class="benefit-card">
                <i class="fas fa-clock"></i>
                <h3>Eficiencia operativa</h3>
                <p>Automatizamos el proceso de búsqueda y filtrado, permitiéndote dedicar recursos a la preparación de ofertas competitivas.</p>
            </div>

            <div class="benefit-card">
                <i class="fas fa-filter"></i>
                <h3>Filtrado inteligente</h3>
                <p>Algoritmos avanzados que identifican y clasifican licitaciones según relevancia, presupuesto y probabilidad de adjudicación.</p>
            </div>

            <div class="benefit-card">
                <i class="fas fa-bell"></i>
                <h3>Alertas en tiempo real</h3>
                <p>Recibe notificaciones instantáneas cuando se publiquen nuevas oportunidades que coincidan con tu perfil empresarial.</p>
            </div>

            <div class="benefit-card">
                <i class="fas fa-chart-simple"></i>
                <h3>Interfaz analítica</h3>
                <p>Panel de control intuitivo con métricas y estadísticas para evaluar oportunidades y optimizar tu estrategia de licitación.</p>
            </div>

            <div class="benefit-card">
                <i class="fas fa-robot"></i>
                <h3>Tecnología avanzada</h3>
                <p>Implementamos inteligencia artificial para mejorar continuamente la precisión de las recomendaciones según tus intereses.</p>
            </div>

            <div class="benefit-card">
                <i class="fas fa-briefcase"></i>
                <h3>Solución empresarial</h3>
                <p>Diseñado específicamente para organizaciones que buscan expandir su presencia en el mercado de contratación pública.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Optimiza tu estrategia de licitaciones</h2>
        <p>Solicita acceso a nuestra plataforma y transforma tu enfoque en contratación pública.</p>
        <a href="#contacto" class="btn btn-primary">Solicitar demostración</a>
    </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="contact">
    <div class="container">
        <h2>Contacto profesional</h2>

        <div class="contact-grid">
            <div class="contact-form">
                <form id="contactForm" action="/process_contact.php" method="POST">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="empresa">Empresa</label>
                        <input type="text" id="empresa" name="empresa" required>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono">
                    </div>

                    <div class="form-group">
                        <label for="intereses">Intereses en licitaciones</label>
                        <textarea id="intereses" name="intereses" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="mensaje">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                </form>
            </div>

            <div class="contact-info">
                <h3>Información de contacto</h3>

                <div class="contact-info-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@licitacionesya.com</span>
                </div>

                <div class="contact-info-item">
                    <i class="fas fa-phone"></i>
                    <span>+506 6382-5225</span>
                </div>

                <div class="contact-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Alajuela, Costa Rica</span>
                </div>

                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Validación del formulario
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre');
    const empresa = document.getElementById('empresa');
    const correo = document.getElementById('correo');
    const intereses = document.getElementById('intereses');

    let valid = true;

    // Validar campos
    [nombre, empresa, correo, intereses].forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#ef4444';
            valid = false;
        } else {
            field.style.borderColor = '';
        }
    });

    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo.value)) {
        correo.style.borderColor = '#ef4444';
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        alert('Por favor, complete todos los campos obligatorios correctamente.');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
