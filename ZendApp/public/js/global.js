var navBar = $('.navbar-right').find('.dropdown-toggle');
var navName = $('#nav-username').val();
var caret = '<span class="caret"></span>';

$(window).resize(function () {
    if (window.innerWidth < 1066) {
        if ($(navBar).html() != navName + caret) {
            $(navBar).html(navName + caret);
        }
    } else {
        if ($(navBar).html() != 'Logged in as ' + navName + caret) {
            $(navBar).html('Logged in as ' + navName + caret)
        }
    }
});