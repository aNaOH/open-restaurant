// JS para la vista principal (index)
// Extraído de index.php.twig

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productModal');
    document.querySelectorAll('.product-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.getElementById('modalImage').src = card.dataset.image;
            document.getElementById('modalName').textContent = card.dataset.name;
            document.getElementById('modalPrice').textContent = '$' + card.dataset.price;
            // Decodifica entidades HTML para mostrar correctamente los caracteres especiales
            const txt = document.createElement('textarea');
            txt.innerHTML = card.dataset.description;
            document.getElementById('modalDescription').textContent = txt.value;
            modal.showModal();
            setTimeout(() => {
                modal.classList.add('fade-in');
                modal.classList.remove('fade-out');
            }, 10);
        });
    });
    document.getElementById('closeModalBtn').addEventListener('click', function() {
        modal.classList.remove('fade-in');
        modal.classList.add('fade-out');
        setTimeout(() => {
            modal.close();
        }, 300);
    });
    // Cierra el modal si se hace click fuera del contenido
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('fade-in');
            modal.classList.add('fade-out');
            setTimeout(() => {
                modal.close();
            }, 300);
        }
    });
    // Al cerrar el modal, asegúrate de quitar las clases de animación
    modal.addEventListener('close', function() {
        modal.classList.remove('fade-in', 'fade-out');
    });
    // Botón volver arriba
    const backToTopBtn = document.getElementById('backToTopBtn');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 200) {
            backToTopBtn.classList.remove('hidden');
        } else {
            backToTopBtn.classList.add('hidden');
        }
    });
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
