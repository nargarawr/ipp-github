$(document).ready(function(){
    $('#signupLaunch').trigger('click');
});

$("#signupLaunch").click(function () {
    var signupForm = $('#hiddenSignupForm').clone();
    signupForm.find('form').attr('id','signupForm');

    $.confirm({
        title: 'Sign up to Niceway.to',
        icon: 'fa fa-sign-in',
        content: signupForm.html(),
        theme: 'black',
        confirmButton: 'Sign Up',
        cancelButton: 'Login',
        backgroundDismiss: false,
        keyboardEnabled: true,
        confirm: function() {
            $('#signupForm').submit()
        },
        cancel: function() {
            window.location = "/member/login";
        }
    });
});
