<?php include 'includes/header.php'; ?>

<style>
/* ===================================
   DISEÑO MODERNO CON GLASSMORPHISM
   =================================== */
:root {
    --primary: #0a0e27;
    --primary-light: #151a35;
    --accent-cyan: #00d4ff;
    --accent-purple: #7c3aed;
    --accent-pink: #ec4899;
    --white: #ffffff;
    --gray-light: #f1f5f9;
    --text-muted: #94a3b8;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: #1e293b;
    overflow-x: hidden;
    background: var(--primary);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* ===================================
   HERO CON FONDO ANIMADO
   =================================== */
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
}

/* Fondo animado con partículas */
.hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: 0.3;
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, var(--accent-cyan), transparent);
    top: -200px;
    left: -200px;
    animation-delay: 0s;
}

.orb-2 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, var(--accent-purple), transparent);
    top: 50%;
    right: -150px;
    animation-delay: 5s;
}

.orb-3 {
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--accent-pink), transparent);
    bottom: -100px;
    left: 30%;
    animation-delay: 10s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(50px, -50px) scale(1.1); }
    66% { transform: translate(-50px, 50px) scale(0.9); }
}

/* Grid animado */
.grid-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    background-image:
        linear-gradient(rgba(0, 212, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 212, 255, 0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50px, 50px); }
}

/* Contenido del hero */
.hero-content {
    position: relative;
    z-index: 10;
    text-align: center;
    color: var(--white);
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1.25rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 2rem;
    animation: slideDown 0.8s ease-out;
}

.hero-badge i {
    color: var(--accent-cyan);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-title {
    font-size: 4rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, var(--white), var(--accent-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: slideUp 0.8s ease-out 0.2s both;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--text-muted);
    max-width: 700px;
    margin: 0 auto 2.5rem;
    animation: slideUp 0.8s ease-out 0.4s both;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    animation: slideUp 0.8s ease-out 0.6s both;
}

/* Botones con efectos modernos */
.btn {
    padding: 1rem 2rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
    color: var(--white);
    border: none;
    box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(0, 212, 255, 0.4);
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: var(--white);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-3px);
}

/* Stats con glassmorphism */
.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    max-width: 900px;
    margin: 4rem auto 0;
    animation: slideUp 0.8s ease-out 0.8s both;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    padding: 2rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-5px);
    border-color: var(--accent-cyan);
    box-shadow: 0 10px 30px rgba(0, 212, 255, 0.2);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
}

/* ===================================
   SECCIÓN CÓMO FUNCIONA
   =================================== */
.how-it-works {
    padding: 8rem 0;
    background: var(--white);
    position: relative;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-subtitle {
    color: var(--accent-cyan);
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 1rem;
}

.section-description {
    font-size: 1.125rem;
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto;
}

/* Steps con timeline */
.steps-timeline {
    position: relative;
    max-width: 900px;
    margin: 0 auto;
}

.timeline-line {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--accent-cyan), var(--accent-purple));
    transform: translateX(-50%);
}

.step-item {
    display: flex;
    align-items: center;
    margin-bottom: 4rem;
    position: relative;
}

.step-item:nth-child(even) {
    flex-direction: row-reverse;
}

.step-content {
    flex: 1;
    padding: 0 3rem;
}

.step-item:nth-child(even) .step-content {
    text-align: right;
}

.step-icon-wrapper {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
}

.step-icon-wrapper i {
    font-size: 2rem;
    color: var(--white);
}

.step-number {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 35px;
    height: 35px;
    background: var(--white);
    border: 3px solid var(--accent-purple);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    color: var(--primary);
}

.step-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0.75rem;
}

.step-description {
    color: var(--text-muted);
    line-height: 1.6;
}

/* ===================================
   BENEFICIOS CON CARDS 3D
   =================================== */
.benefits {
    padding: 8rem 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    position: relative;
    overflow: hidden;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 4rem;
}

.benefit-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    padding: 2.5rem;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.benefit-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-cyan), var(--accent-purple));
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.benefit-card:hover::before {
    transform: scaleX(1);
}

.benefit-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.benefit-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(124, 58, 237, 0.2));
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    transition: all 0.4s ease;
}

.benefit-card:hover .benefit-icon {
    transform: scale(1.1) rotate(5deg);
}

