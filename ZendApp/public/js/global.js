var navBar = $('.navbar-right').find('.dropdown-toggle');
var navName = $('#nav-username').val();
var caret = '<span class="caret"></span>';

/**
 * Changed the navigation bar based on the screen size. If the screen is too small, the message 'Logged in as
 * username, will be replaced with just 'Username'
 *
 * @author Craig Knott
 */
$(window).resize(function () {
    if (window.innerWidth < 1066) {
        if ($(navBar).html() != navName + caret) {
            $(navBar).html(navName + caret);
        }
    } else {
        if ($(navBar).html() != 'Logged in as ' + navName + caret) {
            $(navBar).html('Logged in as ' + navName + caret)
        }
    }
});

/**
 * Used to send emails through Ajax
 *
 * @author Craig Knott
 */
$('#email').click(function () {
    $.ajax({
        type: 'POST',
        url:  '/email',
        data: {
            templateName: 'confirmemail',
            name: 'RALPH',
            to: ['cxk01u@gmail.com'],
            subject: 'Please consider confirming your email'
        },
        success: function (response) {
            console.log(response);
        }
    })
});
