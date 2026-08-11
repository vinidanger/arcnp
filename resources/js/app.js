import './bootstrap';

// ---------------------------------------------------------------------
// Menu lateral retrátil — estado persistido em localStorage (por
// navegador, não vai pro servidor). Estado inicial já aplicado por um
// script inline no <head> de cada layout (theme-init), antes do
// primeiro paint, pra não "piscar" expandido.
// ---------------------------------------------------------------------
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

// ---------------------------------------------------------------------
// Menu lateral em telas pequenas (< 992px) — vira um drawer fora do
// fluxo normal (ver .app-sidebar em app.css), aberto/fechado via
// html.sidebar-mobile-open. Sem persistência em localStorage (sempre
// começa fechado a cada carregamento de página, diferente do estado
// retraído/expandido do desktop, que é uma preferência duradoura).
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');
    const sidebar = document.querySelector('.app-sidebar');

    if (! mobileToggle || ! backdrop) {
        return;
    }

    const closeMobileNav = () => document.documentElement.classList.remove('sidebar-mobile-open');

    mobileToggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('sidebar-mobile-open');
    });

    backdrop.addEventListener('click', closeMobileNav);

    // Navegar por um link do menu fecha o drawer — sem isso, a próxima
    // página carregaria com o menu ainda aberto por cima do conteúdo.
    sidebar?.addEventListener('click', (event) => {
        if (event.target.closest('a.nav-link')) {
            closeMobileNav();
        }
    });
});

// ---------------------------------------------------------------------
// Tema claro/escuro — mesma lógica de persistência do menu lateral.
// Estado inicial já aplicado no <head> (theme-init).
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('theme-toggle');

    if (! toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });
});

// ---------------------------------------------------------------------
// Dropdowns — substitui o comportamento data-bs-toggle="dropdown" do
// Bootstrap. Um único listener de clique no documento (capture na
// abertura, fecha o resto) em vez de um listener por dropdown —
// mais simples de manter correto com vários dropdowns na mesma página
// (ex.: várias linhas de tabela, cada uma com o próprio menu).
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    function closeAllDropdowns(except) {
        document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
            if (menu !== except) {
                menu.classList.remove('show');
            }
        });
    }

    document.addEventListener('click', (event) => {
        const toggleEl = event.target.closest('[data-bs-toggle="dropdown"]');

        if (toggleEl) {
            const wrapper = toggleEl.closest('.dropdown');
            const menu = wrapper?.querySelector('.dropdown-menu');

            if (! menu) {
                return;
            }

            const wasOpen = menu.classList.contains('show');
            closeAllDropdowns();

            if (! wasOpen) {
                menu.classList.add('show');

                // data-bs-strategy="fixed": dropdown precisa escapar de
                // um ancestral com overflow:auto/hidden (ex.: dentro de
                // .table-responsive) — reposiciona via position:fixed
                // com coordenadas calculadas na hora, em vez de
                // position:absolute relativo ao ancestral mais próximo.
                if (toggleEl.dataset.bsStrategy === 'fixed') {
                    const rect = toggleEl.getBoundingClientRect();
                    menu.style.position = 'fixed';
                    menu.style.top = `${rect.bottom + 4}px`;
                    menu.style.left = menu.classList.contains('dropdown-menu-end')
                        ? ''
                        : `${rect.left}px`;
                    menu.style.right = menu.classList.contains('dropdown-menu-end')
                        ? `${window.innerWidth - rect.right}px`
                        : '';
                }
            }

            return;
        }

        if (! event.target.closest('.dropdown-menu')) {
            closeAllDropdowns();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
});

/**
 * Mantém a aba ativa (Visão geral/Domínios/Bancos/Backups) através de
 * reloads — sem isso, qualquer form dentro de uma aba (ex.: "Adicionar
 * domínio") volta pra primeira aba depois do redirect do Laravel
 * (back()), porque o estado de aba é só classe CSS, não sobrevive a um
 * load novo da página.
 *
 * A URL guarda a aba atual em #tab-x. O truque: quando um form é
 * enviado, grudamos o #hash atual na própria action ANTES de submeter —
 * o back() do Laravel redireciona sem fragmento próprio, mas o
 * navegador propaga automaticamente o fragmento da requisição original
 * pra URL final quando o Location do redirect não define um.
 */
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#tab-"]');

    if (! tabButtons.length) {
        return;
    }

    function activateTab(button) {
        const targetSelector = button.dataset.bsTarget;
        const target = document.querySelector(targetSelector);

        if (! target) {
            return;
        }

        const paneGroup = target.parentElement;

        // Sincroniza TODO trigger pra esse alvo na página inteira (não só
        // os que ficam dentro da mesma .nav-tabs do botão clicado) — sem
        // isso, disparar a troca por um atalho fora da barra de abas (ex.:
        // um tile da grade "Acesso rápido" apontando pra "#tab-domains")
        // trocava o conteúdo certo, mas a barra de abas de verdade nunca
        // destacava a aba correspondente.
        document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#tab-"]').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.bsTarget === targetSelector);
        });

        paneGroup?.querySelectorAll(':scope > .tab-pane').forEach((pane) => pane.classList.remove('active', 'show'));
        target.classList.add('active', 'show');
    }

    if (location.hash) {
        const target = document.querySelector(`[data-bs-toggle="tab"][data-bs-target="${location.hash}"]`);
        if (target) {
            activateTab(target);
        }
    }

    tabButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            // Alguns desses botões são links reais pra essa mesma página
            // (ex.: itens do menu lateral tipo "Bancos de Dados", que
            // apontam pra cá com #tab-databases). Se o alvo não existe
            // nesta página (o link foi clicado de outra tela), deixa o
            // navegador seguir o href normal — a página de destino ativa
            // a aba certa sozinha ao carregar (ver o bloco location.hash
            // acima). Só intercepta quando dá pra trocar de aba na hora,
            // porque um href só-com-hash pra ESTA MESMA página não recarrega
            // (o navegador só muda a URL), e sem isso o clique não faria
            // nada visível.
            if (! document.querySelector(button.dataset.bsTarget)) {
                return;
            }

            event.preventDefault();
            activateTab(button);
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

