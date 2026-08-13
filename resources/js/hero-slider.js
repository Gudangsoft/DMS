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
        // `loop: true` clones slides to fake an infinite loop, and with only
        // a handful of real slides that cloning can miscount and skip one of
        // them entirely (reported: 3 active sliders, only 2 ever appeared).
        // `rewind` gives the same "restart from slide 1 after the last one"
        // behavior without cloning anything, so every real slide is shown.
        rewind: true,
        autoplay: { delay: 6000, disableOnInteraction: false },
        pagination: { el: '.hero-slider-pagination', clickable: true },
        navigation: {
            nextEl: '.hero-slider-next',
            prevEl: '.hero-slider-prev',
        },
    });
});
