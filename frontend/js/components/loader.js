const loader = document.querySelector('.page-loader');

if (loader) {
    const hideLoader = () => {
        loader.classList.add('is-hidden');
        document.body.classList.remove('is-loading');

        window.setTimeout(() => loader.remove(), 450);
    };

    if (document.readyState === 'complete') {
        window.setTimeout(hideLoader, 120);
    } else {
        window.addEventListener('load', () => window.setTimeout(hideLoader, 120), { once: true });
    }

    window.setTimeout(hideLoader, 5000);
}