// ---------------------------------------------------------------------
// Modais — substitui bootstrap.Modal. Trigger: data-bs-toggle="modal"
// data-bs-target="#id". A própria .modal escuta clique no backdrop e
// Escape pra fechar; [data-bs-dismiss="modal"] fecha o ancestral mais
// próximo (funciona pra qualquer botão "Cancelar"/"×" dentro dela).
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    function openModal(modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-bs-toggle="modal"]');

        if (opener) {
            const modal = document.querySelector(opener.dataset.bsTarget);
            if (modal) {
                openModal(modal);
            }
            return;
        }

        const dismisser = event.target.closest('[data-bs-dismiss="modal"], [data-bs-dismiss="alert"]');

        if (dismisser) {
            if (dismisser.dataset.bsDismiss === 'alert') {
                dismisser.closest('.alert')?.remove();
            } else {
                dismisser.closest('.modal')?.classList.remove('show');
                document.body.style.overflow = '';
            }
            return;
        }

        // Clique no backdrop (fora de .modal-dialog) fecha.
        if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
            closeModal(event.target);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(closeModal);
        }
    });

    // API mínima usada pelo componente <x-modal :show="true"> (abre
    // sozinho ao carregar a página, ex.: fluxo de configurar 2FA).
    window.arcnModal = {
        show(id) {
            const modal = document.getElementById(id);
            if (modal) {
                openModal(modal);
            }
        },
        hide(id) {
            const modal = document.getElementById(id);
            if (modal) {
                closeModal(modal);
            }
        },
    };
});

// Formulário de filtro de e-mail: campo "pasta" só faz sentido pra
// ação "mover pra pasta" — escondido pro resto, alternado via
// data-target (aponta pro wrapper do campo) em vez de id fixo, porque
// cada caixa de e-mail tem seu próprio par select/campo na página.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.filter-action-select').forEach((select) => {
        const wrap = document.querySelector(select.dataset.target);

        if (! wrap) {
            return;
        }

        const toggle = () => {
            wrap.style.display = select.value === 'move_to_folder' ? '' : 'none';
        };

        select.addEventListener('change', toggle);
        toggle();
    });
});

