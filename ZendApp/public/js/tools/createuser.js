$(document).ready(function () {
    $('#createAccount').click(function (e) {
        e.preventDefault();
        createAccount();
    });
});

function createAccount() {
    var username = $('#usernameInput').val();
    if (username === "") {
        $('#usernameInputWrapper').addClass('has-error');
        return;
    }
    $.ajax({
        url: "/account/checkuniqueusername/username/" + username
    }).success(function (isUnique) {
        if (isUnique === 'true') {
            $.ajax({
                type: 'POST',
                url:  '/tools/createaccount',
                data: {
                    username: username,
                    email:    $('#emailInput').val(),
                    fname:    $('#fnameInput').val(),
                    lname:    $('#lnameInput').val()
                }
            }).success(function () {
                window.location.href = '/tools/createuser';
            });
        }
    });
    return false;
}
