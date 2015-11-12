/**
 * Document ready function, repositions the profile picture
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    // Centre the profile once, and then again on a timer because sometimes the first one doesn't work
    centreProfilePicture();
    setTimeout(centreProfilePicture, 750);
});

$(window).resize(function(){
    centreProfilePicture();
});

/**
 * To get the profile picture skins stacking on each other, I had to use absolute positioning for some. This means the
 * image will not automatically centre itself, and looks silly. This code will use the size of the screen at any time
 * to dynamically reposition the profile picture so that it centres
 *
 * @author Craig Knott
 */
function centreProfilePicture() {
    var imgs = $('.dp_container img');
    var maxSize = $('#max_size').val();
    var screenSize = window.innerWidth;

    if (screenSize > maxSize || (screenSize > 334 && screenSize < 991)) {
        var dpContainer = $('.dp_container');

        var dpw = dpContainer.width();
        var iw = $(imgs[0]).width();
        var lw = (dpw - iw) / 2;

        imgs.css('left', lw + 'px');
    } else {
        // Reset
        imgs.css('left', '0px');
    }
}