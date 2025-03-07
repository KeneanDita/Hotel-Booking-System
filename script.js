document.addEventListener("DOMContentLoaded", function () {
    let slides = [
        "url('bg/1.png')",
        "url('bg/2.png')",
        "url('bg/3.png')",
        "url('bg/4.png')",
        "url('bg/5.png')"
    ];
    let currentSlide = 0;
    let introSection = document.querySelector(".intro");

    function showSlide(index) {
        introSection.style.backgroundImage = slides[index];
        introSection.style.backgroundSize = "cover";
        introSection.style.backgroundPosition = "center";
        introSection.style.transition = "background-image 1s ease-in-out";
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    setInterval(nextSlide, 5000);
    showSlide(currentSlide);

    // Toggle Menu for mobile
    function toggleMenu() {
        const toggle = document.querySelector('.toggle');
        const headerList = document.querySelector('.header-list');
        
        toggle.classList.toggle('active');
        headerList.classList.toggle('active');
    }
    
});
