var navText = '';
$(document).ready(function() {
    navText = $('.navbar-right').find('.dropdown-toggle').html();
});

$(window).resize(function() {
    var navBar = $('.navbar-right').find('.dropdown-toggle');
    if (window.innerWidth < 1066) {
        if (navBar.html() != 'Logged in <span class="caret"></span>') {
            navBar.html('Logged in <span class="caret"></span>');
        }
    } else {
        if (navBar.html() != navText) {
            navBar.html(navText);
        }
    }
});