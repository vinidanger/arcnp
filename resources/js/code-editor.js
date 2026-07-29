import { EditorView, basicSetup } from 'codemirror';
import { keymap } from '@codemirror/view';
import { Compartment } from '@codemirror/state';
import { indentWithTab, copyLineDown, copyLineUp } from '@codemirror/commands';
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

const THEME_STORAGE_KEY = 'arcnp-editor-theme';

function languageForFilename(filename) {
    const extension = filename.split('.').pop().toLowerCase();

    return LANGUAGE_BY_EXTENSION[extension] ?? null;
}

function preferredTheme() {
    return localStorage.getItem(THEME_STORAGE_KEY) === 'light' ? 'light' : 'dark';
}

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
        themeCompartment.of(preferredTheme() === 'dark' ? oneDark : []),
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

    const wrapper = document.createElement('div');
    wrapper.className = 'code-editor-wrapper';

    const toolbar = document.createElement('div');
    toolbar.className = 'd-flex justify-content-end gap-2 mb-1';

    const themeToggle = document.createElement('button');
    themeToggle.type = 'button';
    themeToggle.className = 'btn btn-sm btn-outline-secondary';

    const updateToggleLabel = () => {
        themeToggle.textContent = preferredTheme() === 'dark' ? '☀️ Tema claro' : '🌙 Tema escuro';
    };
    updateToggleLabel();

    themeToggle.addEventListener('click', () => {
        const next = preferredTheme() === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_STORAGE_KEY, next);
        view.dispatch({ effects: themeCompartment.reconfigure(next === 'dark' ? oneDark : []) });
        updateToggleLabel();
    });

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

    toolbar.append(themeToggle, fullscreenToggle);

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
