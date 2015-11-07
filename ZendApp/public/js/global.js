$(document).ready(function () {
    resize();
});

$(window).resize(function () {
    resize();
});

/**
 * Used to resize the navigation bar for between 768 and 811, which starts the overlay
 *
 * @author Craig Knott
 */
function resize() {
    if (window.innerWidth >= 768 && window.innerWidth < 811) {
        $('#navbar-collapse').find('li').addClass('smallerPadding');
    } else {
        $('#navbar-collapse').find('li').removeClass('smallerPadding');
    }
}


/**
 * Used to send emails through Ajax
 *
 * @author Craig Knott
 */
$('#email').click(function () {
    $.ajax({
        type:    'POST',
        url:     '/email',
        data:    {
            templateName: 'confirmemail',
            to:           ['psykc@nottingham.ac.uk'],
            subject:      'Please confirm your email address'
        },
        success: function (response) {
            console.log(response);
        }
    })
});
