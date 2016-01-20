/**
 * Document ready function. Gets the geocodes the submitted data and submits the form
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    var currentPage = $('#page_num').val();

    // Pagination buttons
    $('.prevPage').click(function (e) {
        e.preventDefault();
        if (currentPage != 0) {
            submitPaginiationForm('prev');
        }
    });

    $('.nextPage').click(function (e) {
        e.preventDefault();
        if (parseInt($('#page_num').val()) + 1 != $('.iPage').length) {
            submitPaginiationForm('next');
        }
    });

    $('.iPage').click(function (e) {
        e.preventDefault();
        var goTo = parseInt($(this).find('a').text()) - 1;
        submitPaginiationForm(goTo);
    });
});

/**
 * Process the entered user search terms, then submit the search form
 *
 * @author Craig Knott
 */
function submitPaginiationForm(type) {
    // Geocode the start and end points, so we can process them as lat and long values
    $.ajax({
        type: 'GET',
        url:  "https://maps.googleapis.com/maps/api/geocode/json",
        data: {
            address: $("#start_address").val(),
            key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
        }
    }).success(function (response) {
        if (response.status != "OK") {
            $("#formError").val(1);
        } else {
            var geocodedStart = response.results[0].geometry.location;
            $("#start_lat").val(geocodedStart.lat);
            $("#start_lng").val(geocodedStart.lng);
        }

        $.ajax({
            type: 'GET',
            url:  "https://maps.googleapis.com/maps/api/geocode/json",
            data: {
                address: $("#end_address").val(),
                key:     "AIzaSyCwkWD2VSfdZWqbc8GUSOe76SZju3bx460"
            }
        }).success(function (response2) {
            var end = $("#end_address");

            // If user entered something for the end point, but it doesn't exist, throw an error
            if (response2.status != "OK" && end.val() != "") {
                $("#formError").val(2);
            } else {
                if (end.val() != "") {
                    var geocodedEnd = response2.results[0].geometry.location;

                    $("#end_lat").val(geocodedEnd.lat);
                    $("#end_lng").val(geocodedEnd.lng);
                }
            }

            // Change pagination stuff here
            var pn = $('#page_num');
            var cur = pn.val();

            if (type == 'next') {
                pn.val(++cur);
            } else if (type == 'prev') {
                pn.val(--cur);
            } else if (jQuery.isNumeric(type)) {
                pn.val(type);
            }

            $("#pageForm").submit();
        });

    });
}