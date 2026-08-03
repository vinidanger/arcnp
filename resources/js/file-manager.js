// Gerenciador de arquivos: upload por arrastar/soltar (só aparece
// enquanto arrasta), menu de contexto (botão direito) com suporte a
// seleção múltipla (shift+clique), preview de imagem, e modais pra
// criar pasta/arquivo — tudo num arquivo só, reaproveitado nas telas
// de admin e cliente. Toda URL/estado vem de atributos data-* no
// elemento raiz (#file-manager), já que aqui não tem acesso ao helper
// route() do Laravel.
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('file-manager');

    if (! root) {
        return;
    }

    const config = {
        uploadUrl: root.dataset.uploadUrl,
        destroyUrl: root.dataset.destroyUrl,
        compressUrl: root.dataset.compressUrl,
        extractUrl: root.dataset.extractUrl,
        currentPath: root.dataset.currentPath || '',
        rootDomain: root.dataset.root || '',
        csrfToken: root.dataset.csrf,
    };

    setupDropzone(config);
    setupSelection();
    setupImagePreview();
    setupContextMenu(config);
    setupToolbarSelectionActions(config);
});

function submitHiddenForm(url, fields, method = 'POST', csrfToken) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    const csrf = document.createElement('input');
    csrf.name = '_token';
    csrf.value = csrfToken;
    form.appendChild(csrf);

    if (method !== 'POST') {
        const methodField = document.createElement('input');
        methodField.name = '_method';
        methodField.value = method;
        form.appendChild(methodField);
    }

    for (const [key, value] of Object.entries(fields)) {
        if (Array.isArray(value)) {
            value.forEach((v) => {
                const input = document.createElement('input');
                input.name = `${key}[]`;
                input.value = v;
                form.appendChild(input);
            });
            continue;
        }

        const input = document.createElement('input');
        input.name = key;
        input.value = value ?? '';
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}

function getSelectedPaths() {
    return Array.from(document.querySelectorAll('.file-select:checked')).map((cb) => cb.value);
}

function confirmAndDelete(paths, config) {
    if (! paths.length) {
        return;
    }
    const message = paths.length > 1
        ? `Remove ${paths.length} itens definitivamente. Continuar?`
        : 'Remove definitivamente. Continuar?';
    if (! confirm(message)) {
        return;
    }
    submitHiddenForm(config.destroyUrl, { paths, current_path: config.currentPath, root: config.rootDomain }, 'DELETE', config.csrfToken);
}

/**
 * A faixa de "solte aqui" só aparece enquanto um arrasto de arquivo
 * está de fato acontecendo sobre a página — dragenter/dragleave
 * disparam também pra filhos do elemento (bolha), por isso o contador
 * em vez de mostrar/esconder direto em cada evento (senão pisca).
 */
function setupDropzone(config) {
    const overlay = document.getElementById('file-dropzone-overlay');
    const fileInput = document.getElementById('file-upload-input');
    const importBtn = document.getElementById('btn-import');
    const statusEl = document.getElementById('upload-status');

    // Upload em si não depende da faixa de arrastar existir — botão
    // "Importar" tem que funcionar mesmo se o overlay falhar por
    // algum motivo (eram a mesma função antes, um dependia do outro
    // sem necessidade).
    const uploadFiles = (fileList) => {
        if (! fileList || ! fileList.length) {
            return;
        }

        const formData = new FormData();
        Array.from(fileList).forEach((file) => formData.append('files[]', file));
        formData.append('current_path', config.currentPath);
        formData.append('root', config.rootDomain);
        formData.append('_token', config.csrfToken);

        if (statusEl) {
            statusEl.textContent = `Enviando ${fileList.length} arquivo(s)...`;
            statusEl.classList.remove('d-none', 'text-danger');
        }

        fetch(config.uploadUrl, { method: 'POST', body: formData, headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                if (statusEl && data.errors && data.errors.length) {
                    statusEl.textContent = `Falha em ${data.errors.length} arquivo(s): ${data.errors.join(' | ')}`;
                    statusEl.classList.add('text-danger');
                }

                if (data.uploaded > 0) {
                    window.location.reload();
                }
            })
            .catch(() => {
                if (statusEl) {
                    statusEl.textContent = 'Falha ao enviar arquivos.';
                    statusEl.classList.add('text-danger');
                }
            });
    };

    if (importBtn && fileInput) {
        importBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            uploadFiles(fileInput.files);
            fileInput.value = '';
        });
    }

    if (! overlay) {
        return;
    }

    let dragCounter = 0;
    const hasFiles = (e) => Array.from(e.dataTransfer?.types || []).includes('Files');

    window.addEventListener('dragenter', (e) => {
        if (! hasFiles(e)) {
            return;
        }
        e.preventDefault();
        dragCounter++;
        overlay.classList.remove('d-none');
    });

    window.addEventListener('dragover', (e) => {
        if (hasFiles(e)) {
            e.preventDefault();
        }
    });

    window.addEventListener('dragleave', (e) => {
        if (! hasFiles(e)) {
            return;
        }
        dragCounter = Math.max(0, dragCounter - 1);
        if (dragCounter === 0) {
            overlay.classList.add('d-none');
        }
    });

    window.addEventListener('drop', (e) => {
        if (! hasFiles(e)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        dragCounter = 0;
        overlay.classList.add('d-none');
        uploadFiles(e.dataTransfer.files);
    });
}

/**
 * Shift+clique numa checkbox seleciona o intervalo inteiro entre ela e
 * a última clicada — mesmo comportamento de qualquer explorador de
 * arquivos/Gmail.
 */
