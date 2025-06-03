// JS para la vista de editar producto promocional
// Extraído de edit.php.twig

document.addEventListener('DOMContentLoaded', function() {
    const componentsContainer = document.getElementById('componentsContainer');
    const addComponentBtn = document.getElementById('addComponentBtn');
    const form = document.querySelector('form[action^="/admin/promotional/edit/"]');
    const promoCodeField = document.getElementById('promoCodeField');
    const loyaltyPointsField = document.getElementById('loyaltyPointsField');
    const discountEnabled = window.discountEnabled;
    const fidelityEnabled = window.fidelityEnabled;

    function updatePromoFieldsVisibility() {
        if (discountEnabled && !fidelityEnabled) {
            promoCodeField.style.display = '';
            loyaltyPointsField.style.display = 'none';
        } else if (!discountEnabled && fidelityEnabled) {
            promoCodeField.style.display = 'none';
            loyaltyPointsField.style.display = '';
        } else if (discountEnabled && fidelityEnabled) {
            promoCodeField.style.display = '';
            loyaltyPointsField.style.display = '';
        } else {
            promoCodeField.style.display = 'none';
            loyaltyPointsField.style.display = 'none';
        }
    }
    updatePromoFieldsVisibility();

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(form);
        // Imagen desde file-upload (solo si se sube una nueva, no obligatoria)
        let imgFile = null;
        const imgUpload = document.getElementById('imgUpload');
        if (imgUpload && typeof imgUpload.getFileInput === 'function') {
            const fileInput = imgUpload.getFileInput();
            if (fileInput && fileInput.length > 0 && fileInput[0]) {
                imgFile = fileInput[0];
            }
        }
        if (imgFile) {
            formData.append('image', imgFile);
        }
        // Componentes: productos o categorías
        formData.delete('components[]');
        const rows = componentsContainer.querySelectorAll('.component-row');
        let valid = true;
        let totalComponents = 0;
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
                formData.append('components[]', JSON.stringify({ type, ids }));
            }
        });
        if (!valid || totalComponents < 1) {
            alert('Debes seleccionar al menos un producto o categoría como componente.');
            return;
        }
        // Validación de campos según configuración
        const promoCode = form.querySelector('#promo_code') ? form.querySelector('#promo_code').value.trim() : '';
        const loyaltyPoints = form.querySelector('#loyalty_points') ? form.querySelector('#loyalty_points').value.trim() : '';
        if (discountEnabled && !fidelityEnabled && !promoCode) {
            alert('El código promocional es obligatorio.');
            return;
        }
        if (fidelityEnabled && !discountEnabled && !loyaltyPoints) {
            alert('Los puntos de fidelización son obligatorios.');
            return;
        }
        if (discountEnabled && fidelityEnabled && !promoCode && !loyaltyPoints) {
            alert('Debes rellenar al menos el código promocional o los puntos de fidelización.');
            return;
        }
        // Validación de código promocional único (AJAX, si se ha introducido)
        const promoCodeError = document.getElementById('promoCodeError');
        if (promoCode) {
            fetch(`/admin/promotional/check-code?code=${encodeURIComponent(promoCode)}&exclude=${window.composedId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exists) {
                        promoCodeError.classList.remove('hidden');
                    } else {
                        promoCodeError.classList.add('hidden');
                        submitPromoForm(formData);
                    }
                })
                .catch(() => {
                    // Si hay error en la petición, permitir el envío para no bloquear al usuario
                    submitPromoForm(formData);
                });
        } else {
            submitPromoForm(formData);
        }
    });
    function submitPromoForm(formData) {
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.href = '/admin/promotional';
            } else {
                alert('Error al guardar el producto promocional: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud. Por favor, inténtalo de nuevo más tarde.');
        });
    }
    // Botón eliminar componente
    componentsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-component-btn')) {
            const rows = componentsContainer.querySelectorAll('.component-row');
            if (rows.length > 1) {
                e.target.closest('.component-row').remove();
            }
        }
    });
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
    // --- CARGA INICIAL DE COMPONENTES SELECCIONADOS ---
    if (window.componentsData && Array.isArray(window.componentsData)) {
        componentsContainer.innerHTML = '';
        window.componentsData.forEach(function(component) {
            const newRow = document.createElement('div');
            newRow.className = 'component-row flex gap-2 items-center';
            newRow.innerHTML = document.querySelector('.component-row').innerHTML;
            // Set tipo
            newRow.querySelector('.component-type-select').value = component.type;
            if (component.type === 'product') {
                newRow.querySelector('.component-product-select').style.display = '';
                newRow.querySelector('.component-category-select').style.display = 'none';
                newRow.querySelector('.component-category-filter').style.display = '';
                Array.from(newRow.querySelector('.component-product-select').options).forEach(opt => {
                    opt.selected = component.ids.includes(opt.value);
                });
            } else {
                newRow.querySelector('.component-product-select').style.display = 'none';
                newRow.querySelector('.component-category-select').style.display = '';
                newRow.querySelector('.component-category-filter').style.display = 'none';
                newRow.querySelector('.component-category-select').value = component.ids[0] || '';
            }
            newRow.querySelector('.remove-component-btn').style.display = window.componentsData.length > 1 ? '' : 'none';
            componentsContainer.appendChild(newRow);
        });
    }
    // Exponer flags globales para el JS
    window.discountEnabled = typeof discountEnabled !== 'undefined' ? discountEnabled : false;
    window.fidelityEnabled = typeof fidelityEnabled !== 'undefined' ? fidelityEnabled : false;
});
