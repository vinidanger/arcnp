import { EditorView, basicSetup } from 'codemirror';
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

function languageForFilename(filename) {
    const extension = filename.split('.').pop().toLowerCase();

    return LANGUAGE_BY_EXTENSION[extension] ?? null;
}

document.querySelectorAll('textarea[data-code-editor]').forEach((textarea) => {
    const filename = textarea.dataset.filename ?? '';
    const language = languageForFilename(filename);

    const extensions = [
        basicSetup,
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

    textarea.insertAdjacentElement('afterend', view.dom);
    view.dom.classList.add('code-editor');
    textarea.style.display = 'none';

    textarea.closest('form')?.addEventListener('submit', () => {
        textarea.value = view.state.doc.toString();
    });
});
