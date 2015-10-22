$(document).ready(function() {
    var navBarHeight = 70;
    var constantTop = navBarHeight + 75;

    $('.workoutContainer').css({
        height: (($(window).height() - constantTop)/2) + 'px'
    });
});