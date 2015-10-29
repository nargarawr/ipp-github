$(document).ready(function(){
   $('#loginLaunch').trigger('click');
});

$("#loginLaunch").click(function () {
    var loginForm = $('#hiddenLoginForm').clone();
    loginForm.down().attr('id','loginForm');

    $.alert({
        title: 'Login to Niceway.to',
        icon: 'fa fa-sign-in',
        content: loginForm.html(),
        theme: 'black',
        confirmButton: 'Login',
        backgroundDismiss: false,
        confirm: function() {
            $('#loginForm').submit()
        }
    });
});
