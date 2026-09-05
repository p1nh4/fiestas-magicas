/*
 * O site funciona inteiro sem JavaScript: as ligações são ligações, o
 * formulário é um POST normal e o seletor de idioma são três links. O que
 * está aqui é só melhoria — se falhar, nada se perde.
 */

// --- PWA -------------------------------------------------------------------
// Serve para a Sol poder instalar o site no telemóvel como uma app (ícone no
// ecrã inicial, abre sem barra de endereço) e para as páginas já visitadas
// abrirem sem rede — útil num salão de festas com má cobertura.
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sem service worker o site continua a funcionar. Não vale um erro.
        });
    });
}

// --- Âncoras ---------------------------------------------------------------
// O cabeçalho é fixo; sem isto uma âncora deixa o título escondido por baixo.
document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const id = link.getAttribute('href');
        if (id === '#' || id.length < 2) return;

        const target = document.querySelector(id);
        if (!target) return;

        event.preventDefault();
        const offset = document.querySelector('header')?.offsetHeight ?? 0;
        const top = target.getBoundingClientRect().top + window.scrollY - offset - 12;

        window.scrollTo({
            top,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                ? 'auto'
                : 'smooth',
        });

        // Mantém o histórico e o foco corretos para quem usa teclado.
        history.pushState(null, '', id);
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
    });
});

// --- Formulário ------------------------------------------------------------
// Evita o duplo envio quando a ligação está lenta e a pessoa carrega outra vez.
document.querySelectorAll('form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;

        // setTimeout para o valor do botão ainda ir no pedido.
        setTimeout(() => {
            button.disabled = true;
            button.style.opacity = '0.6';
        }, 0);
    });
});
