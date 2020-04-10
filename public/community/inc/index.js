document.addEventListener("DOMContentLoaded", function () {
    // making sure that fixed nav bar is not blocking top content
    let navBarHeight = document.querySelector("#nav-bar").offsetHeight;
    document.querySelector("#home").style.paddingTop = navBarHeight + "px";

    document.querySelectorAll('#nav-links a').forEach(function (link) {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            smoothScroll(e.target.getAttribute("href"), 1000);
        });
    });

    window.onscroll = function () {
        stickyNavBar();

        let inView = {};
        document.querySelectorAll('#nav-links a').forEach(function (link) {
            let aID = link.getAttribute('href');
            inView[aID] = isSectionInView(aID);
        });

        for (let item in inView) {
            if (inView[item]) {
                document.querySelector(`[href="${item}"]`).classList.add('active');
            } else {
                document.querySelector(`[href="${item}"]`).classList.remove('active');
            }
        }
    };


//    slider
    let slideIndex = 1;
    showSlides(slideIndex);

    document.querySelector('.next').addEventListener('click', () => {
        plusSlides(+1);
    });

    document.querySelector('.prev').addEventListener('click', () => {
        plusSlides(+1);
    });

    document.querySelectorAll('.dot').forEach((dot) => {
        dot.addEventListener('click', () => {
            let idx = dot.getAttribute('data-slide');
            currentSlide(idx);
        });
    });

// Next/previous controls
    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

// Thumbnail image controls
    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("slide");
        let dots = document.getElementsByClassName("dot");
        if (n > slides.length) {
            slideIndex = 1
        }
        if (n < 1) {
            slideIndex = slides.length
        }
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
    }

});

const isSectionInView = (element) => {
    element = document.querySelector(element);
    // viewport distance from top
    // viewport includes nav-bar
    const scrollTo = window.scrollY || window.pageYOffset;

    // element
    const boundsTop = element.getBoundingClientRect().top + scrollTo;

    const navBarHeight = document.querySelector("#nav-bar").offsetHeight;

    // top of viewport is located at scroll px
    const viewport = {
        top: scrollTo + navBarHeight + 5, // adding 5 px because div often
        // overlap
        bottom: scrollTo + window.innerHeight
    };

    // top of element specifies the bounding box
    const bounds = {
        top: boundsTop,
        bottom: boundsTop + element.clientHeight
    };

    return ( bounds.bottom >= viewport.top && bounds.bottom <= viewport.bottom )
        || ( bounds.top <= viewport.bottom && bounds.top >= viewport.top )
        || ( bounds.top <= viewport.top && bounds.bottom >= viewport.top );
};

const stickyNavBar = () => {
    const navBar = document.querySelector('#nav-bar');
    let stickyOffset = navBar.offsetTop;
    if (window.pageYOffset >= stickyOffset) {
        navBar.classList.add("sticky");
    } else {
        navBar.classList.remove("sticky");
    }
};

const smoothScroll = (targetId, duration) => {
    let target = document.querySelector(targetId);
    // how far away the id is from view port
    let targetPosition = target.getBoundingClientRect().top;
    let navBarHeight = document.querySelector("#nav-bar").offsetHeight;
    targetPosition = targetPosition - navBarHeight;
    // where the view port is along Y axis of window
    let startPosition = window.pageYOffset;
    let startTime = null;

    const animation = (currentTime) => {
        if (startTime === null) startTime = currentTime;
        let timeElapsed = currentTime - startTime;
        let run = ease(timeElapsed, startPosition, targetPosition, duration);
        window.scrollTo(0, run);
        if (timeElapsed < duration) requestAnimationFrame(animation);
    };

    const ease = (t, b, c, d) => {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t + b;
        t--;
        return -c / 2 * (t * (t - 2) - 1) + b;
    };

    requestAnimationFrame(animation);
};