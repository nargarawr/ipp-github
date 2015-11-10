/**
 * Document ready function, calls the login popup launch function
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('#signup-label').remove();
    $('#signupLaunch').trigger('click');
});

/**
 * Function that launches the signup popup box, using Jquery.confirm.
 *
 * @author Craig Knott
 */
$("#signupLaunch").click(function () {
    var signupForm = $('#hiddenSignupForm').clone();
    signupForm.find('form').attr('id', 'signupForm');

    $.confirm({
        title:             'Sign up to Niceway.to',
        icon:              'fa fa-sign-in',
        content:           signupForm.html(),
        theme:             'black',
        confirmButton:     'Login',
        cancelButton:      'Sign Up',
        backgroundDismiss: false,
        keyboardEnabled:   true,
        confirm:           function () {
            window.location = "/member/login";
        },
        cancel:            function () {
            $('#signupForm').submit()
        }
    });
});
