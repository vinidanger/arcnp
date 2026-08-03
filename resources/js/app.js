import './bootstrap';

import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

window.bootstrap = bootstrap;

// Retrair/expandir o menu lateral — estado persistido em localStorage
// (puramente visual, por navegador, não precisa ir pro servidor). O
// estado inicial já é aplicado por um script inline no <head> de cada
// layout, antes do primeiro paint, pra não "piscar" expandido.
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebar-toggle');

    if (! toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
    });
});

// Tema claro/escuro — mesma lógica de persistência do menu lateral,
// usando o suporte nativo de dark mode do Bootstrap 5.3
// (data-bs-theme). Estado inicial já aplicado no <head> (theme-init).
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('theme-toggle');

    if (! toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
    });
});

// Dropdowns dentro de .table-responsive (ex.: "Baixar" na listagem de
// backups) ficam cortados pelo overflow:auto da tabela — o Popper do
// Bootstrap por padrão posiciona o menu como "absolute", contido no
// ancestral com overflow mais próximo. strategy:"fixed" posiciona
// relativo à viewport em vez disso, escapando desse corte. Não existe
// atributo data-bs-strategy nativo no Bootstrap — precisa passar via
// popperConfig na inicialização mesmo.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-strategy="fixed"]').forEach((toggle) => {
        bootstrap.Dropdown.getOrCreateInstance(toggle, {
            popperConfig: (defaultConfig) => ({ ...defaultConfig, strategy: 'fixed' }),
        });
    });
});

/**
 * Mantém a aba ativa (Visão geral/Domínios/Bancos/Backups) através de
 * reloads — sem isso, qualquer form dentro de uma aba (ex.: "Adicionar
 * domínio") volta pra primeira aba depois do redirect do Laravel
 * (back()), porque o estado de aba do Bootstrap é só classe CSS, não
 * sobrevive a um load novo da página.
 *
 * A URL guarda a aba atual em #tab-x. O truque: quando um form é
 * enviado, grudamos o #hash atual na própria action ANTES de submeter —
 * o back() do Laravel redireciona sem fragmento próprio, mas o
 * navegador propaga automaticamente o fragmento da requisição original
 * pra URL final quando o Location do redirect não define um (comportamento
 * padrão de fragmento em redirect, não precisa de nada server-side).
 * Funciona pra QUALQUER form da página (inclusive os de fora das abas,
 * tipo "Emitir SSL" no cabeçalho) sem precisar anotar cada form
 * individualmente no Blade.
 */
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#tab-"]');

    if (! tabButtons.length) {
        return;
    }

    if (location.hash) {
        const target = document.querySelector(`[data-bs-toggle="tab"][data-bs-target="${location.hash}"]`);
        if (target) {
            bootstrap.Tab.getOrCreateInstance(target).show();
        }
    }

    tabButtons.forEach((button) => {
        button.addEventListener('shown.bs.tab', () => {
            history.replaceState(null, '', button.dataset.bsTarget);
        });
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            if (! location.hash) {
                return;
            }
            const url = new URL(form.action, window.location.origin);
            url.hash = location.hash;
            form.action = url.toString();
        });
    });
});
