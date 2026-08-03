// Gerenciador de arquivos: upload por arrastar/soltar, menu de
// contexto (botão direito) substituindo os botões soltos de
// Renomear/Remover, e seleção múltipla pra compactar. Um arquivo só,
// reaproveitado nas telas de admin e cliente — toda URL/estado vem de
// atributos data-* no elemento raiz (#file-manager), já que aqui não
// tem acesso ao helper route() do Laravel.
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('file-manager');

    if (! root) {
        return;
    }

    const config = {
        uploadUrl: root.dataset.uploadUrl,
        renameUrl: root.dataset.renameUrl,
        destroyUrl: root.dataset.destroyUrl,
        compressUrl: root.dataset.compressUrl,
        extractUrl: root.dataset.extractUrl,
        currentPath: root.dataset.currentPath || '',
        rootDomain: root.dataset.root || '',
        csrfToken: root.dataset.csrf,
    };

    setupDropzone(root, config);
    setupContextMenu(root, config);
    setupCompressSelected(root, config);
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

function setupDropzone(root, config) {
    const dropzone = document.getElementById('file-dropzone');
    const fileInput = document.getElementById('file-upload-input');
    const browseBtn = document.getElementById('file-upload-browse');
    const statusEl = document.getElementById('upload-status');

    if (! dropzone) {
        return;
    }

    const uploadFiles = (fileList) => {
        if (! fileList || ! fileList.length) {
            return;
        }

        const formData = new FormData();
        Array.from(fileList).forEach((file) => formData.append('files[]', file));
        formData.append('current_path', config.currentPath);
        formData.append('root', config.rootDomain);
        formData.append('_token', config.csrfToken);

        statusEl.textContent = `Enviando ${fileList.length} arquivo(s)...`;
        statusEl.classList.remove('d-none', 'text-danger');

        fetch(config.uploadUrl, { method: 'POST', body: formData, headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                if (data.errors && data.errors.length) {
                    statusEl.textContent = `Falha em ${data.errors.length} arquivo(s): ${data.errors.join(' | ')}`;
                    statusEl.classList.add('text-danger');
                }

                if (data.uploaded > 0) {
                    window.location.reload();
                }
            })
            .catch(() => {
                statusEl.textContent = 'Falha ao enviar arquivos.';
                statusEl.classList.add('text-danger');
            });
    };

    ['dragenter', 'dragover'].forEach((evt) => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((evt) => {
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => uploadFiles(e.dataTransfer.files));

    if (browseBtn && fileInput) {
        browseBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => uploadFiles(fileInput.files));
    }
}

function setupContextMenu(root, config) {
    const menu = document.getElementById('file-context-menu');
    const rows = document.querySelectorAll('#file-table tbody tr[data-path]');

    if (! menu || ! rows.length) {
        return;
    }

    let activeRow = null;

    const hideMenu = () => { menu.style.display = 'none'; };

    rows.forEach((row) => {
        row.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            activeRow = row;

            const isZip = row.dataset.zip === '1';
            menu.querySelector('[data-ctx="extract"]').classList.toggle('d-none', ! isZip);

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
        if (activeRow) {
            window.location.href = activeRow.dataset.href;
        }
    });

    menu.querySelector('[data-ctx="rename"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! activeRow) {
            return;
        }
        document.getElementById('rename-from').value = activeRow.dataset.path;
        document.getElementById('rename-name').value = activeRow.dataset.name;
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('rename-modal')).show();
    });

    menu.querySelector('[data-ctx="compress"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (activeRow) {
            openCompressModal([activeRow.dataset.path]);
        }
    });

    menu.querySelector('[data-ctx="extract"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! activeRow) {
            return;
        }
        if (! confirm('Extrair aqui? Arquivos com o mesmo nome já existentes serão sobrescritos.')) {
            return;
        }
        submitHiddenForm(config.extractUrl, { path: activeRow.dataset.path, root: config.rootDomain }, 'POST', config.csrfToken);
    });

    menu.querySelector('[data-ctx="delete"]').addEventListener('click', (e) => {
        e.preventDefault();
        if (! activeRow) {
            return;
        }
        if (! confirm('Remove definitivamente. Continuar?')) {
            return;
        }
        submitHiddenForm(config.destroyUrl, { path: activeRow.dataset.path, root: config.rootDomain }, 'DELETE', config.csrfToken);
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

function setupCompressSelected(root, config) {
    const btn = document.getElementById('btn-compress-selected');

    if (! btn) {
        return;
    }

    btn.addEventListener('click', () => {
        const paths = Array.from(document.querySelectorAll('.file-select:checked')).map((cb) => cb.value);

        if (! paths.length) {
            alert('Selecione ao menos um item.');
            return;
        }

        window.openCompressModal(paths);
    });
}
