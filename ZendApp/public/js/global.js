$(document).ready(function () {
    resize();
});

$(window).resize(function () {
    resize();
});

/**
 * Used to resize the navigation bar for between 768 and 811, which starts the overlay
 *
 * @author Craig Knott
 */
function resize() {
    var nav = $('#navbar-collapse');
    if (window.innerWidth >= 768 && window.innerWidth < 936) {
        nav.find('li a .text').addClass('hidden');
    } else {
        nav.find('li a .text').removeClass('hidden');
    }

    if (window.innerWidth <= 308) {
        $('.navbar-logo').addClass('hidden');
    } else {
        $('.navbar-logo').removeClass('hidden');
    }
}