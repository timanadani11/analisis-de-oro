/**
 * Funcionalidades deportivas para la web Análisis de Oro
 */

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Animación para elementos con la clase .animate-on-scroll
    initScrollAnimations();
    
    // Menú de navegación deportivo
    initSportNavbar();
    
    // Contadores para estadísticas
    initCounters();
    
    // Efecto parallax para fondos deportivos
    initParallax();
    
    // Animaciones para el footer
    initFooterAnimations();
});

/**
 * Inicializa animaciones al hacer scroll
 */
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Tomar la animación del atributo data-animation o usar el predeterminado
                const animation = entry.target.dataset.animation || 'visible';
                entry.target.classList.add(animation);
            }
        });
    }, { threshold: 0.1 });
    
    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Inicializa el menú de navegación deportivo
 */
function initSportNavbar() {
    const navbar = document.querySelector('.nav-sport');
    if (!navbar) return;
    
    // Cambiar estilo al hacer scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('nav-sport-scrolled');
        } else {
            navbar.classList.remove('nav-sport-scrolled');
        }
    });
    
    // Manejar el menú móvil
    const menuToggle = navbar.querySelector('.nav-sport-toggle');
    const mobileMenu = navbar.querySelector('.nav-sport-mobile');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

/**
 * Inicializa contadores para estadísticas
 */
function initCounters() {
    const counters = document.querySelectorAll('.counter');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'), 10);
                const duration = parseInt(counter.getAttribute('data-duration'), 10) || 2000;
                
                if (counter.classList.contains('counted')) return;
                
                let start = 0;
                const increment = Math.ceil(target / (duration / 16));
                
                const updateCounter = () => {
                    start += increment;
                    if (start >= target) {
                        counter.innerText = target.toLocaleString();
                        counter.classList.add('counted');
                    } else {
                        counter.innerText = start.toLocaleString();
                        requestAnimationFrame(updateCounter);
                    }
                };
                
                updateCounter();
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => {
        observer.observe(counter);
    });
}

/**
 * Inicializa efecto parallax para fondos
 */
function initParallax() {
    const parallaxElements = document.querySelectorAll('.parallax');
    
    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;
        
        parallaxElements.forEach(element => {
            const speed = element.getAttribute('data-speed') || 0.5;
            element.style.transform = `translateY(${scrollY * speed}px)`;
        });
    });
}

/**
 * Inicializa animaciones específicas para el footer
 */
function initFooterAnimations() {
    const footer = document.querySelector('.footer-sport');
    if (!footer) return;
    
    // Animar columnas del footer al entrar en viewport
    const footerColumns = footer.querySelectorAll('.footer-sport-column, .grid > div');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Añadir un retraso para crear un efecto secuencial
                setTimeout(() => {
                    entry.target.classList.add('animate-bounce-in');
                    entry.target.style.opacity = '1';
                }, index * 150);
            }
        });
    }, { threshold: 0.1 });
    
    footerColumns.forEach(column => {
        // Configurar para animación
        column.style.opacity = '0';
        observer.observe(column);
    });
    
    // Animar iconos sociales con un efecto de rebote
    const socialIcons = footer.querySelectorAll('.footer-sport-social a, .flex.space-x-4 a');
    socialIcons.forEach((icon, index) => {
        icon.style.opacity = '0';
        setTimeout(() => {
            icon.style.opacity = '1';
            icon.classList.add('animate-bounce-in');
        }, 1000 + (index * 200));
    });
    
    // Efecto hover para enlaces del footer
    const footerLinks = footer.querySelectorAll('a:not(.footer-sport-social a):not(.flex.space-x-4 a)');
    footerLinks.forEach(link => {
        link.addEventListener('mouseenter', () => {
            link.classList.add('link-hover-effect');
        });
        link.addEventListener('mouseleave', () => {
            link.classList.remove('link-hover-effect');
        });
    });
    
    // Añadir efecto brillante al logo del footer
    const footerLogo = footer.querySelector('.footer-sport-logo, h2');
    if (footerLogo) {
        footerLogo.addEventListener('mouseenter', () => {
            footerLogo.classList.add('animate-shine');
        });
        footerLogo.addEventListener('mouseleave', () => {
            footerLogo.classList.remove('animate-shine');
        });
    }
}

/**
 * Inicializa efectos deportivos para tablas y estadísticas
 */
function initSportTables() {
    const tables = document.querySelectorAll('.table-sport');
    if (!tables.length) return;
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.classList.add('highlight-row');
            });
            row.addEventListener('mouseleave', () => {
                row.classList.remove('highlight-row');
            });
        });
    });
}

