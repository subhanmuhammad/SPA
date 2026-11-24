import 'bootstrap/js/dist/collapse';
import './behaviors';

const navbar = document.querySelector('.navbar');
const backToTop = document.querySelector('.back-to-top');

const onScroll = () => {
    navbar?.classList.toggle('sticky-top', window.scrollY > 45);
    navbar?.classList.toggle('shadow-sm', window.scrollY > 45);
    backToTop?.classList.toggle('d-flex', window.scrollY > 300);
};

window.addEventListener('scroll', onScroll, { passive: true });
onScroll();
