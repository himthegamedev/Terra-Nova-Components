(function () {

    /* =========================================================
       HOVER HIGHLIGHT
       ========================================================= */

    function highlight(selector, highlightClass) {

        const targets =
            document.querySelectorAll(selector);

        targets.forEach((element) => {

            element.addEventListener('mouseenter', () => {
                element.classList.add(highlightClass);
            });

            element.addEventListener('mouseleave', () => {
                element.classList.remove(highlightClass);
            });

        });
    }


    /* =========================================================
       STICKY NAVIGATION
       ========================================================= */

    function stickyNav(
        selector,
        className,
        threshold = 100
    ) {

        const navbar =
            document.querySelector(selector);

        if (!navbar) return;

        const handleScroll = () => {

            navbar.classList.toggle(
                className,
                window.scrollY > threshold
            );

        };

        handleScroll();

        window.addEventListener(
            'scroll',
            handleScroll,
            { passive: true }
        );
    }


    /* =========================================================
       MOBILE NAVIGATION
       ========================================================= */

    function mobileNavigation() {

        const hamburger =
            document.querySelector('#hamburger-button');

        const mobileMenu =
            document.querySelector('#mobile-menu');

        const menuLinks =
            document.querySelectorAll(
                '.mobile-menu-link'
            );

        const mobileQuoteBar =
            document.querySelector(
                '.mobile-quote-bar'
            );


        /* -----------------------------------------------
           HAMBURGER MENU
           ----------------------------------------------- */

        if (hamburger && mobileMenu) {

            hamburger.addEventListener(
                'click',
                function (event) {

                    event.stopPropagation();

                    const isOpen =
                        mobileMenu.classList.toggle(
                            'open'
                        );

                    hamburger.classList.toggle(
                        'active',
                        isOpen
                    );

                    hamburger.setAttribute(
                        'aria-expanded',
                        isOpen
                            ? 'true'
                            : 'false'
                    );

                    hamburger.setAttribute(
                        'aria-label',
                        isOpen
                            ? 'Close navigation menu'
                            : 'Open navigation menu'
                    );

                }
            );


            /* -------------------------------------------
               CLOSE MENU WHEN LINK IS CLICKED
               ------------------------------------------- */

            menuLinks.forEach((link) => {

                link.addEventListener(
                    'click',
                    function () {

                        mobileMenu.classList.remove(
                            'open'
                        );

                        hamburger.classList.remove(
                            'active'
                        );

                        hamburger.setAttribute(
                            'aria-expanded',
                            'false'
                        );

                        hamburger.setAttribute(
                            'aria-label',
                            'Open navigation menu'
                        );

                    }
                );

            });


            /* -------------------------------------------
               CLOSE MENU WHEN CLICKING OUTSIDE
               ------------------------------------------- */

            document.addEventListener(
                'click',
                function (event) {

                    const clickedMenu =
                        mobileMenu.contains(
                            event.target
                        );

                    const clickedHamburger =
                        hamburger.contains(
                            event.target
                        );

                    if (
                        !clickedMenu &&
                        !clickedHamburger &&
                        mobileMenu.classList.contains(
                            'open'
                        )
                    ) {

                        mobileMenu.classList.remove(
                            'open'
                        );

                        hamburger.classList.remove(
                            'active'
                        );

                        hamburger.setAttribute(
                            'aria-expanded',
                            'false'
                        );

                        hamburger.setAttribute(
                            'aria-label',
                            'Open navigation menu'
                        );

                    }

                }
            );

        }


        /* -----------------------------------------------
           MOBILE BOTTOM QUOTE BAR
           ----------------------------------------------- */

        if (mobileQuoteBar) {

            function updateMobileQuoteBar() {

                if (window.innerWidth <= 767) {

                    mobileQuoteBar.classList.add(
                        'mobile-sticky-active'
                    );

                } else {

                    mobileQuoteBar.classList.remove(
                        'mobile-sticky-active'
                    );

                }

            }

            updateMobileQuoteBar();

            window.addEventListener(
                'resize',
                updateMobileQuoteBar
            );

        }

    }


    /* =========================================================
       SIDE-SCROLL GALLERY
       ========================================================= */

    function sideScrollGallery(
        selector,
        waitTime = 4000
    ) {

        const gallery =
            document.querySelector(selector);

        if (!gallery) return;

        const track =
            gallery.querySelector(
                '.gallery-track'
            );

        const slides =
            track
                ? track.querySelectorAll('.slide')
                : [];

        const nextButton =
            gallery.querySelector(
                '.gallery-next'
            );

        const prevButton =
            gallery.querySelector(
                '.gallery-prev'
            );

        if (
            !track ||
            slides.length === 0 ||
            !nextButton ||
            !prevButton
        ) {
            return;
        }

        let current = 0;
        let timer;


        function showImage(index) {

            if (index >= slides.length) {

                current = 0;

            } else if (index < 0) {

                current =
                    slides.length - 1;

            } else {

                current = index;

            }

            track.style.transform =
                `translateX(-${current * 100}vw)`;

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


        nextButton.addEventListener(
            'click',
            nextImage
        );

        prevButton.addEventListener(
            'click',
            previousImage
        );


        showImage(0);

        resetTimer();

    }


    /* =========================================================
       LOGO CROSSFADE
       ========================================================= */

    function updateLogoFade() {

        /*
         * The large hero logo fades OUT.
         * The navigation logo fades IN.
         *
         * 0px:
         * Large logo = 100%
         * Nav logo   = 0%
         *
         * 400px:
         * Large logo = 0%
         * Nav logo   = 100%
         */

        const fadeDistance = 400;

        const progress =
            Math.min(
                window.scrollY / fadeDistance,
                1
            );

        const largeLogo =
            document.querySelector('.logo-img');

        const navLogo =
            document.querySelector('.nav-logo');


        if (largeLogo) {

            largeLogo.style.opacity =
                1 - progress;

        }


        if (navLogo) {

            navLogo.style.opacity =
                progress;

        }

    }


    /* =========================================================
       PRODUCT PREVIEW
       ========================================================= */

    function cycleProductPreview(
        selector,
        interval = 3500
    ) {

        const headings =
            document.querySelectorAll(
                `${selector} .quick-product-heading`
            );

        const summary =
            document.querySelector(
                `${selector} .quick-product-summary`
            );

        if (
            headings.length < 2 ||
            !summary
        ) {
            return;
        }

        let activeIndex = 0;
        let timer;


        function showProduct(index) {

            headings[
                activeIndex
            ].classList.remove(
                'is-active'
            );

            activeIndex = index;

            headings[
                activeIndex
            ].classList.add(
                'is-active'
            );

            summary.textContent =
                headings[
                    activeIndex
                ].dataset.summary;

        }


        function resetTimer() {

            clearTimeout(timer);

            timer = setTimeout(() => {

                showProduct(
                    (activeIndex + 1) %
                    headings.length
                );

                resetTimer();

            }, interval);

        }


        headings.forEach(
            (heading, index) => {

                heading.addEventListener(
                    'click',
                    () => {

                        showProduct(index);

                        resetTimer();

                    }
                );

            }
        );


        resetTimer();

    }


    /* =========================================================
       INITIALIZE EVERYTHING
       ========================================================= */

    stickyNav(
        '.Nav',
        'stickyNav'
    );

    sideScrollGallery(
        '.gallery',
        10000
    );

    cycleProductPreview(
        '.product-preview'
    );

    mobileNavigation();

    updateLogoFade();


    /* =========================================================
       SCROLL EVENTS
       ========================================================= */

    window.addEventListener(
        'scroll',
        updateLogoFade,
        { passive: true }
    );


    /* =========================================================
       HIGHLIGHTS
       ========================================================= */

    highlight(
        '#productbtn',
        'highlight'
    );

})();


/* =========================================================
   SCROLL-IN BOX ANIMATION
   ========================================================= */

function scrollReveal(selector) {

    const elements =
        document.querySelectorAll(selector);

    if (!elements.length) return;

    const observer =
        new IntersectionObserver(
            (entries, observer) => {

                entries.forEach((entry) => {

                    if (entry.isIntersecting) {

                        entry.target.classList.add('show');

                        observer.unobserve(
                            entry.target
                        );

                    }

                });

            },
            {
                threshold: 0.15
            }
        );

    elements.forEach((element) => {
        observer.observe(element);
    });
}


scrollReveal('.prod-box');

scrollReveal('.slide');

scrollReveal('.location-card');