import { EditorView, basicSetup } from 'codemirror';
import { keymap } from '@codemirror/view';
import { Compartment } from '@codemirror/state';
import { indentWithTab, copyLineDown, copyLineUp, toggleComment } from '@codemirror/commands';
import { indentUnit } from '@codemirror/language';
import { oneDark } from '@codemirror/theme-one-dark';

function isDarkTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

// import() dinâmico em vez de importar os 8 pacotes de linguagem no
// topo do arquivo — cada um vira um chunk separado que só baixa
// quando alguém realmente abre um arquivo daquele tipo (antes, editar
// um .txt baixava o parser de PHP/SQL/YAML/etc. igual, tudo junto num
// chunk só de ~740KB). O editor aparece na hora; o highlight de
// sintaxe entra um instante depois, quando o import resolve.
const LANGUAGE_LOADERS = {
    php: () => import('@codemirror/lang-php').then((m) => m.php()),
    phtml: () => import('@codemirror/lang-php').then((m) => m.php()),
    html: () => import('@codemirror/lang-html').then((m) => m.html()),
    htm: () => import('@codemirror/lang-html').then((m) => m.html()),
    css: () => import('@codemirror/lang-css').then((m) => m.css()),
    js: () => import('@codemirror/lang-javascript').then((m) => m.javascript()),
    mjs: () => import('@codemirror/lang-javascript').then((m) => m.javascript()),
    cjs: () => import('@codemirror/lang-javascript').then((m) => m.javascript()),
    json: () => import('@codemirror/lang-json').then((m) => m.json()),
    sql: () => import('@codemirror/lang-sql').then((m) => m.sql()),
    md: () => import('@codemirror/lang-markdown').then((m) => m.markdown()),
    yml: () => import('@codemirror/lang-yaml').then((m) => m.yaml()),
    yaml: () => import('@codemirror/lang-yaml').then((m) => m.yaml()),
};

function languageLoaderForFilename(filename) {
    const extension = filename.split('.').pop().toLowerCase();

    return LANGUAGE_LOADERS[extension] ?? null;
}

const themeCompartments = [];

