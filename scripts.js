 function highlight(selector, highlightClass){
    const targets = document.querySelectorAll(selector);

            targets.forEach(element => {
                element.addEventListener('mouseenter', () => {
                    element.classList.add(highlightClass);
                });

                element.addEventListener('mouseleave', () => {
                    element.classList.remove(highlightClass);
                });
            });
 }
function skip(whereto, offset=100){
   const target = document.getElementById(whereto);

    if (!target) return;

    const position = target.getBoundingClientRect().top + window.scrollY;

    window.scrollTo({
        top: position - offset,
        behavior: 'smooth'
    });
}
function stickyNav(selector, className, threshold = 100){
    const navbar = document.querySelector(selector);
            if (!navbar) return;

            const handleScroll = () => {
                if (window.scrollY > threshold) {
                    navbar.classList.add(className);
                } else {
                    navbar.classList.remove(className);
                }
            };
            handleScroll(); 
            window.addEventListener('scroll', handleScroll, { passive: true });
        }
function sideScrollGallery(selector, waitTime = 4000) {

    const gallery = document.querySelector(selector);

    if (!gallery) return;

    const track = gallery.querySelector('.gallery-track');
    const slides = track.querySelectorAll('.slide');
    const nextButton = gallery.querySelector('.gallery-next');
    const prevButton = gallery.querySelector('.gallery-prev');

    let current = 0;
    let timer;

    function showImage(index) {

        if (index >= slides.length) {
            current = 0;
        }
        else if (index < 0) {
            current = slides.length - 1;
        }
        else {
            current = index;
        }

        track.style.transform =
            `translateX(-${current * 100}vw)`;
    }

    function nextImage() {

        showImage(current + 1);

        resetTimer();
    }

    function previousImage() {

        showImage(current - 1);

        resetTimer();
    }

    function resetTimer() {

        clearTimeout(timer);

        timer = setTimeout(() => {
            nextImage();
        }, waitTime);
    }

    nextButton.addEventListener('click', nextImage);

    prevButton.addEventListener('click', previousImage);

    showImage(0);

    resetTimer();
}
sideScrollGallery('.gallery', 10000);

const largeLogo = document.querySelector('.logo-img');
const navLogo = document.querySelector('.nav-logo');

function updateLogoFade() {
    const fadeDistance = 400;
    const progress = Math.min(window.scrollY / fadeDistance, 1);

    if (largeLogo) {
        largeLogo.style.opacity = 1 - progress;
    }

    if (navLogo) {
        navLogo.style.opacity = progress;
    }
}

updateLogoFade();
window.addEventListener('scroll', updateLogoFade, { passive: true });

highlight('#contacts', 'highlight');
highlight('#manubtn', 'highlight');
highlight('#productbtn', 'highlight');