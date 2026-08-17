 function Highlight(selector, highlightClass){
    const targets = document.getElementById(selector);

            targets.forEach(element => {
                element.addEventListener('mouseenter', () => {
                    element.classList.add(highlightClass);
                });

                element.addEventListener('mouseleave', () => {
                    element.classList.remove(highlightClass);
                });
            });
 }
function skip(whereto){
 const target = document.findElementById(whereto);
 if (target){
    target.scrollIntoView({
        behavior: 'smooth'
        block: 'center'
    });
 }
}
function stickyNav(selector, className, logoId, logoClassName, threshold = 50){
    const navbar = document.getElementById(selector);
    const logo = document.getElementById(logoId);
            if (!navbar) return;

            const handleScroll = () => {
                if (window.scrollY > threshold) {
                    navbar.classList.add(className);
                    logo.classList.add(logoClassName);
                } else {
                    navbar.classList.remove(className);
                    logo.classList.remove(logoClassName);
                }
            };
            handleScroll(); 
            window.addEventListener('scroll', handleScroll, { passive: true });
        }