// ---------------------------------------------------------------------
// Template "cPanel" — página "Tools" (client/hosting-accounts/show.blade.php):
// colapsar/expandir categoria (persistido em localStorage, por
// navegador, mesmo padrão do menu lateral retrátil) e busca ao vivo
// filtrando os itens da grade (não as categorias inteiras — combina
// com o colapso acima, uma categoria com algum item batendo a busca se
// auto-expande sem mexer na preferência salva).
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const collapsedKey = (category) => `cpanelCategoryCollapsed:${category.dataset.category}`;

    // Fonte única pra colapsar/expandir — mexe na classe (o que o CSS
    // já usava) E no atributo data-cpanel-collapsed do <html> (o que
    // theme-init.blade.php grava pra evitar o flash no próximo
    // carregamento, ver ali). Sem manter os dois em sincronia aqui, uma
    // categoria que nasceu colapsada (atributo presente) nunca
    // conseguia ser reaberta: a classe saía do elemento, mas a regra
    // CSS baseada no atributo continuava escondendo a grade.
    const applyCollapsed = (category, collapsed) => {
        category.classList.toggle('collapsed', collapsed);

        const slug = category.dataset.category;

        if (! slug) {
            return;
        }

        const current = new Set((document.documentElement.getAttribute('data-cpanel-collapsed') || '').split(' ').filter(Boolean));

        if (collapsed) {
            current.add(slug);
        } else {
            current.delete(slug);
        }

        if (current.size) {
            document.documentElement.setAttribute('data-cpanel-collapsed', Array.from(current).join(' '));
        } else {
            document.documentElement.removeAttribute('data-cpanel-collapsed');
        }
    };

    // Sincroniza a classe com o que já está salvo — sem isso, o
    // primeiro clique numa categoria que já nasceu colapsada (só via
    // atributo, a classe nunca tinha sido aplicada no elemento) lia
    // "collapsed" como false e tentava colapsar de novo em vez de abrir.
    document.querySelectorAll('.cpanel-category[data-category]').forEach((category) => {
        if (localStorage.getItem(collapsedKey(category)) === '1') {
            applyCollapsed(category, true);
        }
    });

    document.querySelectorAll('[data-cpanel-category-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const category = button.closest('.cpanel-category');

            if (! category) {
                return;
            }

            const collapsed = ! category.classList.contains('collapsed');
            applyCollapsed(category, collapsed);
            localStorage.setItem(collapsedKey(category), collapsed ? '1' : '0');
        });
    });

    const search = document.getElementById('cpanel-tool-search');

    if (! search) {
        return;
    }

    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();

        document.querySelectorAll('.cpanel-category').forEach((category) => {
            let categoryHasMatch = false;

            category.querySelectorAll('.cpanel-tool-item').forEach((item) => {
                const matches = item.dataset.toolLabel.toLowerCase().includes(query);
                item.hidden = ! matches;
                categoryHasMatch = categoryHasMatch || matches;
            });

            if (query === '') {
                // Busca limpa — volta pra preferência salva em vez de
                // forçar expandido.
                applyCollapsed(category, localStorage.getItem(collapsedKey(category)) === '1');
            } else {
                applyCollapsed(category, ! categoryHasMatch);
            }

            category.hidden = query !== '' && ! categoryHasMatch;
        });
    });
});

// ---------------------------------------------------------------------
// Template "cPanel" — reordenar as categorias da página "Tools" por
// arraste (só pela alcinha .cpanel-category-drag-handle, não o cabeçalho
// inteiro, pra não brigar com o clique de colapsar/expandir). Ordem
// persistida em localStorage (por navegador, mesmo padrão do resto do
// template cPanel) e reaplicada no carregamento movendo os elementos já
// existentes no DOM — sem re-render nenhum.
// ---------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const column = document.querySelector('.cpanel-tools-column');

    if (! column) {
        return;
    }

    const orderKey = 'cpanelCategoryOrder';

    const saveOrder = () => {
        const order = Array.from(column.querySelectorAll('.cpanel-category[data-category]'))
            .map((category) => category.dataset.category);
        localStorage.setItem(orderKey, JSON.stringify(order));
    };

    let savedOrder = [];
    try {
        savedOrder = JSON.parse(localStorage.getItem(orderKey) || '[]');
    } catch (e) {
        savedOrder = [];
    }

    if (Array.isArray(savedOrder)) {
        savedOrder.forEach((slug) => {
            const category = column.querySelector(`.cpanel-category[data-category="${slug}"]`);
            if (category) {
                column.appendChild(category);
            }
        });
    }

    let dragging = null;

    column.querySelectorAll('.cpanel-category-header').forEach((header) => {
        header.addEventListener('dragstart', (event) => {
            dragging = header.closest('.cpanel-category');

            if (! dragging) {
                return;
            }

            dragging.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragging.dataset.category || '');
        });

        header.addEventListener('dragend', () => {
            if (dragging) {
                dragging.classList.remove('dragging');
            }

            dragging = null;
            saveOrder();
        });
    });

    column.addEventListener('dragover', (event) => {
        if (! dragging) {
            return;
        }

        event.preventDefault();

        const siblings = Array.from(column.querySelectorAll('.cpanel-category:not(.dragging)'));
        const next = siblings.find((sibling) => {
            const rect = sibling.getBoundingClientRect();
            return event.clientY < rect.top + rect.height / 2;
        });

        if (next) {
            column.insertBefore(dragging, next);
        } else {
            column.appendChild(dragging);
        }
    });
});
