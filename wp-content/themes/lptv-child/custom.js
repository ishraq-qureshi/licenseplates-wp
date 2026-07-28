window.MRCplaythevideo = function () {

    var wrapper = document.getElementById("MRCembedwrapper");
    var iframe = document.getElementById("MRCyoutubeiframe");
    var container = document.getElementById("MRCiframecode");

    if (!iframe) return;

    wrapper.style.display = "none";
    container.style.display = "block";

    iframe.src = "https://www.youtube.com/embed/cZkFy2E9Nrg?autoplay=1&rel=0";
};


function MRClocateplaybutton() {
    var objYTImage = document.getElementById("MRCstaticYTimage");
    var objPlayButton = document.getElementById("MRCplaybutton");

    if (!objYTImage || !objPlayButton) return;

    var nImageLeft = (objYTImage.width / 2) - 50;
    var nImageTop = (objYTImage.height / 2) - 40;

    objPlayButton.style.left = nImageLeft + "px";
    objPlayButton.style.top = nImageTop + "px";
}

setTimeout(MRClocateplaybutton, 200);

document.addEventListener('DOMContentLoaded', function () {

    // Fix Owl Carousel accessibility
    function fixOwlAccessibility(root = document) {

        const buttons = root.querySelectorAll(
            '.trustedWrap .owl-nav button, .reviewWrap .owl-nav button'
        );

        buttons.forEach(btn => {

            btn.removeAttribute('role');

            const isPrev = btn.classList.contains('owl-prev');
            const label = isPrev ? 'Previous slide' : 'Next slide';

            if (!btn.getAttribute('aria-label')) {
                btn.setAttribute('aria-label', label);
            }

            // avoid duplicate sr-only spans
            if (!btn.querySelector('.sr-only')) {
                const span = document.createElement('span');
                span.className = 'sr-only';
                span.textContent = label;

                span.style.position = 'absolute';
                span.style.width = '1px';
                span.style.height = '1px';
                span.style.margin = '-1px';
                span.style.overflow = 'hidden';
                span.style.clip = 'rect(0,0,0,0)';
                span.style.border = '0';

                btn.appendChild(span);
            }
        });
    }

    // initial run
    fixOwlAccessibility();

    // handle Owl dynamic DOM updates (IMPORTANT FIX)
    const observer = new MutationObserver(() => {
        fixOwlAccessibility();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });


    // Fix mobile menu toggle accessibility
    document.querySelectorAll('.header .deskMenu.onlymobile .navbar-toggler').forEach(function (btn) {
        btn.setAttribute('aria-label', 'Toggle Navigation Menu');
    });


});

