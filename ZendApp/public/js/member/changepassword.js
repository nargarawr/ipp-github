/**
 * Document ready function, calls the password popup launch function
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('#pwordChange-label').remove();
    $('#hash-label').remove();
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
        title:             'Reset your password',
        icon:              'fa fa-unlock-alt',
        content:           passwordForm.html(),
        theme:             'black',
        confirmButton:     'Change Password',
        cancelButton:      'Login',
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
