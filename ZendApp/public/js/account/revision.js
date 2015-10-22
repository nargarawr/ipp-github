$(document).ready(function () {
    $('#updateRevision').bind("click", updateRevisionDetails);
    $('#addExamPeriod').bind("click", addExamPeriod);

    if ($('#start_date').length == 1) {
        $("#start_date").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $("#end_date").datepicker({
            dateFormat: 'dd/mm/yy'
        });
    }

    if ($('.ftuBanner').length == 0) {
        displayExamPeriods();
    }
});

function addExamPeriod () {
    var name = $('#period_name').val();
    var sDate = $('#start_date').val();
    var eDate = $('#end_date').val();

    var countNull = 0;
    $('.addExamForm .form-control').each(function () {
        var val = $(this).val();
        var id = $(this).attr('id');
        if (val === '') {
            $('#' + id + 'Wrapper').addClass('has-error');
            countNull++;
        } else {
            $('#' + id + 'Wrapper').removeClass('has-error');
        }
    });

    if (countNull != 0) {
        return;
    }

    var startDate = new Date(convertToAmericanDate(sDate));
    var endDate = new Date(convertToAmericanDate(eDate));

    if (startDate > endDate) {
        $('#start_dateWrapper').addClass('has-error');
        $('#end_dateWrapper').addClass('has-error');
        return;
    }

    $.ajax({
        type: 'POST',
        url:  '/revision/addexamperiod',
        data: {
            name:       name,
            start_date: convertToAmericanDate(sDate),
            end_date:   convertToAmericanDate(eDate)
        }
    }).success(function () {
        location.reload();
    });
    return false;
}

function displayExamPeriods () {
    $('#periods_tbody').empty();
    $.ajax({
        type: 'GET',
        url:  '/revision/getexamperiods'
    }).success(function (response) {
        var parsedJSON = JSON.parse(response);

        for (var i = 0; i < parsedJSON.length; i++) {
            var e = parsedJSON[i];

            var tr = $('<tr>');
            tr.append($('<td>')
                    .append($('<span>').attr('id', 'pName_' + e.id).text(e.name))
                    .append($('<input class="form-control hidden">').attr('id', 'pNameInput_' + e.id).val(''))
            )
                .append($('<td>')
                    .append($('<span>').attr('id', 'pStart_' + e.id).text(moment(e.start_date).format('DD/MM/YYYY')))
                    .append($('<input class="form-control hidden">').attr('id', 'pStartInput_' + e.id).val(moment(e.start_date).format('DD/MM/YYYY')))
            )
                .append($('<td>')
                    .append($('<span>').attr('id', 'pEnd_' + e.id).text(moment(e.end_date).format('DD/MM/YYYY')))
                    .append($('<input class="form-control hidden">').attr('id', 'pEndInput_' + e.id).val(moment(e.end_date).format('DD/MM/YYYY')))
            )
                .append($('<td>')
                    .append($('<span>')
                        .attr('class', 'cPointer r_margin_15 glyphicon glyphicon-pencil glyphicon-20')
                        .attr('id', 'pEdit_' + e.id)
                        .attr('onClick', 'editExamPeriod(' + e.id + ')')
                )
                    .append($('<span>')
                        .attr('class', 'cPointer glyphicon glyphicon-remove glyphicon-20')
                        .attr('id', 'pDelete_' + e.id)
                        .attr('onClick', 'deleteExamPeriod(' + e.id + ')')
                )
                    .append($('<span>')
                        .attr('class', 'cPointer glyphicon glyphicon-ok glyphicon-20 hidden')
                        .attr('id', 'pSubmitEdit_' + e.id)
                        .attr('onClick', 'submitEditedExamPeriod(' + e.id + ')')
                )
            );

            $('#periods_tbody').append(tr);
        }
    });
}

function submitEditedExamPeriod (id) {
    var start = convertToAmericanDate($("#pStartInput_" + id).val());
    var end = convertToAmericanDate($("#pEndInput_" + id).val())

    if (start < end) {
        $.ajax({
            url:  '/revision/editexamperiod',
            data: {
                id:         id,
                name:       $("#pNameInput_" + id).val(),
                start_date: convertToAmericanDate($("#pStartInput_" + id).val()),
                end_date:   convertToAmericanDate($("#pEndInput_" + id).val())
            }
        }).success(function () {
            $("#pStartInput_" + id).removeClass('redBorder');
            $("#pEndInput_" + id).removeClass('redBorder');
            displayExamPeriods();
        });
    } else {
        $("#pStartInput_" + id).addClass('redBorder');
        $("#pEndInput_" + id).addClass('redBorder');
    }
}

function editExamPeriod (id) {
    $('#pEdit_' + id).addClass('hidden');
    $('#pDelete_' + id).addClass('hidden');
    $('#pSubmitEdit_' + id).removeClass('hidden');

    $('#pName_' + id).addClass('hidden');
    $('#pStart_' + id).addClass('hidden');
    $('#pEnd_' + id).addClass('hidden');

    $('#pNameInput_' + id).removeClass('hidden');
    $('#pStartInput_' + id).removeClass('hidden');
    $('#pEndInput_' + id).removeClass('hidden');

    // Add date listeners
    $('#pNameInput_' + id).val($('#pName_' + id).text());
    $("#pStartInput_" + id).datepicker({
        dateFormat: 'dd/mm/yy'
    });
    $("#pEndInput_" + id).datepicker({
        dateFormat: 'dd/mm/yy'
    });
}

function deleteExamPeriod (id) {
    var r = window.confirm("This will permanently delete this period, are you sure you wish to continue?");
    if (r) {
        $.ajax({
            url:  '/revision/deleteexamperiod',
            data: {
                id: id
            }
        }).success(function () {
            displayExamPeriods();
        });
        return false;
    }
}

function updateRevisionDetails () {
    if ($('#datepicker').datepicker('getDate') == null) {
        $('#dateWrapper').addClass("has-error");
    } else {
        var d = convertFromDateObj($('#datepicker').datepicker('getDate'), 'en_us');
        $.ajax({
            type: 'POST',
            url:  '/account/updaterevisionstartdate',
            data: {
                startDate: d
            }
        }).success(function (response) {
            location.reload();
        });
        return false;
    }
}