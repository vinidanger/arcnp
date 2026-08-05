import { EditorView, basicSetup } from 'codemirror';
import { keymap } from '@codemirror/view';
import { Compartment } from '@codemirror/state';
import { indentWithTab, copyLineDown, copyLineUp, toggleComment } from '@codemirror/commands';
import { indentUnit } from '@codemirror/language';
import { oneDark } from '@codemirror/theme-one-dark';
import { php } from '@codemirror/lang-php';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';
import { sql } from '@codemirror/lang-sql';
import { markdown } from '@codemirror/lang-markdown';
import { yaml } from '@codemirror/lang-yaml';

function isDarkTheme() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

const LANGUAGE_BY_EXTENSION = {
    php: php(),
    phtml: php(),
    html: html(),
    htm: html(),
    css: css(),
    js: javascript(),
    mjs: javascript(),
    cjs: javascript(),
    json: json(),
    sql: sql(),
    md: markdown(),
    yml: yaml(),
    yaml: yaml(),
};

function languageForFilename(filename) {
    const extension = filename.split('.').pop().toLowerCase();

    return LANGUAGE_BY_EXTENSION[extension] ?? null;
}

const themeCompartments = [];

// O toggle de tema global não recarrega a página — um MutationObserver
// no atributo data-bs-theme do <html> é o jeito de manter editores já
// abertos sincronizados quando o usuário troca o tema.
new MutationObserver(() => {
    const dark = isDarkTheme();

    themeCompartments.forEach(({ view, compartment }) => {
        view.dispatch({ effects: compartment.reconfigure(dark ? oneDark : []) });
    });
}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

document.querySelectorAll('textarea[data-code-editor]').forEach((textarea) => {
    const filename = textarea.dataset.filename ?? '';
    const language = languageForFilename(filename);
    const themeCompartment = new Compartment();

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
    ];

    if (language) {
        extensions.push(language);
    }

    const view = new EditorView({
        doc: textarea.value,
        extensions,
    });

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

    const saveButton = textarea.closest('form')?.querySelector('button[type="submit"]');

    if (saveButton) {
        saveButton.classList.add('mt-2');
        wrapper.appendChild(saveButton);
    }

    textarea.closest('form')?.addEventListener('submit', () => {
        textarea.value = view.state.doc.toString();
    });
});
