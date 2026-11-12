document.querySelectorAll('[data-material-combobox]').forEach((form) => {
    initializeCombobox(form, 'material');
});

document.querySelectorAll('[data-product-combobox]').forEach((form) => {
    initializeCombobox(form, 'product');
});

function initializeCombobox(form, type) {
    const search = form.querySelector(`[data-${type}-search]`);
    const selectedId = form.querySelector(`[data-${type}-id]`);
    const optionsList = form.querySelector(`[data-${type}-options]`);
    const options = [...form.querySelectorAll(`[data-${type}-option]`)];

    if (!search || !selectedId || !optionsList) {
        return;
    }

    const normalize = (value) => value.toLocaleLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    const visibleOptions = () => options.filter((option) => !option.hidden);
    const closeOptions = () => {
        optionsList.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
    };
    let suppressNextFocusOpen = false;

    const updateOptions = (showAll = false) => {
        const query = normalize(search.value);
        let visibleCount = 0;

        options.forEach((option) => {
            option.hidden = !showAll && query !== '' && !normalize(option.dataset.search).includes(query);
            visibleCount += Number(!option.hidden);
        });

        const isOpen = visibleCount > 0;
        optionsList.classList.toggle('hidden', !isOpen);
        search.setAttribute('aria-expanded', String(isOpen));
    };

    const selectOption = (option) => {
        selectedId.value = option.dataset.id;
        search.value = option.dataset.label;
        search.setCustomValidity('');
        options.forEach((item) => item.setAttribute('aria-selected', String(item === option)));
        optionsList.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
        search.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const previousSelection = options.find((option) => option.dataset.id === selectedId.value);
    if (previousSelection) {
        search.value = previousSelection.dataset.label;
        previousSelection.setAttribute('aria-selected', 'true');
    }

    search.addEventListener('focus', () => {
        if (suppressNextFocusOpen) {
            suppressNextFocusOpen = false;
            return;
        }

        updateOptions(true);
    });
    search.addEventListener('input', () => {
        selectedId.value = '';
        search.setCustomValidity('');
        options.forEach((option) => option.setAttribute('aria-selected', 'false'));
        updateOptions();
    });

    document.addEventListener('pointerdown', (event) => {
        if (
            event.button === 0
            && !search.contains(event.target)
            && !optionsList.contains(event.target)
        ) {
            closeOptions();
        }
    });

    options.forEach((option) => {
        option.addEventListener('click', () => selectOption(option));
    });

    search.addEventListener('keydown', (event) => {
        const visible = visibleOptions();

        if (event.key === 'ArrowDown' && visible.length > 0) {
            event.preventDefault();
            optionsList.classList.remove('hidden');
            search.setAttribute('aria-expanded', 'true');
            visible[0].focus();
        } else if (event.key === 'Enter' && !optionsList.classList.contains('hidden') && visible.length > 0) {
            event.preventDefault();
            selectOption(visible[0]);
        } else if (event.key === 'Escape') {
            closeOptions();
        }
    });

    options.forEach((option) => {
        option.addEventListener('keydown', (event) => {
            const visible = visibleOptions();
            const visibleIndex = visible.indexOf(option);

            if (event.key === 'ArrowDown' && visible[visibleIndex + 1]) {
                event.preventDefault();
                visible[visibleIndex + 1].focus();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                (visible[visibleIndex - 1] || search).focus();
            } else if (event.key === 'Escape') {
                closeOptions();
                suppressNextFocusOpen = true;
                search.focus();
            }
        });
    });

    form.addEventListener('submit', (event) => {
        if (!selectedId.value && !form.hasAttribute('data-combobox-optional')) {
            event.preventDefault();
            search.setCustomValidity(`Pilih ${type === 'product' ? 'produk' : 'material'} dari daftar yang tersedia.`);
            search.reportValidity();
        }
    });
}
