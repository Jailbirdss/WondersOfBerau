document.addEventListener('DOMContentLoaded', function () {
    const hero = document.querySelector('.hero');
    if (!hero) return;

    const layers = Array.from(document.querySelectorAll('.hero-bg'));
    if (layers.length < 2) return;

    let datasetImages = [];
    try {
        datasetImages = JSON.parse(hero.dataset.heroImages || '[]');
    } catch (e) {
        datasetImages = [];
    }

    const imageNodes = Array.from(document.querySelectorAll('#unggulan .card img'));
    const domImages = imageNodes.map(img => img.getAttribute('src')).filter(Boolean);

    let images = Array.from(new Set([...datasetImages, ...domImages].filter(Boolean)));

    if (!images.length) return;

    const overlay = 'linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5))';
    const initial = hero.dataset.initialHero || images[0];
    if (initial && !images.includes(initial)) {
        images.unshift(initial);
    }

    const prevBtn = document.querySelector('.hero-prev');
    const nextBtn = document.querySelector('.hero-next');

    let currentIndex = Math.max(images.indexOf(initial), 0);
    let timerId = null;
    let activeLayer = 0;

    const setLayerBackground = (layer, src) => {
        layer.style.backgroundImage = `${overlay}, url('${src}')`;
    };

    const goToIndex = (index) => {
        currentIndex = (index + images.length) % images.length;
        const nextLayer = layers[activeLayer ^ 1];
        setLayerBackground(nextLayer, images[currentIndex]);

        requestAnimationFrame(() => {
            layers[activeLayer].classList.remove('active');
            nextLayer.classList.add('active');
            activeLayer ^= 1;
        });
    };

    const nextSlide = () => goToIndex(currentIndex + 1);
    const prevSlide = () => goToIndex(currentIndex - 1);

    const startAutoPlay = () => {
        clearInterval(timerId);
        timerId = setInterval(nextSlide, 10000);
    };

    setLayerBackground(layers[activeLayer], images[currentIndex]);
    setLayerBackground(layers[activeLayer ^ 1], images[currentIndex]);
    layers[activeLayer].classList.add('active');
    startAutoPlay();

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            startAutoPlay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            startAutoPlay();
        });
    }
});