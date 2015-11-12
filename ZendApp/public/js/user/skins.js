/**
 * Document ready function, updates profile picture based on user selection
 *
 * @author Craig Knott
 */
var ids;
$(document).ready(function () {
    var selects = $('select');

    ids = {};
    selects.each(function () {
        var index = $(this).attr('id').slice(0, -7);
        ids[index] = [];
        $(this).children().each(function () {
            ids[index].push($(this).attr("data-skinid"));
        });

        $(this).ddslick();
    });

    $('#saveChanges').click(function () {
        var icon = $(this).find('i');
        icon.removeClass('fa-check').addClass('fa-refresh fa-spin');

        var postData = {};

        $('.dd-container').each(function () {
            var id = $(this).attr('id').slice(0, -7);
            var selected = $(this).data('ddslick').selectedIndex;

            postData[id] = ids[id][selected];
        });

        postData['userId'] = $('#userId').val();
        $.ajax({
            type: 'POST',
            url:  '/user/updateskins/',
            data: postData
        }).success(function () {
            icon.addClass('fa-check').removeClass('fa-refresh fa-spin');
        });
    });

    $('.dd-option').click(function () {
        var newImgUrl = $(this).find('img').attr('src').replace('_thumb', '');
        var slot = $(this).closest('.dd-container').attr('id').slice(0, -7);
        $("#skin_" + slot).attr('src', newImgUrl);
    });
});
