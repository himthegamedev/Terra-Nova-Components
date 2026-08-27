document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       MOBILE HAMBURGER MENU
       ===================================================== */

    const hamburgerButton =
        document.getElementById("hamburger-button");

    const mobileMenu =
        document.getElementById("mobile-menu");

    const mobileMenuClose =
        document.getElementById("mobile-menu-close");

    const mobileMenuLinks =
        document.querySelectorAll(".mobile-menu-link");


    function openMenu() {

        if (!mobileMenu || !hamburgerButton) {
            return;
        }

        mobileMenu.classList.add("open");
        hamburgerButton.classList.add("active");

        hamburgerButton.setAttribute(
            "aria-expanded",
            "true"
        );

        document.body.classList.add("menu-open");
    }


    function closeMenu() {

        if (!mobileMenu || !hamburgerButton) {
            return;
        }

        mobileMenu.classList.remove("open");
        hamburgerButton.classList.remove("active");

        hamburgerButton.setAttribute(
            "aria-expanded",
            "false"
        );

        document.body.classList.remove("menu-open");
    }


    if (hamburgerButton) {

        hamburgerButton.addEventListener(
            "click",
            function () {

                if (mobileMenu.classList.contains("open")) {
                    closeMenu();
                } else {
                    openMenu();
                }

            }
        );

    }


    if (mobileMenuClose) {

        mobileMenuClose.addEventListener(
            "click",
            closeMenu
        );

    }


    mobileMenuLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {
                closeMenu();
            }
        );

    });


    /* Close menu when clicking outside the menu content */

    if (mobileMenu) {

        mobileMenu.addEventListener(
            "click",
            function (event) {

                if (event.target === mobileMenu) {
                    closeMenu();
                }

            }
        );

    }


    /* Close menu with ESC */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {
                closeMenu();
            }

        }
    );


    /* =====================================================
       HERO PRODUCT TABS
       ===================================================== */

    const productButtons =
        document.querySelectorAll(
            ".quick-product-heading"
        );

    const productSummary =
        document.querySelector(
            ".quick-product-summary"
        );


    productButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                productButtons.forEach(
                    function (item) {
                        item.classList.remove(
                            "is-active"
                        );
                    }
                );

                button.classList.add(
                    "is-active"
                );

                if (productSummary) {

                    const summary =
                        button.getAttribute(
                            "data-summary"
                        );

                    if (summary) {
                        productSummary.textContent =
                            summary;
                    }

                }

            }
        );

    });


    /* =====================================================
       SMOOTH SCROLL
       ===================================================== */

    const allAnchorLinks =
        document.querySelectorAll(
            'a[href^="#"]'
        );


    allAnchorLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function (event) {

                const targetId =
                    link.getAttribute("href");

                if (
                    !targetId ||
                    targetId === "#"
                ) {
                    return;
                }

                const target =
                    document.querySelector(
                        targetId
                    );

                if (!target) {
                    return;
                }

                event.preventDefault();

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            }
        );

    });


    /* =====================================================
       PREVENT BODY SCROLL WHEN MENU IS OPEN
       ===================================================== */

    window.addEventListener(
        "resize",
        function () {

            if (
                window.innerWidth > 767 &&
                mobileMenu &&
                mobileMenu.classList.contains("open")
            ) {
                closeMenu();
            }

        }
    );


});