// O toggle de tema global não recarrega a página — um MutationObserver
// no atributo data-theme do <html> é o jeito de manter editores já
// abertos sincronizados quando o usuário troca o tema.
new MutationObserver(() => {
    const dark = isDarkTheme();

    themeCompartments.forEach(({ view, compartment }) => {
        view.dispatch({ effects: compartment.reconfigure(dark ? oneDark : []) });
    });
}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

document.querySelectorAll('textarea[data-code-editor]').forEach((textarea) => {
    const filename = textarea.dataset.filename ?? '';
    const languageLoader = languageLoaderForFilename(filename);
    const themeCompartment = new Compartment();
    const languageCompartment = new Compartment();

    const extensions = [
        basicSetup,
        // Precisa ser registrada via editorAttributes, não
        // view.dom.classList.add() depois de criar a view — o
        // CodeMirror recalcula e reaplica a lista de classes do próprio
        // elemento quando o estado de foco muda, e uma classe colada
        // "por fora" some nesse recálculo (foi exatamente o editor
        // encolhendo ao clicar dentro dele).
        EditorView.editorAttributes.of({ class: 'code-editor' }),
        themeCompartment.of(isDarkTheme() ? oneDark : []),
        indentUnit.of('    '),
        // basicSetup não trata Tab por padrão (deixa o foco sair do
        // editor, comportamento padrão do navegador) — indentWithTab é
        // o jeito documentado de fazer Tab/Shift-Tab indentar de
        // verdade. Duplicar linha (Shift-Alt-seta) também não vem por
        // padrão, precisa vincular explicitamente.
        keymap.of([
            indentWithTab,
            { key: 'Shift-Alt-ArrowDown', run: copyLineDown },
            { key: 'Shift-Alt-ArrowUp', run: copyLineUp },
            { key: 'Mod-/', run: toggleComment },
            // Mod- = Cmd no Mac, Ctrl em todo o resto (convenção do
            // próprio CodeMirror) — preventDefault evita o diálogo
            // nativo "Salvar página" do navegador disparando junto.
            // requestSubmit() (não submit()) porque o listener de
            // 'submit' logo abaixo é quem sincroniza o conteúdo do
            // editor de volta pro <textarea> antes de enviar — .submit()
            // pula esse listener.
            {
                key: 'Mod-s',
                preventDefault: true,
                run: () => {
                    textarea.closest('form')?.requestSubmit();

                    return true;
                },
            },
        ]),
        EditorView.lineWrapping,
        EditorView.updateListener.of((update) => {
            if (update.docChanged) {
                textarea.value = update.state.doc.toString();
            }
        }),
        // Vazio até o import() dinâmico resolver — reconfigurado com a
        // extensão de linguagem real assim que o chunk carrega.
        languageCompartment.of([]),
    ];

    const view = new EditorView({
        doc: textarea.value,
        extensions,
    });

    if (languageLoader) {
        languageLoader().then((language) => {
            view.dispatch({ effects: languageCompartment.reconfigure(language) });
        });
    }

    themeCompartments.push({ view, compartment: themeCompartment });

    const wrapper = document.createElement('div');
    wrapper.className = 'code-editor-wrapper';

    const toolbar = document.createElement('div');
    toolbar.className = 'd-flex justify-content-end gap-2 mb-1';

    const fullscreenToggle = document.createElement('button');
    fullscreenToggle.type = 'button';
    fullscreenToggle.className = 'btn btn-sm btn-outline-secondary';

    const updateFullscreenLabel = () => {
        fullscreenToggle.textContent = wrapper.classList.contains('is-fullscreen') ? '↙ Sair da tela cheia' : '⛶ Tela cheia';
    };
    updateFullscreenLabel();

    const setFullscreen = (enabled) => {
        wrapper.classList.toggle('is-fullscreen', enabled);
        document.body.style.overflow = enabled ? 'hidden' : '';
        updateFullscreenLabel();
        view.requestMeasure();
    };

    fullscreenToggle.addEventListener('click', () => setFullscreen(!wrapper.classList.contains('is-fullscreen')));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && wrapper.classList.contains('is-fullscreen')) {
            setFullscreen(false);
        }
    });

    toolbar.append(fullscreenToggle);

    textarea.insertAdjacentElement('afterend', wrapper);
    wrapper.append(toolbar, view.dom);
    textarea.style.display = 'none';

    const form = textarea.closest('form');
    const saveButton = form?.querySelector('button[type="submit"]');

    if (saveButton) {
        saveButton.classList.add('mt-2');
        wrapper.appendChild(saveButton);
    }

    const feedback = document.createElement('span');
    feedback.className = 'small ms-2';
    wrapper.appendChild(feedback);

    let feedbackTimeout;

    function showFeedback(ok, message) {
        clearTimeout(feedbackTimeout);
        feedback.textContent = message;
        feedback.className = 'small ms-2 ' + (ok ? 'text-success' : 'text-danger');
        feedbackTimeout = setTimeout(() => { feedback.textContent = ''; }, 3000);
    }

    // Salva via fetch em vez de deixar o <form> navegar normalmente —
    // um POST clássico recarrega a página inteira, e num arquivo grande
    // isso joga quem está editando lá pela linha 1000 de volta pro
    // topo toda vez que salva. O controller (FileManagerController::
    // update) reconhece o cabeçalho Accept: application/json e devolve
    // JSON em vez de redirect nesse caso.
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        textarea.value = view.state.doc.toString();

        if (saveButton) {
            saveButton.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });
            const data = await response.json().catch(() => ({}));

            showFeedback(response.ok, data.message ?? (response.ok ? 'Salvo.' : 'Falha ao salvar.'));
        } catch (error) {
            showFeedback(false, 'Falha ao salvar — sem conexão.');
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
            }
        }
    });
});
