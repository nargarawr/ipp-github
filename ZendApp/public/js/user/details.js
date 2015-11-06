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