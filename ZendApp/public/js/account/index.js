$(document).ready(function () {
    $('#updateAccount').bind("click", updateAccountDetails);
    $('#updatePassword').bind("click", updatePassword);
});

function updateAccountDetails () {
    $.ajax({
        type: 'POST',
        url:  '/account/updateaccountsettings',
        data: {
            firstName: $('#in_fname').val(),
            lastName:  $('#in_lname').val(),
            email:     $('#in_email').val()
        }
    }).success(function () {
        location.reload();
    });
    return false;
}

function updatePassword () {
    $.ajax({
        type: 'POST',
        url:  '/account/updatepassword',
        data: {
            currentPass: $('#in_curPass').val(),
            newPass1:    $('#in_newPass1').val(),
            newPass2:    $('#in_newPass2').val()
        }
    }).success(function (response) {
        if (response.length != 0) {
            var parsedResponse = JSON.parse(response);
            handleErrors(parsedResponse.error);
        } else {
            location.reload();
        }
    });
    return false;
}

function handleErrors (error) {
    $('#errorDisplay').removeClass("hidden");
    switch (error) {
        case "wrong_password":
            $('#in_curPassWrapper').addClass("has-error");
            $('#in_newPass1Wrapper').removeClass("has-error");
            $('#in_newPass2Wrapper').removeClass("has-error");
            $('#errorDisplay').text("Current password was incorrect");
            break;
        case "password_mismatch":
            $('#in_curPassWrapper').removeClass("has-error");
            $('#in_newPass1Wrapper').addClass("has-error");
            $('#in_newPass2Wrapper').addClass("has-error");
            $('#errorDisplay').text("Entered passwords did not match");
            break;
        case "blank_fields":
            $('#in_curPassWrapper').addClass("has-error");
            $('#in_newPass1Wrapper').addClass("has-error");
            $('#in_newPass2Wrapper').addClass("has-error");
            $('#errorDisplay').text("One or more fields were left blank");
            break;
    }
}