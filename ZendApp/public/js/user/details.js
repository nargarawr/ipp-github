/**
 * Document ready function, calls the email confirmed popup launch function if necessary
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    var ec = $('#emailConfirmed');
    if (ec.val() == 1) {
        ec.trigger('click');
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
