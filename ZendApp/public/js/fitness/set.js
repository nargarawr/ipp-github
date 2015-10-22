$(document).ready(function () {
    $('#setAddButton').click(function () {
        addTableRow('#setsTbody', getSetValues());
        $.ajax({
            url:     '/fitness/addset',
            type:    'POST',
            data:    {
                reps:       $('#repsVal').text().trim(),
                weight:     $('#weightVal').text().trim(),
                workoutId:  $('#workoutId').val(),
                exerciseId: $('#exerciseId').val()
            },
            success: function () {
            }
        })
    });

    $('.addReps').each(function(){
        var childElement = $(this).children('span.val')[0];
        var myVal = $(childElement).text().trim();
        $(this).click(function() {
            var initialVal = parseFloat($('#repsVal').text().trim());
            $('#repsVal').text(initialVal + parseFloat(myVal));
        })
    });

    $('.minusReps').each(function(){
        var childElement = $(this).children('span.val')[0];
        var myVal = $(childElement).text().trim();
        $(this).click(function() {
            var initialVal = parseFloat($('#repsVal').text().trim());
            $('#repsVal').text(initialVal - parseFloat(myVal));
            if (parseFloat($('#repsVal').text().trim()) <= 0) {
                $('#repsVal').text('0');
            }
        })
    });

    $('.addWeight').each(function(){
        var childElement = $(this).children('span.val')[0];
        var myVal = $(childElement).text().trim();
        $(this).click(function() {
            var initialVal = parseFloat($('#weightVal').text().trim());
            $('#weightVal').text(initialVal + parseFloat(myVal));
        })
    });

    $('.minusWeight').each(function(){
        var childElement = $(this).children('span.val')[0];
        var myVal = $(childElement).text().trim();
        $(this).click(function() {
            var initialVal = parseFloat($('#weightVal').text().trim());
            $('#weightVal').text(initialVal - parseFloat(myVal));
            if (parseFloat($('#weightVal').text().trim()) <= 0) {
                $('#weightVal').text('0');
            }
        })
    });
});

function getSetValues() {
    var reps = $('#repsVal').text().trim();
    var weight = $('#weightVal').text().trim();
    return reps + ' x ' + weight + 'kg';
}

function addTableRow(tableBodyId, rowText) {
    var table = $(tableBodyId);
    var tableRowNumber = $(tableBodyId).children('tr').length;
    if (tableRowNumber == 0) {
        var newRow = $('<tr>').addClass('text-center');
        newRow.append($('<td>').text(rowText));
        newRow.append($('<td>'));
        table.append(newRow);
    }

    table.children('tr').each(function (i, row) {
        // Check for the first blank row
        var firstColumn = $(row).children('td')[0];
        if ($(firstColumn).text() === "") {
            // Found an empty row, add here
            $(firstColumn).text(rowText);
            return false;
        }

        if ((i + 1) == (tableRowNumber)) {
            var newRow = $('<tr>').addClass('text-center');
            newRow.append($('<td>').text(rowText));
            newRow.append($('<td>'));
            table.append(newRow);
        }
    });
}