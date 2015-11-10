/**
 * Document ready function, calls the various popup launch functions as necessary
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    var ec = $('#emailConfirmed');
    if (ec.val() == 1) {
        ec.trigger('click');
    }

    var nce = $('#notConfirmedEmail');
    if (nce.val() == 1) {
        nce.trigger('click');
    }

    $('.delBtn').click(function (e) {
        var url = this.href;
        e.preventDefault();
        $.confirm({
            title:           'Delete point?',
            icon:            'fa fa-warning',
            content:         'Are you sure you wish to delete this route? This action is irreversible.',
            theme:           'black',
            confirmButton:   'Delete',
            keyboardEnabled: true,
            confirm:         function () {
                window.location = url;
            }
        });
    });

    // Centre the profile once, and then again on a timer because sometimes the first one doesn't work
    centreProfilePicture();
    setTimeout(centreProfilePicture, 750);
});

$(window).resize(function(){
    centreProfilePicture();
});

/**
 * Function that launches the email confirmation popup box, using Jquery.confirm.
 *
 * @author Craig Knott
 */
$("#emailConfirmed").click(function () {
    $.alert({
        title:           'Email address confirmed!',
        icon:            'fa fa-envelope',
        content:         'Thanks for confirming your email address, you can now access all features of Niceway.to, enjoy!',
        theme:           'black',
        confirmButton:   'Okay',
        keyboardEnabled: true
    });
});

/**
 * Function that launches the popup box telling the user they need to confirm their email, using Jquery.confirm.
 *
 * @author Craig Knott
 */
$("#notConfirmedEmail").click(function () {
    $.alert({
        title:           'Email address is not yet confirmed!',
        icon:            'fa fa-envelope-o',
        content:         'Unfortunately, commenting on routes, rating routes, and creating routes are reserved for users' +
        ' with confirmed email addresses. Please check your email inbox for an email from us to confirm your email address.',
        theme:           'black',
        confirmButton:   'Okay',
        keyboardEnabled: true
    });
});


/**
 * To get the profile picture skins stacking on each other, I had to use absolute positioning for some. This means the
 * image will not automatically centre itself, and looks silly. This code will use the size of the screen at any time
 * to dynamically reposition the profile picture so that it centres
 */
function centreProfilePicture() {
    var imgs = $('.dp_container img');
    var screenSize = window.innerWidth;

    if (screenSize > 1636 || (screenSize > 334 && screenSize < 991)) {
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