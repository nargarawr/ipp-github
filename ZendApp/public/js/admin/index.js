/**
 * Ran when the document is loaded, sets up listeners
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('#postAnnouncement').click(function (e) {
        e.preventDefault();

        var form = $('#ancmtForm');
        var input = form.find('input[name=message]');

        if (input.val() === "") {
            input.addClass('redBorder');
        } else {
            input.removeClass('redBorder');
            form.submit();
        }
    });

    window.setInterval(function () {
        getStats()
    }, 30000);
    getStats();

    window.setInterval(function () {
        getServerStats()
    }, 1000);
    getServerStats();
});

/**
 * Gets usage stats and updates the page with them
 *
 * @author Craig Knott
 */
function getStats() {
    $.ajax({
        type: 'GET',
        url:  '/admin/getstats',
        data: {
            from: $('#start_time').val()
        }
    }).success(function (response) {
        var data = JSON.parse(response);

        for (var prop in data) {
            if (!data.hasOwnProperty(prop)) {
                continue;
            }
            $('#' + prop).text(data[prop]);
        }
    });
}

/**
 * Gets server stats and updates the page with them
 *
 * @author Craig Knott
 */
function getServerStats() {
    $.ajax({
        type: 'GET',
        url:  '/admin/getserverstats'
    }).success(function (response) {
        var data = JSON.parse(response);

        for (var prop in data) {
            if (!data.hasOwnProperty(prop)) {
                continue;
            }
            $('#server_' + prop).text(data[prop]);
        }
    });
}