.benefit-icon i {
    font-size: 2rem;
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.benefit-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--white);
    margin-bottom: 0.75rem;
}

.benefit-description {
    color: var(--text-muted);
    line-height: 1.6;
}

/* ===================================
   CTA CON EFECTO PARALLAX
   =================================== */
.cta-section {
    padding: 8rem 0;
    background: var(--white);
    position: relative;
    overflow: hidden;
}

.cta-content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.cta-box {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 2rem;
    padding: 5rem 3rem;
    position: relative;
    overflow: hidden;
}

.cta-box::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 212, 255, 0.1), transparent);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--white);
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
}

.cta-description {
    font-size: 1.125rem;
    color: var(--text-muted);
    margin-bottom: 2rem;
    position: relative;
    z-index: 2;
}

/* ===================================
   FORMULARIO MODERNO
   =================================== */
.contact {
    padding: 8rem 0;
    background: var(--gray-light);
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-top: 4rem;
}

.contact-form {
    background: var(--white);
    border-radius: 1.5rem;
    padding: 3rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 0.75rem;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--gray-light);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--accent-cyan);
    background: var(--white);
    box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.1);
}

.contact-info {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 1.5rem;
    padding: 3rem;
    color: var(--white);
    position: relative;
    overflow: hidden;
}

.contact-info::before {
    content: '';
    position: absolute;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(0, 212, 255, 0.2), transparent);
    top: -100px;
    right: -100px;
    border-radius: 50%;
}

.contact-info-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 2rem;
    position: relative;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-cyan);
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 3rem;
    position: relative;
}

.social-link {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    transition: all 0.3s ease;
}

.social-link:hover {
    background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
    transform: translateY(-5px);
}

/* ===================================
   RESPONSIVE
   =================================== */
@media (max-width: 992px) {
    .hero-title {
        font-size: 3rem;
    }

    .steps-timeline {
        padding-left: 2rem;
    }

    .timeline-line {
        left: 40px;
    }

    .step-item,
    .step-item:nth-child(even) {
        flex-direction: row;
    }

    .step-content,
    .step-item:nth-child(even) .step-content {
        text-align: left;
    }

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
    .hero-title {
        font-size: 2.5rem;
    }

    .hero-buttons {
        flex-direction: column;
    }

    .hero-stats {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 2rem;
    }
}

/* ===================================
   SCROLL ANIMATIONS
   =================================== */
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease-out;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}
</style>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg">
        <div class="grid-bg"></div>
        <div class="gradient-orb orb-1"></div>
        <div class="gradient-orb orb-2"></div>
        <div class="gradient-orb orb-3"></div>
    </div>

    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-sparkles"></i>
                Sistema de alertas inteligente
            </div>

            <h1 class="hero-title">
                Licitaciones que importan,<br>directo a tu bandeja
            </h1>

            <p class="hero-subtitle">
                Olvídate de buscar manualmente. Nuestra IA encuentra y filtra las mejores oportunidades para tu negocio.
            </p>

            <div class="hero-buttons">
                <a href="#contacto" class="btn btn-primary">
                    <i class="fas fa-rocket"></i>
                    Comenzar ahora
                </a>
                <a href="#como-funciona" class="btn btn-secondary">
                    <i class="fas fa-play-circle"></i>
                    Ver demo
                </a>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-number">+150</div>
                    <div class="stat-label">Licitaciones analizadas diariamente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Precisión en filtrado</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">+50</div>
                    <div class="stat-label">Empresas activas</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CÓMO FUNCIONA -->
