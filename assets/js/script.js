/* =====================================
   CALZ CONNECT - JAVASCRIPT ENGINE
===================================== */


/* 1. MOBILE NAVIGATION */

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

if (menuToggle && navLinks) {

    menuToggle.addEventListener("click", function () {

        navLinks.classList.toggle("show");

        const isOpen = navLinks.classList.contains("show");

        menuToggle.textContent = isOpen ? "✕" : "☰";

        menuToggle.setAttribute(
            "aria-expanded",
            isOpen ? "true" : "false"
        );

    });

}


/* 2. CLOSE MENU AFTER CLICKING A LINK */

document.querySelectorAll(".nav-links a").forEach(function(link) {

    link.addEventListener("click", function() {

        if (navLinks) {
            navLinks.classList.remove("show");
        }

        if (menuToggle) {
            menuToggle.textContent = "☰";
            menuToggle.setAttribute("aria-expanded", "false");
        }

    });

});


/* 3. HEADER SHADOW ON SCROLL */

const header = document.querySelector(".header");

window.addEventListener("scroll", function() {

    if (header) {

        if (window.scrollY > 30) {
            header.classList.add("scrolled");
        } else {
            header.classList.remove("scrolled");
        }

    }

});


/* 4. SCROLL REVEAL ANIMATIONS */

const revealElements = document.querySelectorAll(
    ".intro-section, .business-card, .step-card, .section-header, .cta-content"
);

revealElements.forEach(function(element) {
    element.classList.add("reveal");
});

if ("IntersectionObserver" in window) {

    const observer = new IntersectionObserver(function(entries, observer) {

        entries.forEach(function(entry) {

            if (entry.isIntersecting) {

                entry.target.classList.add("active");

                observer.unobserve(entry.target);

            }

        });

    }, {
        threshold: 0.15
    });

    revealElements.forEach(function(element) {
        observer.observe(element);
    });

} else {

    revealElements.forEach(function(element) {
        element.classList.add("active");
    });

}


/* 5. STAGGERED CARD ANIMATIONS */

document.querySelectorAll(".business-card, .step-card")
.forEach(function(card, index) {

    card.style.transitionDelay = (index % 4) * 0.12 + "s";

});


/* 6. DYNAMIC FOOTER YEAR */

const yearElement = document.getElementById("year");

if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
}


/* 7. WHATSAPP CONFIGURATION */

const whatsappNumber = "256742857966";

const whatsappLinks = document.querySelectorAll(".whatsapp-float");

whatsappLinks.forEach(function(button) {

    button.href = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(
        "Hello Calz Connect. I would like to know more about your business."
    )}`;

});


/* 8. BUTTON CLICK ANIMATION */

document.querySelectorAll(".btn").forEach(function(button) {

    button.addEventListener("click", function() {

        button.classList.add("button-clicked");

        setTimeout(function() {

            button.classList.remove("button-clicked");

        }, 250);

    });

});


/* 9. PAGE LOAD ANIMATION */

window.addEventListener("load", function() {

    document.body.classList.add("page-loaded");

});


console.log("Calz Connect website successfully initialized.");
