document.querySelectorAll('[data-auto-submit]').forEach((element) => {
    element.addEventListener('change', () => (element.form ?? element).requestSubmit());
});

document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        if (!window.confirm(element.dataset.confirm)) event.preventDefault();
    });
});

document.querySelectorAll('[data-print]').forEach((element) => {
    element.addEventListener('click', () => window.print());
});