function setupSelection() {
    const checkboxes = Array.from(document.querySelectorAll('.file-select'));
    let lastIndex = null;

    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('click', (e) => {
            if (e.shiftKey && lastIndex !== null) {
                const [start, end] = [lastIndex, index].sort((a, b) => a - b);
                for (let i = start; i <= end; i++) {
                    checkboxes[i].checked = true;
                }
            }
            lastIndex = index;
        });
    });
}

/**
 * Clique no nome de uma imagem abre um preview em modal (via a mesma
 * URL de download — <img> sempre renderiza inline, o cabeçalho de
 * download não afeta isso) em vez de tentar "editar" um binário.
 */
function setupImagePreview() {
    window.openImagePreview = (url, name) => {
        const img = document.getElementById('image-preview-img');
        const title = document.getElementById('image-preview-title');
        if (! img || ! title) {
            return;
        }
        img.src = url;
        title.textContent = name;
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('image-preview-modal')).show();
    };

    document.querySelectorAll('.file-preview-image').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const row = link.closest('.file-row');
            window.openImagePreview(row.dataset.downloadUrl, row.dataset.name);
        });
    });
}

function setupContextMenu(config) {
    const menu = document.getElementById('file-context-menu');
    const rows = Array.from(document.querySelectorAll('#file-table tbody tr[data-path]'));

    if (! menu || ! rows.length) {
        return;
    }

    let selectedPaths = [];
    let singleRow = null;

    const hideMenu = () => { menu.style.display = 'none'; };
    const toggleItem = (ctx, hidden) => {
        const item = menu.querySelector(`[data-ctx="${ctx}"]`);
        if (item) {
            item.closest('li').classList.toggle('d-none', hidden);
        }
    };

    rows.forEach((row) => {
        row.addEventListener('contextmenu', (e) => {
            e.preventDefault();

            const rowCheckbox = row.querySelector('.file-select');
            const checked = Array.from(document.querySelectorAll('.file-select:checked'));

            if (checked.length > 1 && rowCheckbox.checked) {
                // Botão direito num item que já faz parte de uma seleção
                // múltipla: as ações valem pra seleção inteira.
                selectedPaths = checked.map((cb) => cb.value);
                singleRow = null;
            } else {
                // Botão direito fora da seleção atual: reseta e seleciona
                // só esse item (mesma lógica de qualquer explorador).
                document.querySelectorAll('.file-select').forEach((cb) => { cb.checked = false; });
                rowCheckbox.checked = true;
                selectedPaths = [row.dataset.path];
                singleRow = row;
            }

            const isMulti = selectedPaths.length > 1;
            const kind = singleRow?.dataset.kind;

            toggleItem('open', isMulti || ! singleRow || kind === 'other');
            toggleItem('download', isMulti || ! singleRow || singleRow.dataset.type !== 'file');
            toggleItem('rename', isMulti);
            toggleItem('extract', isMulti || ! singleRow || singleRow.dataset.zip !== '1');

            menu.style.display = 'block';
            const menuWidth = menu.offsetWidth || 200;
            const maxLeft = window.innerWidth - menuWidth - 8;
            menu.style.left = Math.min(e.pageX, maxLeft) + 'px';
            menu.style.top = e.pageY + 'px';
        });
    });

    document.addEventListener('click', hideMenu);
    window.addEventListener('scroll', hideMenu);

    menu.querySelector('[data-ctx="open"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! singleRow) {
            return;
        }
        if (singleRow.dataset.kind === 'image') {
            window.openImagePreview(singleRow.dataset.downloadUrl, singleRow.dataset.name);
        } else {
            window.location.href = singleRow.dataset.href;
        }
    });

    menu.querySelector('[data-ctx="download"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (singleRow) {
            window.location.href = singleRow.dataset.downloadUrl;
        }
    });

    menu.querySelector('[data-ctx="rename"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! singleRow) {
            return;
        }
        document.getElementById('rename-from').value = singleRow.dataset.path;
        document.getElementById('rename-name').value = singleRow.dataset.name;
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('rename-modal')).show();
    });

    menu.querySelector('[data-ctx="compress"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (selectedPaths.length) {
            window.openCompressModal(selectedPaths);
        }
    });

    menu.querySelector('[data-ctx="extract"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! singleRow) {
            return;
        }
        if (! confirm('Extrair aqui? Arquivos com o mesmo nome já existentes serão sobrescritos.')) {
            return;
        }
        submitHiddenForm(config.extractUrl, { path: singleRow.dataset.path, root: config.rootDomain }, 'POST', config.csrfToken);
    });

    menu.querySelector('[data-ctx="delete"]').addEventListener('click', (e) => {
        e.preventDefault();
        confirmAndDelete(selectedPaths, config);
    });

    window.openCompressModal = (paths) => {
        const container = document.getElementById('compress-paths');
        container.innerHTML = '';
        paths.forEach((p) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'paths[]';
            input.value = p;
            container.appendChild(input);
        });
        document.getElementById('compress-current-path').value = config.currentPath;
        document.getElementById('compress-root').value = config.rootDomain;
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('compress-modal')).show();
    };
}

function setupToolbarSelectionActions(config) {
    const compressBtn = document.getElementById('btn-compress-selected');
    const deleteBtn = document.getElementById('btn-delete-selected');

    if (compressBtn) {
        compressBtn.addEventListener('click', () => {
            const paths = getSelectedPaths();
            if (! paths.length) {
                alert('Selecione ao menos um item.');
                return;
            }
            window.openCompressModal(paths);
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            const paths = getSelectedPaths();
            if (! paths.length) {
                alert('Selecione ao menos um item.');
                return;
            }
            confirmAndDelete(paths, config);
        });
    }
}
