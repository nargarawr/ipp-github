$(document).ready(function(){
    $('#signupLaunch').trigger('click');
});

$("#signupLaunch").click(function () {
    var loginForm = $('#hiddenSignupForm').clone();
    loginForm.down().attr('id','signupForm');

    $.alert({
        title: 'Sign up to Niceway.to',
        icon: 'fa fa-sign-in',
        content: loginForm.html(),
        theme: 'black',
        confirmButton: 'Sign Up',
        backgroundDismiss: false,
        confirm: function() {
            $('#signupForm').submit()
        }
    });
});
