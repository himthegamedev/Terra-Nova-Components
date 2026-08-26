(function () {
    function highlight(selector, highlightClass) {
        const targets = document.querySelectorAll(selector);

        targets.forEach((element) => {
            element.addEventListener('mouseenter', () => {
                element.classList.add(highlightClass);
            });

            element.addEventListener('mouseleave', () => {
                element.classList.remove(highlightClass);
            });
        });
    }

    function stickyNav(selector, className, threshold = 100) {
        const navbar = document.querySelector(selector);

        if (!navbar) return;

        const handleScroll = () => {
            navbar.classList.toggle(className, window.scrollY > threshold);
        };

        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    function sideScrollGallery(selector, waitTime = 4000) {
        const gallery = document.querySelector(selector);

        if (!gallery) return;

        const track = gallery.querySelector('.gallery-track');
        const slides = track ? track.querySelectorAll('.slide') : [];
        const nextButton = gallery.querySelector('.gallery-next');
        const prevButton = gallery.querySelector('.gallery-prev');

        if (!track || slides.length === 0 || !nextButton || !prevButton) return;

        let current = 0;
        let timer;

        function showImage(index) {
            if (index >= slides.length) {
                current = 0;
            } else if (index < 0) {
                current = slides.length - 1;
            } else {
                current = index;
            }

            track.style.transform = `translateX(-${current * 100}vw)`;
        }

        function resetTimer() {
            clearTimeout(timer);

            timer = setTimeout(() => {
                showImage(current + 1);
                resetTimer();
            }, waitTime);
        }

        function nextImage() {
            showImage(current + 1);
            resetTimer();
        }

        function previousImage() {
            showImage(current - 1);
            resetTimer();
        }

        nextButton.addEventListener('click', nextImage);
        prevButton.addEventListener('click', previousImage);

        showImage(0);
        resetTimer();
    }

    function updateLogoFade() {
        const fadeDistance = 400;
        const progress = Math.min(window.scrollY / fadeDistance, 1);
        const largeLogo = document.querySelector('.logo-img');
        const navLogo = document.querySelector('.nav-logo');

        if (largeLogo) {
            largeLogo.style.opacity = 1 - progress;
        }

        if (navLogo) {
            navLogo.style.opacity = progress;
        }
    }

    function cycleProductPreview(selector, interval = 3500) {
        const headings = document.querySelectorAll(`${selector} .quick-product-heading`);
        const summary = document.querySelector(`${selector} .quick-product-summary`);

        if (headings.length < 2 || !summary) return;

        let activeIndex = 0;

        setInterval(() => {
            headings[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % headings.length;
            headings[activeIndex].classList.add('is-active');
            summary.textContent = headings[activeIndex].dataset.summary;
        }, interval);
    }

    stickyNav('.Nav', 'stickyNav');
    sideScrollGallery('.gallery', 10000);
    cycleProductPreview('.product-preview');
    updateLogoFade();

    window.addEventListener('scroll', updateLogoFade, { passive: true });

    highlight('#contacts', 'highlight');
    highlight('#manubtn', 'highlight');
    highlight('#productbtn', 'highlight');
})();