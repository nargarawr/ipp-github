$(document).ready(function(){
   $('#loginLaunch').trigger('click');
});

$("#loginLaunch").click(function () {
    var loginForm = $('#hiddenLoginForm').clone();
    loginForm.find('form').attr('id','loginForm');

    $.confirm({
        title: 'Login to Niceway.to',
        icon: 'fa fa-sign-in',
        content: loginForm.html(),
        theme: 'black',
        confirmButton: 'Login',
        cancelButton: 'Signup',
        backgroundDismiss: false,
        keyboardEnabled: true,
        confirm: function() {
            $('#loginForm').submit()
        },
        cancel: function() {
            window.location = "/member/signup";
        }
    });
});
