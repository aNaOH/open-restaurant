// JS para la vista de configuración admin
// Extraído de config.php.twig

document.addEventListener('DOMContentLoaded', function() {
    // --- LOGO UPLOAD AJAX ---
    const logoForm = document.querySelector('form[action="/admin/config/logo"]');
    if (logoForm) {
        logoForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const fileUpload = document.getElementById('restaurant_logo');
            if (!fileUpload || !fileUpload.getFileInput || !fileUpload.getFileInput()[0]) {
                alert('Por favor selecciona una imagen.');
                return;
            }
            const formData = new FormData();
            formData.append('restaurant_logo', fileUpload.getFileInput()[0]);
            fetch(logoForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Error al subir el logo.');
                }
            })
            .catch(() => alert('Error al subir el logo.'));
        });
    }

    // --- FIDELITY & PROMO WARNING MESSAGES ---
    const loyaltyCheckbox = document.getElementById('loyalty_program');
    const promoCheckbox = document.getElementById('promo_codes');
    const warningDiv = document.getElementById('fidelity-warning');

    function updateWarnings() {
        let showWarning = false;
        let warningMsg = '';
        if (!loyaltyCheckbox.checked) {
            showWarning = true;
            warningMsg = 'Al desactivar el programa de fidelización, todos los usuarios que no sean administradores ni empleados serán borrados.';
            if (!promoCheckbox.checked) {
                warningMsg += '<br><b>Además, al desactivar ambos, todos los productos promocionales serán borrados.</b>';
            }
        }
        if (showWarning) {
            warningDiv.innerHTML = warningMsg;
            warningDiv.classList.remove('hidden');
        } else {
            warningDiv.innerHTML = '';
            warningDiv.classList.add('hidden');
        }
    }
    if (loyaltyCheckbox && promoCheckbox && warningDiv) {
        loyaltyCheckbox.addEventListener('change', updateWarnings);
        promoCheckbox.addEventListener('change', updateWarnings);
        updateWarnings();
    }
});
