// Validación visual de contraseña segura para el registro

document.addEventListener('DOMContentLoaded', function() {
    if(window.feather){window.feather.replace();}
    const passwordInput = document.getElementById('password');
    const requirements = [
        { regex: /.{8,}/, selector: 'li:nth-child(1)' },
        { regex: /[A-Z]/, selector: 'li:nth-child(2)' },
        { regex: /[a-z]/, selector: 'li:nth-child(3)' },
        { regex: /[0-9]/, selector: 'li:nth-child(4)' },
        { regex: /[^a-zA-Z0-9]/, selector: 'li:nth-child(5)' }
    ];
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const value = passwordInput.value;
            requirements.forEach(req => {
                const li = passwordInput.parentElement.parentElement.querySelector(req.selector);
                if (li) {
                    if (req.regex.test(value)) {
                        li.classList.add('text-green-600');
                        li.classList.remove('text-gray-500');
                    } else {
                        li.classList.remove('text-green-600');
                        li.classList.add('text-gray-500');
                    }
                }
            });
        });
    }
});