<section id="como-funciona" class="how-it-works">
    <div class="container">
        <div class="section-header fade-in">
            <div class="section-subtitle">PROCESO SIMPLE</div>
            <h2 class="section-title">Tres pasos hacia el éxito</h2>
            <p class="section-description">
                Configuración rápida y resultados inmediatos
            </p>
        </div>

        <div class="steps-timeline">
            <div class="timeline-line"></div>

            <div class="step-item fade-in">
                <div class="step-content">
                    <h3 class="step-title">Define tus criterios</h3>
                    <p class="step-description">
                        Configura categorías, presupuestos, ubicaciones y más. Nuestro sistema aprende de tus preferencias.
                    </p>
                </div>
                <div class="step-icon-wrapper">
                    <div class="step-number">1</div>
                    <i class="fas fa-sliders"></i>
                </div>
                <div class="step-content"></div>
            </div>

            <div class="step-item fade-in">
                <div class="step-content">
                    <h3 class="step-title">Recibe alertas personalizadas</h3>
                    <p class="step-description">
                        Cada mañana, las mejores oportunidades en tu email. Sin ruido, solo licitaciones relevantes.
                    </p>
                </div>
                <div class="step-icon-wrapper">
                    <div class="step-number">2</div>
                    <i class="fas fa-bell"></i>
                </div>
                <div class="step-content"></div>
            </div>

            <div class="step-item fade-in">
                <div class="step-content">
                    <h3 class="step-title">Gana más contratos</h3>
                    <p class="step-description">
                        Actúa rápido con información completa. Aumenta tus probabilidades con análisis de competencia.
                    </p>
                </div>
                <div class="step-icon-wrapper">
                    <div class="step-number">3</div>
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="step-content"></div>
            </div>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="benefits">
    <div class="container">
        <div class="section-header fade-in">
            <div class="section-subtitle" style="color: var(--accent-cyan);">VENTAJAS</div>
            <h2 class="section-title" style="color: var(--white);">Por qué elegir LicitaHoy</h2>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3 class="benefit-title">10x más rápido</h3>
                <p class="benefit-description">
                    Reduce de horas a minutos el tiempo de búsqueda y análisis de oportunidades.
                </p>
            </div>

            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="benefit-title">IA especializada</h3>
                <p class="benefit-description">
                    Algoritmos entrenados en millones de licitaciones para detectar oportunidades ocultas.
                </p>
            </div>

            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h3 class="benefit-title">100% confiable</h3>
                <p class="benefit-description">
                    Datos verificados en tiempo real desde fuentes oficiales del gobierno.
                </p>
            </div>

            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="benefit-title">Análisis predictivo</h3>
                <p class="benefit-description">
                    Evaluamos probabilidades de éxito basadas en históricos y competencia.
                </p>
            </div>

            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-mobile-screen"></i>
                </div>
                <h3 class="benefit-title">Acceso total</h3>
                <p class="benefit-description">
                    Dashboard web y app móvil para gestionar oportunidades donde estés.
                </p>
            </div>

            <div class="benefit-card fade-in">
                <div class="benefit-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="benefit-title">Soporte 24/7</h3>
                <p class="benefit-description">
                    Equipo especializado listo para ayudarte a maximizar resultados.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content fade-in">
            <div class="cta-box">
                <h2 class="cta-title">
                    ¿Listo para transformar tu estrategia?
                </h2>
                <p class="cta-description">
                    Únete a las empresas que ya están ganando más contratos con menos esfuerzo
                </p>
                <a href="#contacto" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Solicitar demo gratuita
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="contact">
    <div class="container">
        <div class="section-header fade-in">
            <div class="section-subtitle">HABLEMOS</div>
            <h2 class="section-title">Comienza hoy mismo</h2>
        </div>

        <div class="contact-grid">
            <div class="contact-form fade-in">
                <form id="contactForm" action="/process_contact.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" class="form-input" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Empresa</label>
                        <input type="text" class="form-input" name="empresa" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email corporativo</label>
                        <input type="email" class="form-input" name="correo" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-input" name="telefono">
                    </div>

                    <div class="form-group">
                        <label class="form-label">¿En qué categorías te interesa participar?</label>
                        <textarea class="form-textarea" name="intereses" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mensaje adicional</label>
                        <textarea class="form-textarea" name="mensaje" rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i>
                        Enviar solicitud
                    </button>
                </form>
            </div>

            <div class="contact-info fade-in">
                <h3 class="contact-info-title">Información de contacto</h3>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; opacity: 0.7;">Email</div>
                        <div style="font-weight: 600;">info@licitacionesya.com</div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; opacity: 0.7;">Teléfono</div>
                        <div style="font-weight: 600;">+506 6382-5225</div>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; opacity: 0.7;">Ubicación</div>
                        <div style="font-weight: 600;">Alajuela, Costa Rica</div>
                    </div>
                </div>

                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

// Form validation
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('[required]');
    let valid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            valid = false;
        } else {
            input.style.borderColor = '#e2e8f0';
        }
    });

    if (!valid) {
        e.preventDefault();
        alert('Por favor completa todos los campos requeridos');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
