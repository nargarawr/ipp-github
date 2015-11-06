/**
 * Document ready function, calls the login popup launch function
 *
 * @author Craig Knott
 */
$(document).ready(function () {
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
        confirmButton:     'Sign Up',
        cancelButton:      'Login',
        backgroundDismiss: false,
        keyboardEnabled:   true,
        confirm:           function () {
            $('#signupForm').submit()
        },
        cancel:            function () {
            window.location = "/member/login";
        }
    });
});
