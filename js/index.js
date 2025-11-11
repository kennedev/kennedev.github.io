// ===== MOBILE NAVIGATION =====
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');
const navLinks = document.querySelectorAll('.nav-link');

// Toggle mobile menu
if (hamburger) {
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
}

// Close mobile menu when clicking on a link
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        hamburger?.classList.remove('active');
        navMenu?.classList.remove('active');
    });
});

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offset = 80; // navbar height
            const targetPosition = target.offsetTop - offset;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ===== NAVBAR SCROLL EFFECT =====
let lastScroll = 0;
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    // Add/remove shadow based on scroll
    if (currentScroll > 0) {
        navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
    } else {
        navbar.style.boxShadow = 'none';
    }
    
    lastScroll = currentScroll;
});

// ===== SCROLL REVEAL ANIMATIONS =====
const revealElements = document.querySelectorAll('.reveal');

const revealOnScroll = () => {
    const windowHeight = window.innerHeight;
    const revealPoint = 100;

    revealElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        
        if (elementTop < windowHeight - revealPoint) {
            element.classList.add('active');
        }
    });
};

// Initial check
revealOnScroll();

// Check on scroll
window.addEventListener('scroll', revealOnScroll);

// ===== COUNTER ANIMATION FOR STATS =====
const animateCounter = (element, target, duration = 2000) => {
    let current = 0;
    const increment = target / (duration / 16); // 60 FPS
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current) + '+';
    }, 16);
};

// Observe stats section for counter animation
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const statNumbers = entry.target.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                animateCounter(stat, target);
            });
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const statsSection = document.querySelector('.stats');
if (statsSection) {
    statsObserver.observe(statsSection);
}

// ===== PARALLAX EFFECT FOR GRADIENT ORBS =====
const orbs = document.querySelectorAll('.gradient-orb');

window.addEventListener('mousemove', (e) => {
    const mouseX = e.clientX / window.innerWidth;
    const mouseY = e.clientY / window.innerHeight;
    
    orbs.forEach((orb, index) => {
        const speed = (index + 1) * 20;
        const x = (mouseX - 0.5) * speed;
        const y = (mouseY - 0.5) * speed;
        orb.style.transform = `translate(${x}px, ${y}px)`;
    });
});

// ===== TYPING EFFECT FOR HERO SUBTITLE (Optional Enhancement) =====
// Disabled to work with language toggle
// const heroSubtitle = document.querySelector('.hero-subtitle');
// if (heroSubtitle) {
//     const originalText = heroSubtitle.textContent;
//     heroSubtitle.textContent = '';
//     
//     let charIndex = 0;
//     const typeSpeed = 100;
//     
//     const typeWriter = () => {
//         if (charIndex < originalText.length) {
//             heroSubtitle.textContent += originalText.charAt(charIndex);
//             charIndex++;
//             setTimeout(typeWriter, typeSpeed);
//         }
//     };
//     
//     // Start typing after a short delay
//     setTimeout(typeWriter, 500);
// }

// ===== SCROLL INDICATOR AUTO-HIDE =====
const scrollIndicator = document.querySelector('.scroll-indicator');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        scrollIndicator?.classList.add('hidden');
        if (scrollIndicator) {
            scrollIndicator.style.opacity = '0';
            scrollIndicator.style.pointerEvents = 'none';
        }
    } else {
        scrollIndicator?.classList.remove('hidden');
        if (scrollIndicator) {
            scrollIndicator.style.opacity = '1';
            scrollIndicator.style.pointerEvents = 'auto';
        }
    }
});

// ===== SKILL ITEMS STAGGER ANIMATION =====
const skillCategories = document.querySelectorAll('.skill-category');

skillCategories.forEach(category => {
    const items = category.querySelectorAll('.skill-item');
    items.forEach((item, index) => {
        item.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
    });
});

// ===== ACTIVE NAVIGATION LINK ON SCROLL =====
const sections = document.querySelectorAll('section[id]');

const highlightNavigation = () => {
    const scrollY = window.pageYOffset;

    sections.forEach(section => {
        const sectionHeight = section.offsetHeight;
        const sectionTop = section.offsetTop - 100;
        const sectionId = section.getAttribute('id');
        const navLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);

        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            navLink?.classList.add('active');
        } else {
            navLink?.classList.remove('active');
        }
    });
};

window.addEventListener('scroll', highlightNavigation);

// ===== PROJECT CARDS INTERACTION =====
const projectCards = document.querySelectorAll('.project-card');

projectCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// ===== LOADING ANIMATION =====
window.addEventListener('load', () => {
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease-in';
        document.body.style.opacity = '1';
    }, 100);
});

// ===== EASTER EGG - KONAMI CODE =====
let konamiCode = [];
const konamiPattern = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];

document.addEventListener('keydown', (e) => {
    konamiCode.push(e.key);
    konamiCode = konamiCode.slice(-konamiPattern.length);
    
    if (konamiCode.join(',') === konamiPattern.join(',')) {
        // Easter egg activated!
        document.body.style.animation = 'rainbow 2s infinite';
        setTimeout(() => {
            document.body.style.animation = '';
        }, 5000);
    }
});

