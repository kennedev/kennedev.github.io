// ===== PREFERÊNCIAS / CAPACIDADES =====
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

// ===== MOBILE NAVIGATION =====
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');
const navLinks = document.querySelectorAll('.nav-link');

const setMenuState = (isOpen) => {
    if (!hamburger || !navMenu) return;
    hamburger.classList.toggle('active', isOpen);
    navMenu.classList.toggle('active', isOpen);
    hamburger.setAttribute('aria-expanded', String(isOpen));
    hamburger.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
    document.body.classList.toggle('nav-lock', isOpen);
};

if (hamburger) {
    hamburger.addEventListener('click', () => {
        setMenuState(!navMenu.classList.contains('active'));
    });
}

navLinks.forEach(link => link.addEventListener('click', () => setMenuState(false)));

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navMenu?.classList.contains('active')) {
        setMenuState(false);
        hamburger?.focus();
    }
});

// ===== SMOOTH SCROLL (âncoras na mesma página) =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            window.scrollTo({ top: target.offsetTop - 72, behavior: 'smooth' });
        }
    });
});

// ===== NAVBAR SCROLL STATE =====
const navbar = document.querySelector('.navbar');
const onNavScroll = () => navbar?.classList.toggle('scrolled', window.scrollY > 24);
onNavScroll();
window.addEventListener('scroll', onNavScroll, { passive: true });

// ===== SCROLL REVEAL (IntersectionObserver + stagger) =====
const revealEls = document.querySelectorAll('.reveal');

revealEls.forEach(el => {
    const sibs = Array.from(el.parentElement.children).filter(c => c.classList.contains('reveal'));
    const idx = Math.max(0, sibs.indexOf(el));
    el.style.transitionDelay = `${Math.min(idx * 0.07, 0.42)}s`;
});

if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

    revealEls.forEach(el => revealObserver.observe(el));
} else {
    revealEls.forEach(el => el.classList.add('active'));
}

// ===== COUNTER ANIMATION FOR STATS =====
const animateCounter = (element, target, duration = 1800) => {
    let current = 0;
    const increment = target / (duration / 16);
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current) + '+';
    }, 16);
};

const statsSection = document.querySelector('.stats');
if (statsSection && 'IntersectionObserver' in window) {
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.stat-number').forEach(stat => {
                    animateCounter(stat, parseInt(stat.getAttribute('data-target'), 10));
                });
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    statsObserver.observe(statsSection);
}

// ===== CURSOR SPOTLIGHT (hero + cards) — somente ponteiro fino =====
const hero = document.querySelector('.hero');
const heroMouse = { x: -9999, y: -9999, inside: false };

if (hero && finePointer) {
    let raf = null, mx = 50, my = 38;
    hero.addEventListener('pointermove', (e) => {
        const r = hero.getBoundingClientRect();
        mx = ((e.clientX - r.left) / r.width) * 100;
        my = ((e.clientY - r.top) / r.height) * 100;
        heroMouse.x = e.clientX - r.left;
        heroMouse.y = e.clientY - r.top;
        heroMouse.inside = true;
        if (!raf) {
            raf = requestAnimationFrame(() => {
                hero.style.setProperty('--mx', mx + '%');
                hero.style.setProperty('--my', my + '%');
                raf = null;
            });
        }
    });
    hero.addEventListener('pointerleave', () => { heroMouse.inside = false; });
}

if (finePointer) {
    document.querySelectorAll('[data-spotlight]').forEach(card => {
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            card.style.setProperty('--mx', `${((e.clientX - r.left) / r.width) * 100}%`);
            card.style.setProperty('--my', `${((e.clientY - r.top) / r.height) * 100}%`);
        });
    });
}

// ===== HERO CONSTELLATION (canvas) — desktop + sem reduced-motion =====
const canvas = document.querySelector('.hero-canvas');
if (canvas && hero && finePointer && !reduceMotion) {
    const ctx = canvas.getContext('2d');
    let w = 0, h = 0, dpr = 1, particles = [], raf = null, running = false;

    const resize = () => {
        const r = hero.getBoundingClientRect();
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = r.width; h = r.height;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        const count = Math.min(70, Math.floor((w * h) / 16000));
        particles = Array.from({ length: count }, () => ({
            x: Math.random() * w,
            y: Math.random() * h,
            vx: (Math.random() - 0.5) * 0.35,
            vy: (Math.random() - 0.5) * 0.35
        }));
    };

    const draw = () => {
        ctx.clearRect(0, 0, w, h);
        const LINK = 130, MOUSE_LINK = 170;

        for (const p of particles) {
            p.x += p.vx; p.y += p.vy;
            if (p.x < 0 || p.x > w) p.vx *= -1;
            if (p.y < 0 || p.y > h) p.vy *= -1;
            ctx.beginPath();
            ctx.arc(p.x, p.y, 1.4, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(129, 140, 248, 0.5)';
            ctx.fill();
        }

        for (let i = 0; i < particles.length; i++) {
            const a = particles[i];
            for (let j = i + 1; j < particles.length; j++) {
                const b = particles[j];
                const dist = Math.hypot(a.x - b.x, a.y - b.y);
                if (dist < LINK) {
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.strokeStyle = `rgba(99, 102, 241, ${0.16 * (1 - dist / LINK)})`;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            }
            if (heroMouse.inside) {
                const dm = Math.hypot(a.x - heroMouse.x, a.y - heroMouse.y);
                if (dm < MOUSE_LINK) {
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(heroMouse.x, heroMouse.y);
                    ctx.strokeStyle = `rgba(34, 211, 238, ${0.28 * (1 - dm / MOUSE_LINK)})`;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                }
            }
        }
        raf = requestAnimationFrame(draw);
    };

    const start = () => { if (!running) { running = true; draw(); } };
    const stop = () => { running = false; if (raf) cancelAnimationFrame(raf); raf = null; };

    resize();
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resize, 200);
    });

    // Pausa quando o hero sai da tela (economia de bateria/CPU)
    if ('IntersectionObserver' in window) {
        new IntersectionObserver((entries) => {
            entries.forEach(e => e.isIntersecting ? start() : stop());
        }, { threshold: 0 }).observe(hero);
    } else {
        start();
    }
}

// ===== SCROLL INDICATOR AUTO-HIDE =====
const scrollIndicator = document.querySelector('.scroll-indicator');
window.addEventListener('scroll', () => {
    if (!scrollIndicator) return;
    const hide = window.scrollY > 280;
    scrollIndicator.style.opacity = hide ? '0' : '1';
    scrollIndicator.style.pointerEvents = hide ? 'none' : 'auto';
}, { passive: true });

// ===== ACTIVE NAVIGATION LINK ON SCROLL =====
const sections = document.querySelectorAll('section[id]');
const highlightNavigation = () => {
    const scrollY = window.pageYOffset;
    sections.forEach(section => {
        const top = section.offsetTop - 120;
        const id = section.getAttribute('id');
        const link = document.querySelector(`.nav-link[href="#${id}"]`);
        if (!link) return;
        link.classList.toggle('nav-active', scrollY > top && scrollY <= top + section.offsetHeight);
    });
};
window.addEventListener('scroll', highlightNavigation, { passive: true });

console.log('%c Kennedev ', 'background: #6366f1; color: #fff; font-size: 14px; font-weight: bold; padding: 2px 6px; border-radius: 4px;');

// Site corporativo Kennedev - PT-BR
