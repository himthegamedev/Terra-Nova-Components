
    const btn = document.getElementById('my-button');
     btn.addEventListener('mouseenter', () => {
    btn.textContent = 'You are hovering!';
    btn.style.fontWeight = 'bold';
  });
    btn.addEventListener('mouseleave', () => {
    btn.textContent = 'Hover JavaScript Button';
    btn.style.fontWeight = 'normal';
  });

function skip(whereto){
 const target = document.findElementById(whereto);
 if (target){
    target.scrollIntoView({
        behavior: 'smooth'
        block: 'center'
    });
 }
}
function stikcynav(){

}