// Add rainbow animation to CSS dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes rainbow {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }
    
    .nav-link.active::after {
        width: 100%;
    }
`;
document.head.appendChild(style);

console.log('%c🚀 Welcome to Kennedy\'s Portfolio!', 'color: #6366f1; font-size: 20px; font-weight: bold;');
console.log('%c💡 Try the Konami Code for a surprise!', 'color: #8b5cf6; font-size: 14px;');

// ===== LANGUAGE TOGGLE =====
const translations = {
    en: {
        // Navigation
        'nav.home': 'Home',
        'nav.about': 'About',
        'nav.skills': 'Skills',
        'nav.projects': 'Projects',
        'nav.contact': 'Contact',
        
        // Hero
        'hero.greeting': '👋 Hello, I\'m',
        'hero.subtitle': 'Full-Stack Developer',
        'hero.description': 'Transforming ideas into elegant digital solutions with',
        'hero.years': '12+ years',
        'hero.descriptionEnd': 'of expertise',
        'hero.viewWork': 'View My Work',
        'hero.getInTouch': 'Get In Touch',
        
        // Stats
        'stats.yearsExperience': 'Years Experience',
        'stats.projectsCompleted': 'Projects Completed',
        'stats.technologiesMastered': 'Technologies Mastered',
        
        // About
        'about.tag': 'Get to know me',
        'about.title': 'About Me',
        'about.paragraph1': 'I have over 12 years of experience in software development, focusing on creating scalable, secure, and sustainable web applications. I work primarily with Ruby on Rails, PostgreSQL, Sidekiq, and Svelte, in addition to managing infrastructure on AWS (EC2, S3, RDS, CloudWatch).',
        'about.paragraph2': 'I have solid experience in API architecture, integrations with external services, background jobs, deployment automation, and observability (monitoring and metrics). In my workflow, I prioritize automated testing (RSpec), rigorous code review, CI/CD (GitHub Actions and AWS pipelines), and best practices for versioning and security.',
        'about.paragraph3': 'One of the most relevant projects was at Santander, where I modernized a legacy cash flow control system — originally in Microsoft Access — transforming it into an integrated, lightweight, and highly reliable web application. The project increased operational productivity and reduced failures in manual processes.',
        'about.paragraph4': 'Currently, I am dedicated to backend engineering with Rails, focusing on scalability, automation, and system integration, always seeking to balance technical quality, delivery speed, and product impact.',
        'about.highlight1': 'Backend Engineering',
        'about.highlight2': 'System Integration',
        'about.highlight3': 'AWS Infrastructure',
        
        // Skills
        'skills.tag': 'What I do',
        'skills.title': 'My Skills',
        'skills.frontend': 'Front-End',
        'skills.backend': 'Back-End',
        'skills.databases': 'Databases',
        'skills.devops': 'Tools & DevOps',
        
        // Projects
        'projects.tag': 'My work',
        'projects.title': 'Featured Projects',
        'projects.project1.title': 'Santander Cash Flow System',
        'projects.project1.description': 'Modernized a legacy Access system into a dynamic web application, integrating multiple operational systems into a unified platform.',
        'projects.project1.tag1': 'Web App',
        'projects.project1.tag2': 'Integration',
        'projects.project1.tag3': 'Finance',
        'projects.project2.title': 'E-Commerce Platform',
        'projects.project2.description': 'Full-stack e-commerce solution with payment integration, inventory management, and admin dashboard.',
        'projects.project2.tag1': 'E-Commerce',
        'projects.project2.tag2': 'Full-Stack',
        'projects.project2.tag3': 'Payment',
        'projects.project3.title': 'CRM System',
        'projects.project3.description': 'Custom CRM solution with advanced analytics, customer tracking, and automated workflows.',
        'projects.project3.tag1': 'CRM',
        'projects.project3.tag2': 'Analytics',
        'projects.project3.tag3': 'Automation',
        
        // Contact
        'contact.tag': 'Let\'s connect',
        'contact.title': 'Get In Touch',
        'contact.description': 'I\'m always open to discussing new projects, creative ideas, or opportunities to be part of your vision.',
        'contact.emailLabel': 'Email',
        'contact.linkedinText': 'Connect with me',
        
        // Footer
        'footer.text': 'Crafted with',
        'footer.and': 'and code'
    },
    pt: {
        // Navigation
        'nav.home': 'Início',
        'nav.about': 'Sobre',
        'nav.skills': 'Habilidades',
        'nav.projects': 'Projetos',
        'nav.contact': 'Contato',
        
        // Hero
        'hero.greeting': '👋 Olá, eu sou',
        'hero.subtitle': 'Desenvolvedor Full-Stack',
        'hero.description': 'Transformando ideias em soluções digitais elegantes com',
        'hero.years': '12+ anos',
        'hero.descriptionEnd': 'de experiência',
        'hero.viewWork': 'Ver Meus Trabalhos',
        'hero.getInTouch': 'Entre em Contato',
        
        // Stats
        'stats.yearsExperience': 'Anos de Experiência',
        'stats.projectsCompleted': 'Projetos Concluídos',
        'stats.technologiesMastered': 'Tecnologias Dominadas',
        
        // About
        'about.tag': 'Conheça-me',
        'about.title': 'Sobre Mim',
        'about.paragraph1': 'Tenho mais de 12 anos de experiência em desenvolvimento de software, com foco em criar aplicações web escaláveis, seguras e sustentáveis. Atuo principalmente com Ruby on Rails, PostgreSQL, Sidekiq e Svelte, além de gerenciar infraestrutura em AWS (EC2, S3, RDS, CloudWatch).',
        'about.paragraph2': 'Tenho sólida experiência em arquitetura de APIs, integrações com serviços externos, background jobs, automação de deploy e observabilidade (monitoramento e métricas). No meu fluxo de trabalho, priorizo testes automatizados (RSpec), code review rigoroso, CI/CD (GitHub Actions e pipelines em AWS) e boas práticas de versionamento e segurança.',
        'about.paragraph3': 'Um dos projetos mais relevantes foi no Santander, onde modernizei um sistema de controle de fluxo de caixa legado — originalmente em Microsoft Access — transformando-o em uma aplicação web integrada, leve e de alta confiabilidade. O projeto aumentou a produtividade da operação e reduziu falhas em processos manuais.',
        'about.paragraph4': 'Atualmente, estou dedicado a engenharia backend com Rails, focando em escalabilidade, automação e integração entre sistemas, sempre buscando equilibrar qualidade técnica, velocidade de entrega e impacto de produto.',
        'about.highlight1': 'Engenharia Backend',
        'about.highlight2': 'Integração de Sistemas',
        'about.highlight3': 'Infraestrutura AWS',
        
        // Skills
        'skills.tag': 'O que eu faço',
        'skills.title': 'Minhas Habilidades',
        'skills.frontend': 'Front-End',
        'skills.backend': 'Back-End',
        'skills.databases': 'Bancos de Dados',
        'skills.devops': 'Ferramentas & DevOps',
        
        // Projects
        'projects.tag': 'Meus trabalhos',
        'projects.title': 'Projetos em Destaque',
        'projects.project1.title': 'Sistema de Fluxo de Caixa Santander',
        'projects.project1.description': 'Modernizei um sistema legado em Access para uma aplicação web dinâmica, integrando múltiplos sistemas operacionais em uma plataforma unificada.',
        'projects.project1.tag1': 'Aplicação Web',
        'projects.project1.tag2': 'Integração',
        'projects.project1.tag3': 'Financeiro',
        'projects.project2.title': 'Plataforma E-Commerce',
        'projects.project2.description': 'Solução e-commerce full-stack com integração de pagamento, gestão de estoque e painel administrativo.',
        'projects.project2.tag1': 'E-Commerce',
        'projects.project2.tag2': 'Full-Stack',
        'projects.project2.tag3': 'Pagamento',
        'projects.project3.title': 'Sistema CRM',
        'projects.project3.description': 'Solução CRM personalizada com análises avançadas, rastreamento de clientes e fluxos de trabalho automatizados.',
        'projects.project3.tag1': 'CRM',
        'projects.project3.tag2': 'Análises',
        'projects.project3.tag3': 'Automação',
        
        // Contact
        'contact.tag': 'Vamos conversar',
        'contact.title': 'Entre em Contato',
        'contact.description': 'Estou sempre aberto a discutir novos projetos, ideias criativas ou oportunidades de fazer parte da sua visão.',
        'contact.emailLabel': 'E-mail',
        'contact.linkedinText': 'Conecte-se comigo',
        
        // Footer
        'footer.text': 'Feito com',
        'footer.and': 'e código'
    }
};

// Get current language from localStorage or default to 'en'
let currentLanguage = localStorage.getItem('language') || 'en';

// Function to update all text content
const updateLanguage = (lang) => {
    currentLanguage = lang;
    localStorage.setItem('language', lang);
    
    // Update all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            // Special handling for elements that might contain HTML
            if (element.tagName === 'SPAN' && element.parentElement.classList.contains('hero-description')) {
                // This is part of the hero description, update it
                element.textContent = translations[lang][key];
            } else {
                element.textContent = translations[lang][key];
            }
        }
    });
    
    // Update language toggle button text
    const langText = document.querySelector('.lang-text');
    if (langText) {
        langText.textContent = lang.toUpperCase();
    }
    
    // Update HTML lang attribute
    document.documentElement.lang = lang;
};

// Initialize language on page load
updateLanguage(currentLanguage);

// Language toggle button event
const languageToggle = document.getElementById('languageToggle');
if (languageToggle) {
    languageToggle.addEventListener('click', () => {
        const newLang = currentLanguage === 'en' ? 'pt' : 'en';
        updateLanguage(newLang);
    });
}