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
    var nav = $('#navbar-collapse');
    if (window.innerWidth >= 768 && window.innerWidth < 936) {
        nav.find('li a .text').addClass('hidden');
    } else {
        nav.find('li a .text').removeClass('hidden');
    }

    if (window.innerWidth <= 308) {
        $('.navbar-logo').addClass('hidden');
    } else {
        $('.navbar-logo').removeClass('hidden');
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
