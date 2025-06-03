// JS para la vista de crear producto compuesto
// Extraído de add.php.twig

document.addEventListener('DOMContentLoaded', function() {
    const componentsContainer = document.getElementById('componentsContainer');
    const addComponentBtn = document.getElementById('addComponentBtn');
    function updateRemoveComponentButtons() {
        const rows = componentsContainer.querySelectorAll('.component-row');
        rows.forEach((row, idx) => {
            const btn = row.querySelector('.remove-component-btn');
            btn.style.display = (rows.length > 2) ? '' : 'none';
        });
    }
    addComponentBtn.addEventListener('click', function() {
        const firstRow = componentsContainer.querySelector('.component-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelector('.component-type-select').value = 'product';
        newRow.querySelector('.component-product-select').style.display = '';
        Array.from(newRow.querySelector('.component-product-select').options).forEach(opt => opt.selected = false);
        newRow.querySelector('.component-category-select').style.display = 'none';
        newRow.querySelector('.component-category-select').selectedIndex = 0;
        newRow.querySelector('.component-category-filter').style.display = '';
        newRow.querySelector('.component-category-filter').selectedIndex = 0;
        newRow.querySelector('.remove-component-btn').style.display = '';
        componentsContainer.appendChild(newRow);
        updateRemoveComponentButtons();
    });
    componentsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-component-btn')) {
            const rows = componentsContainer.querySelectorAll('.component-row');
            if (rows.length > 2) {
                e.target.closest('.component-row').remove();
                updateRemoveComponentButtons();
            }
        }
    });
    componentsContainer.addEventListener('change', function(e) {
        if (e.target.classList.contains('component-type-select')) {
            const row = e.target.closest('.component-row');
            if (e.target.value === 'product') {
                row.querySelector('.component-product-select').style.display = '';
                row.querySelector('.component-category-select').style.display = 'none';
                row.querySelector('.component-category-filter').style.display = '';
            } else {
                row.querySelector('.component-product-select').style.display = 'none';
                row.querySelector('.component-category-select').style.display = '';
                row.querySelector('.component-category-filter').style.display = 'none';
            }
        }
        if (e.target.classList.contains('component-category-filter')) {
            const row = e.target.closest('.component-row');
            const filterValue = e.target.value;
            const productSelect = row.querySelector('.component-product-select');
            Array.from(productSelect.options).forEach(opt => {
                if (!filterValue || opt.dataset.category === filterValue) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                    opt.selected = false;
                }
            });
        }
    });
    updateRemoveComponentButtons();
    // --- FILE UPLOAD LOGIC & SUBMIT ---
    const form = document.querySelector('form[action="/admin/composed/add"]');
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
                alert('Error al crear el producto compuesto: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud. Por favor, inténtalo de nuevo más tarde.');
        });
    });
});
