import Swiper from 'swiper';
import { Autoplay, EffectFade, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.querySelector('.hero-slider');
    if (!el) return;

    new Swiper(el, {
        modules: [Autoplay, EffectFade, Navigation, Pagination],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        loop: el.querySelectorAll('.swiper-slide').length > 1,
        autoplay: { delay: 6000, disableOnInteraction: false },
        pagination: { el: '.hero-slider-pagination', clickable: true },
        navigation: {
            nextEl: '.hero-slider-next',
            prevEl: '.hero-slider-prev',
        },
    });
});
