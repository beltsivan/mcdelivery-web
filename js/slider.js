let currentSlide = 0;
const slides = document.querySelectorAll('.slide');

function showNextSlide() {
    if (slides.length === 0) return;

    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
}

// Runs every 5 seconds
setInterval(showNextSlide, 5000);


function scrollGrid(direction) {
    const grid = document.getElementById('categoryGrid');

    // We scroll by the width of exactly two items plus their gaps (160 + 20) * 2 = 360
    const scrollAmount = 360;

    if (direction === 1) {
        // Scroll Right
        grid.scrollLeft += scrollAmount;
    } else {
        // Scroll Left
        grid.scrollLeft -= scrollAmount;
    }
}
