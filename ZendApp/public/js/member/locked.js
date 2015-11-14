/**
 * Document ready function, calls the locked popup launch function
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('#lockedLaunch').trigger('click');
});

/**
 * Function that launches the popup box, using Jquery.confirm.
 *
 * @author Craig Knott
 */
$("#lockedLaunch").click(function () {
    $.confirm({
        title:             'Login to Niceway.to',
        icon:              'fa fa-sign-in',
        content:           loginForm.html(),
        theme:             'black',
        confirmButton:     'Login',
        cancelButton:      'Signup',
        backgroundDismiss: false,
        keyboardEnabled:   true,
        confirm:           function () {
            $('#loginForm').submit()
        },
        cancel:            function () {
            window.location = "/member/signup";
        }
    });
});
