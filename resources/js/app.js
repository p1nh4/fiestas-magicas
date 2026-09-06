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

// --- Validação inline do formulário ----------------------------------------
// O erro aparece assim que se sai do campo, em vez de só depois de enviar e
// de a página recarregar. É melhoria e mais nada: o `<form>` continua a ser
// um POST normal, o `required` e o `type="email"` continuam a travar o envio
// sozinhos, e o StoreLeadRequest continua a ser quem decide de verdade. Se
// este ficheiro não carregar — webview do Instagram, ligação má, JS
// bloqueado — o formulário funciona exatamente como funcionava.
//
// As mensagens vêm do `lang/`, através de um data-attribute, e nunca daqui:
// o site tem três idiomas e uma frase escrita neste ficheiro só saberia um.
document.querySelectorAll('form[data-validacion]').forEach((form) => {
    let mensagens;
    try {
        mensagens = JSON.parse(form.dataset.validacion);
    } catch {
        return; // Sem mensagens não se inventa texto: fica o comportamento normal.
    }

    const email = form.querySelector('[name="email"]');
    const phone = form.querySelector('[name="phone"]');

    /** O que está mal neste campo, se é que está. */
    const problema = (campo) => {
        // O par email/telefone: a única regra que o browser não sabe
        // exprimir, e a que as pessoas falham mais. Só se avisa ao sair do
        // email, que é o segundo dos dois — avisar no primeiro seria dar
        // erro a quem ainda ia a caminho de preencher o outro.
        if (campo === email && !email.value.trim() && phone && !phone.value.trim()) {
            return mensagens.contact_required;
        }

        if (campo.value.trim() === '' && !campo.required) {
            return '';
        }

        if (campo === phone && campo.value.trim() && !/^[0-9+\s().\-]{6,}$/.test(campo.value)) {
            return mensagens.phone_format;
        }

        if (campo.checkValidity()) {
            return '';
        }

        if (campo.validity.valueMissing) {
            return campo.type === 'checkbox' ? mensagens.privacy : mensagens.required;
        }
        if (campo.validity.typeMismatch && campo.type === 'email') {
            return mensagens.email_format;
        }
        if (campo.validity.rangeUnderflow && campo.type === 'date') {
            return mensagens.date_past;
        }

        // Resto (números fora do intervalo, texto longo demais): a mensagem
        // do próprio browser, já traduzida por ele, é melhor do que uma
        // genérica nossa.
        return campo.validationMessage;
    };

    /** Mostra ou apaga a mensagem por baixo do campo. */
    const marcar = (campo, texto) => {
        const id = `e-${campo.name}`;
        let aviso = form.querySelector(`#${CSS.escape(id)}`);

        if (!texto) {
            campo.removeAttribute('aria-invalid');
            campo.removeAttribute('aria-describedby');
            if (aviso?.dataset.inline) aviso.remove();
            return;
        }

        if (!aviso) {
            aviso = document.createElement('p');
            aviso.id = id;
            aviso.dataset.inline = '1';
            aviso.className = 'text-[0.8rem] text-rosa-deep';
            (campo.type === 'checkbox' ? campo.closest('div').parentElement : campo.parentElement)
                .append(aviso);
        }

        aviso.textContent = texto;
        campo.setAttribute('aria-invalid', 'true');
        campo.setAttribute('aria-describedby', id);
    };

    const validavel = (campo) =>
        campo?.name && !campo.closest('[aria-hidden="true"]') && campo.type !== 'hidden';

    // `focusout` e não `blur`: o blur não sobe na árvore e obrigaria a um
    // listener por campo.
    form.addEventListener('focusout', (evento) => {
        if (validavel(evento.target)) marcar(evento.target, problema(evento.target));
    });

    // Enquanto corrigem, o erro desaparece — mas nunca aparece a meio de
    // quem ainda está a escrever.
    form.addEventListener('input', (evento) => {
        const campo = evento.target;
        if (validavel(campo) && campo.getAttribute('aria-invalid')) {
            marcar(campo, problema(campo));
        }
    });

    form.addEventListener('change', (evento) => {
        if (evento.target.type === 'checkbox') marcar(evento.target, problema(evento.target));
    });
});
