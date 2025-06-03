// JS para la vista de editar producto compuesto
// Extraído de edit.php.twig

document.addEventListener('DOMContentLoaded', function() {
    const componentsContainer = document.getElementById('componentsContainer');
    const addComponentBtn = document.getElementById('addComponentBtn');
    const form = componentsContainer.closest('form');

    function createComponentRow(type = 'product') {
        const template = componentsContainer.querySelector('.component-row');
        const clone = template.cloneNode(true);
        clone.querySelector('.component-type-select').value = type;
        clone.querySelector('.component-category-filter').value = '';
        clone.querySelector('.component-product-select').selectedIndex = -1;
        clone.querySelector('.component-category-select').selectedIndex = 0;
        updateComponentType(clone, type);
        Array.from(clone.querySelectorAll('option[selected]')).forEach(o => o.selected = false);
        return clone;
    }

    function updateComponentType(row, type) {
        const productSelect = row.querySelector('.component-product-select');
        const categorySelect = row.querySelector('.component-category-select');
        const categoryFilter = row.querySelector('.component-category-filter');
        if (type === 'product') {
            productSelect.style.display = '';
            categorySelect.style.display = 'none';
            if (categoryFilter) categoryFilter.style.display = '';
        } else {
            productSelect.style.display = 'none';
            categorySelect.style.display = '';
            if (categoryFilter) categoryFilter.style.display = 'none';
        }
    }

    function filterProductsByCategory(row, categoryId) {
        const productSelect = row.querySelector('.component-product-select');
        Array.from(productSelect.options).forEach(opt => {
            if (!categoryId || opt.dataset.category === categoryId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
                opt.selected = false;
            }
        });
    }

    // Añadir componente
    addComponentBtn.addEventListener('click', function() {
        const newRow = createComponentRow();
        componentsContainer.appendChild(newRow);
        updateRemoveButtons();
    });

    // Delegación de eventos
    componentsContainer.addEventListener('change', function(e) {
        const row = e.target.closest('.component-row');
        if (e.target.classList.contains('component-type-select')) {
            updateComponentType(row, e.target.value);
        }
        if (e.target.classList.contains('component-category-filter')) {
            filterProductsByCategory(row, e.target.value);
        }
    });

    // Eliminar componente
    componentsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-component-btn')) {
            const row = e.target.closest('.component-row');
            if (componentsContainer.children.length > 2) {
                row.remove();
                updateRemoveButtons();
            } else {
                alert('Debe haber al menos dos componentes.');
            }
        }
    });

    // Ocultar botón eliminar si hay solo dos componentes
    function updateRemoveButtons() {
        const rows = componentsContainer.querySelectorAll('.component-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-component-btn');
            if (rows.length <= 2) {
                btn.style.display = 'none';
            } else {
                btn.style.display = '';
            }
        });
    }
    updateRemoveButtons();

    // Precarga: filtra productos por categoría si corresponde
    componentsContainer.querySelectorAll('.component-row').forEach(row => {
        const filter = row.querySelector('.component-category-filter');
        if (filter && filter.value) {
            filterProductsByCategory(row, filter.value);
        }
        // Ajusta visibilidad de selects según tipo
        const type = row.querySelector('.component-type-select').value;
        updateComponentType(row, type);
    });

    // AJAX para el submit del formulario
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(form);
        // Imagen desde file-upload (opcional)
        const imgUpload = document.getElementById('imgUpload');
        if (
            imgUpload &&
            typeof imgUpload.getFileInput === 'function' &&
            Array.isArray(imgUpload.getFileInput()) &&
            imgUpload.getFileInput().length > 0 &&
            imgUpload.getFileInput()[0]
        ) {
            formData.append('image', imgUpload.getFileInput()[0]);
        }
        // Componentes: productos o categorías
        for (let key of Array.from(formData.keys())) {
            if (key === 'components[]' || key === 'components') {
                formData.delete(key);
            }
        }
        const rows = componentsContainer.querySelectorAll('.component-row');
        let valid = true;
        let totalComponents = 0;
        let anyComponent = false;
        let componentsArray = [];
        rows.forEach(row => {
            const type = row.querySelector('.component-type-select').value;
            let ids = [];
            if (type === 'product') {
                const productSelect = row.querySelector('.component-product-select');
                const selectedOptions = Array.from(productSelect.options).filter(opt => opt.selected && opt.style.display !== 'none');
                if (selectedOptions.length) {
                    ids = selectedOptions.map(opt => opt.value);
                    totalComponents += ids.length;
                } else {
                    valid = false;
                }
            } else {
                const categorySelect = row.querySelector('.component-category-select');
                const selectedCategory = categorySelect.value;
                if (selectedCategory) {
                    ids.push(selectedCategory);
                    totalComponents++;
                } else {
                    valid = false;
                }
            }
            if (ids.length) {
                componentsArray.push({ type, ids });
                anyComponent = true;
            }
        });
        if (!anyComponent) {
            alert('Debes añadir al menos un componente.');
            return;
        }
        if (!valid || totalComponents < 2) {
            alert('Debes seleccionar al menos dos productos o categorías en total para los componentes.');
            return;
        }
        // Enviar los componentes como un solo campo JSON
        formData.append('components', JSON.stringify(componentsArray));
        // Envío del formulario
        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = '/admin/composed';
            } else {
                alert('Error al guardar el producto compuesto: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud. Por favor, inténtalo de nuevo más tarde.');
        });
    });
});