/**
 * Agrega efectos deportivos a botones y enlaces
 */
function addButtonEffects() {
    const sportButtons = document.querySelectorAll('.btn-sport');
    
    sportButtons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.classList.add('btn-pulsate');
        });
        
        button.addEventListener('mouseleave', () => {
            button.classList.remove('btn-pulsate');
        });
    });
}

/**
 * Crea una animación de confeti para celebraciones
 * @param {Element} targetElement - Elemento sobre el que mostrar el confeti
 */
function showConfetti(targetElement) {
    // Crear lienzo para confeti
    const canvas = document.createElement('canvas');
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '9999';
    
    targetElement.style.position = 'relative';
    targetElement.appendChild(canvas);
    
    const ctx = canvas.getContext('2d');
    canvas.width = targetElement.clientWidth;
    canvas.height = targetElement.clientHeight;
    
    const colors = ['#0ea5e9', '#0284c7', '#10b981', '#059669', '#ef4444'];
    const pieces = [];
    const numPieces = 100;
    
    // Crear piezas de confeti
    for (let i = 0; i < numPieces; i++) {
        pieces.push({
            x: Math.random() * canvas.width,
            y: -20 - Math.random() * 100,
            width: Math.random() * 10 + 5,
            height: Math.random() * 10 + 5,
            speed: Math.random() * 5 + 2,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotationSpeed: Math.random() * 10 - 5
        });
    }
    
    // Animar confeti
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        let stillFalling = false;
        
        pieces.forEach(piece => {
            piece.y += piece.speed;
            piece.rotation += piece.rotationSpeed;
            
            ctx.save();
            ctx.translate(piece.x, piece.y);
            ctx.rotate((piece.rotation * Math.PI) / 180);
            ctx.fillStyle = piece.color;
            ctx.fillRect(-piece.width / 2, -piece.height / 2, piece.width, piece.height);
            ctx.restore();
            
            if (piece.y < canvas.height) {
                stillFalling = true;
            }
        });
        
        if (stillFalling) {
            requestAnimationFrame(animate);
        } else {
            // Eliminar el canvas cuando termine la animación
            setTimeout(() => {
                canvas.remove();
            }, 1000);
        }
    }
    
    animate();
}

/**
 * Muestra un gráfico deportivo simple
 * @param {string} selector - Selector del elemento canvas
 * @param {Array} data - Datos para el gráfico
 * @param {Array} labels - Etiquetas para el gráfico
 */
function createSportChart(selector, data, labels) {
    const canvas = document.querySelector(selector);
    if (!canvas || !canvas.getContext) return;
    
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    const padding = 40;
    const chartWidth = width - (padding * 2);
    const chartHeight = height - (padding * 2);
    
    // Limpiar canvas
    ctx.clearRect(0, 0, width, height);
    
    // Dibujar fondo
    ctx.fillStyle = '#f8fafc';
    ctx.fillRect(padding, padding, chartWidth, chartHeight);
    
    // Encontrar valor máximo
    const maxValue = Math.max(...data) * 1.1;
    
    // Dibujar ejes
    ctx.strokeStyle = '#94a3b8';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, height - padding);
    ctx.lineTo(width - padding, height - padding);
    ctx.stroke();
    
    // Dibujar datos
    const barWidth = chartWidth / data.length * 0.8;
    const barSpacing = chartWidth / data.length * 0.2;
    
    for (let i = 0; i < data.length; i++) {
        const barHeight = (data[i] / maxValue) * chartHeight;
        const x = padding + (i * (barWidth + barSpacing));
        const y = height - padding - barHeight;
        
        // Barra con gradiente
        const gradient = ctx.createLinearGradient(x, y, x, height - padding);
        gradient.addColorStop(0, '#0ea5e9');
        gradient.addColorStop(1, '#0284c7');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(x, y, barWidth, barHeight);
        
        // Etiqueta
        ctx.fillStyle = '#1f2937';
        ctx.font = '12px Montserrat';
        ctx.textAlign = 'center';
        ctx.fillText(labels[i], x + barWidth / 2, height - padding + 20);
        
        // Valor
        ctx.fillStyle = '#1f2937';
        ctx.font = 'bold 14px Montserrat';
        ctx.textAlign = 'center';
        ctx.fillText(data[i].toString(), x + barWidth / 2, y - 10);
    }
}

// Exportar funciones para uso global
window.SportsFunctions = {
    showConfetti,
    createSportChart
}; 