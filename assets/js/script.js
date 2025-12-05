// ARQUIVO: assets/js/script.js

document.addEventListener('DOMContentLoaded', () => {
    const btnMobile = document.getElementById('btn-mobile');
    const mobileMenu = document.getElementById('mobile-menu');

    if (btnMobile && mobileMenu) {
        btnMobile.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            mobileMenu.classList.toggle('hidden'); // Tailwind utility
        });
    }
});