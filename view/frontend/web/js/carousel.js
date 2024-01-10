require([
    'jquery',
    'slick'
], function ($) {
    $(document).ready(function () {
        $('.carousel-container').slick({
            slidesToShow: 5,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            prevArrow: '<button type="button" class="slick-prev">Previous</button>',
            nextArrow: '<button type="button" class="slick-next">Next</button>',
        });
    });
});
