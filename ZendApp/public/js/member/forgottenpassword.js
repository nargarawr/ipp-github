/**
 * Document ready function, calls the password popup launch function
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('#pword-label').remove();
    $('#passwordLaunch').trigger('click');
});

/**
 * Function that launches the password popup box, using Jquery.confirm.
 *
 * @author Craig Knott
 */
$("#passwordLaunch").click(function () {
    var passwordForm = $('#hiddenPasswordForm').clone();
    passwordForm.find('form').attr('id', 'passwordForm');

    $.confirm({
        title:             'Reset your email',
        icon:              'fa fa-envelope-o',
        content:           passwordForm.html(),
        theme:             'black',
        confirmButton:     'Send Email',
        cancelButton:      'Cancel',
        backgroundDismiss: false,
        keyboardEnabled:   true,
        confirm:           function () {
            $('#passwordForm').submit()
        },
        cancel:            function () {
            window.location = "/member/login";
        }
    });
});
