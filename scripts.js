/* =========================================================
   TERRA NOVA COMPONENTS
   MOBILE MENU + PRODUCT PREVIEW
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       MOBILE HAMBURGER MENU
       ===================================================== */

    const hamburgerButton =
        document.getElementById("hamburger-button");

    const mobileMenu =
        document.getElementById("mobile-menu");

    const mobileMenuLinks =
        document.querySelectorAll(".mobile-menu-link");


    if (hamburgerButton && mobileMenu) {

        hamburgerButton.addEventListener("click", function () {

            const isOpen =
                mobileMenu.classList.toggle("open");

            hamburgerButton.classList.toggle(
                "active",
                isOpen
            );

            hamburgerButton.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

            hamburgerButton.setAttribute(
                "aria-label",
                isOpen
                    ? "Close navigation menu"
                    : "Open navigation menu"
            );

        });


        /* Close menu when a link is clicked */

        mobileMenuLinks.forEach(function (link) {

            link.addEventListener("click", function () {

                mobileMenu.classList.remove("open");

                hamburgerButton.classList.remove("active");

                hamburgerButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

                hamburgerButton.setAttribute(
                    "aria-label",
                    "Open navigation menu"
                );

            });

        });


        /* Close menu when clicking outside it */

        document.addEventListener("click", function (event) {

            const clickedInsideMenu =
                mobileMenu.contains(event.target);

            const clickedHamburger =
                hamburgerButton.contains(event.target);

            if (
                !clickedInsideMenu &&
                !clickedHamburger &&
                mobileMenu.classList.contains("open")
            ) {

                mobileMenu.classList.remove("open");

                hamburgerButton.classList.remove("active");

                hamburgerButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

                hamburgerButton.setAttribute(
                    "aria-label",
                    "Open navigation menu"
                );

            }

        });

    }


    /* =====================================================
       QUICK PRODUCT PREVIEW
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

        button.addEventListener("click", function () {

            /* Remove active state */

            productButtons.forEach(function (item) {

                item.classList.remove("is-active");

            });


            /* Activate clicked button */

            button.classList.add("is-active");


            /* Change description */

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

        });

    });


    /* =====================================================
       CLOSE MOBILE MENU WHEN RESIZING TO DESKTOP
       ===================================================== */

    window.addEventListener("resize", function () {

        if (window.innerWidth > 767) {

            if (mobileMenu) {
                mobileMenu.classList.remove("open");
            }

            if (hamburgerButton) {

                hamburgerButton.classList.remove(
                    "active"
                );

                hamburgerButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

                hamburgerButton.setAttribute(
                    "aria-label",
                    "Open navigation menu"
                );

            }

        }

    });

});