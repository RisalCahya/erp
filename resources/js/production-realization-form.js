document.querySelectorAll('[data-production-realization-form]').forEach((form) => {
    const productSearch = form.querySelector('[data-product-search]');
    const productId = form.querySelector('[data-product-id]');
    const products = [...form.querySelectorAll('[data-product-option]')];
    const quantityInput = form.querySelector('[data-finished-good-quantity]');
    const previewUrlTemplate = form.dataset.previewUrlTemplate;
    const productSummary = form.querySelector('[data-product-summary]');
    const formulaBody = form.querySelector('[data-formula-body]');
    const formulaMessage = form.querySelector('[data-formula-message]');
    const submitButton = form.querySelector('[data-save-draft]');
    const outputUnit = form.querySelector('[data-output-unit]');
    let bomItems = [];
    let outputUnitValue = '';
    let requestSequence = 0;

    if (!productSearch || !productId || !quantityInput || !formulaBody || !submitButton) {
        return;
    }

    const parseCents = (value) => {
        const match = String(value).trim().match(/^(\d+)(?:\.(\d{0,2}))?$/);
        if (!match) {
            return null;
        }

        return BigInt(match[1]) * 100n + BigInt((match[2] || '').padEnd(2, '0'));
    };

    const formatCents = (value) => `${value / 100n}.${String(value % 100n).padStart(2, '0')}`;
    const makeCell = (value, className = '') => {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = value;
        return cell;
    };

    const renderRows = () => {
        formulaBody.replaceChildren();

        let allValid = bomItems.length > 0;
        const quantity = parseCents(quantityInput.value);

        bomItems.forEach((item) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/80';
            const bomQuantity = parseCents(item.quantity_per_output);
            const available = parseCents(item.available_quantity);
            const required = quantity === null || bomQuantity === null
                ? null
                : (quantity * bomQuantity + 50n) / 100n;
            const shortage = required === null || available === null
                ? null
                : (required > available ? required - available : 0n);
            const status = shortage === null ? 'Isi Qty Finished Good'
                : shortage > 0n ? 'Kurang' : 'Cukup';

            if (required === null || available === null) {
                allValid = false;
            }
            row.append(
                makeCell(item.sku, 'whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-700'),
                makeCell(item.name, 'px-4 py-3 font-medium text-slate-900'),
                makeCell(item.unit, 'whitespace-nowrap px-4 py-3 text-slate-600'),
                makeCell(`${item.quantity_per_output} ${item.unit}`, 'whitespace-nowrap px-4 py-3 text-right text-slate-700'),
                makeCell(quantity === null ? '—' : `${formatCents(quantity)} ${outputUnitValue}`, 'whitespace-nowrap px-4 py-3 text-right text-slate-700'),
                makeCell(required === null ? '—' : `${formatCents(required)} ${item.unit}`, 'whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900'),
                makeCell(`${item.available_quantity} ${item.unit}`, 'whitespace-nowrap px-4 py-3 text-right text-slate-700'),
                makeCell(shortage === null ? '—' : `${formatCents(shortage)} ${item.unit}`, `whitespace-nowrap px-4 py-3 text-right font-semibold ${shortage > 0n ? 'text-red-700' : 'text-slate-500'}`),
                makeCell(status, `whitespace-nowrap px-4 py-3 text-xs font-semibold ${status === 'Kurang' ? 'text-red-700' : status === 'Cukup' ? 'text-emerald-700' : 'text-slate-500'}`),
            );
            formulaBody.append(row);
        });

        submitButton.disabled = !productId.value || bomItems.length === 0 || !allValid || !outputUnitValue;
        submitButton.classList.toggle('cursor-not-allowed', submitButton.disabled);
        submitButton.classList.toggle('opacity-50', submitButton.disabled);
    };

    const showMessage = (message) => {
        formulaMessage.textContent = message;
        formulaMessage.classList.toggle('hidden', !message);
    };

    const loadProduct = async (option) => {
        productId.value = option.dataset.id;
        productSummary.textContent = `${option.dataset.sku} — ${option.dataset.name}`;
        outputUnitValue = option.dataset.unit || '';
        outputUnit.textContent = outputUnitValue || 'Belum diatur';
        bomItems = [];
        renderRows();
        showMessage('Memuat formula BOM...');

        const sequence = ++requestSequence;
        const url = previewUrlTemplate.replace('__PRODUCT__', encodeURIComponent(productId.value));

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                throw new Error('Data produk dan BOM gagal dimuat.');
            }

            const data = await response.json();
            if (sequence !== requestSequence) {
                return;
            }

            outputUnitValue = data.product.output_unit || '';
            outputUnit.textContent = outputUnitValue || 'Belum diatur';
            bomItems = data.bom_items || [];
            showMessage(data.message || (!outputUnitValue ? 'Satuan output belum diatur pada Master Produk.' : ''));
            renderRows();
        } catch (error) {
            if (sequence === requestSequence) {
                productId.value = '';
                productSummary.textContent = 'Produk belum dipilih';
                outputUnitValue = '';
                outputUnit.textContent = 'Belum diatur';
                bomItems = [];
                renderRows();
                showMessage(error.message || 'Formula BOM gagal dimuat. Coba pilih produk kembali.');
            }
        }
    };

    const matchProduct = () => {
        const option = products.find((item) => item.dataset.label === productSearch.value.trim());
        productId.value = '';
        productSummary.textContent = 'Produk belum dipilih';
        outputUnitValue = '';
        outputUnit.textContent = '';
        bomItems = [];
        renderRows();

        if (!option) {
            showMessage(productSearch.value ? 'Pilih produk dari daftar SKU dan nama yang tersedia.' : '');
            requestSequence++;
            return;
        }

        loadProduct(option);
    };

    productSearch.addEventListener('change', matchProduct);
    productSearch.addEventListener('input', () => {
        if (!products.some((option) => option.dataset.label === productSearch.value.trim())) {
            productId.value = '';
            productSummary.textContent = 'Produk belum dipilih';
            outputUnitValue = '';
            outputUnit.textContent = '';
            bomItems = [];
            requestSequence++;
            renderRows();
            showMessage(productSearch.value ? 'Pilih produk dari daftar SKU dan nama yang tersedia.' : '');
        }
    });
    quantityInput.addEventListener('input', renderRows);

    form.addEventListener('submit', (event) => {
        if (!productId.value || bomItems.length === 0 || !parseCents(quantityInput.value)) {
            event.preventDefault();
            showMessage('Pilih produk dengan BOM dan isi Qty Finished Good lebih besar dari nol.');
        }
    });

    const oldProduct = products.find((option) => option.dataset.id === productId.value);
    if (oldProduct) {
        productSearch.value = oldProduct.dataset.label;
        loadProduct(oldProduct);
    }

    renderRows();
});
