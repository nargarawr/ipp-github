/**
 * Ran when the document is loaded, sets up listeners
 *
 * @author Craig Knott
 */
$(document).ready(function(){
    $('#postAnnouncement').click(function(e){
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
});