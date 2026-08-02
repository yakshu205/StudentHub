const slides = document.querySelectorAll(".slide");

let index = 0;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove("active"));
    slides[index].classList.add("active");
}

setInterval(() => {
    index = (index + 1) % slides.length;
    showSlide(index);
}, 3000);