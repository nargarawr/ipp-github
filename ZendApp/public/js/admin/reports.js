/**
 * Called on page load, loads the report table
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    loadReportTable();
});

/**
 * Loads the content of the report table
 *
 * @author Craig Knott
 */
function loadReportTable() {
    $.ajax({
        url:     '/report/get',
        type:    'post',
        success: function (response) {
            response = JSON.parse(response);
            constructReportTable(response);
        }
    });
}

/**
 * Takes an json array of reports and constructs an html table displaying them
 *
 * @author Craig Knott
 *
 * @param data JSON array of reports
 */
function constructReportTable(data) {
    var table = $('#reportsTable');
    table.empty();

    if (data.length > 0) {
        for (var i = 0; i < data.length; i++) {
            var obj = data[i];

            var content = (obj.type == 'comment'
                ? obj.reported_comment
                : '<a href="' + obj.reported_route + '"> Route ' + obj.reported_item_id + '</a>');

            var datetime = moment(obj.datetime).format('DD/MM/YYYY');

            var tr = $('<tr>');
            tr.html(
                '<td>' + obj.username + '</td>' +
                '<td>' + datetime + '</td>' +
                '<td>' + obj.report_message + '</td>' +
                '<td>' + content + '</td>' +
                '<td>' +
                '   <div class="form-group">' +
                '      <div class="input-group">' +
                '          <select class="form form-control form-control-smaller">' +
                '              <option value="0"> Action... </option>' +
                '              <option value="1"> Mark content as acceptable </option>' +
                '              <option value="2"> Delete content </option>' +
                '          </select>' +
                '          <div class="input-group-addon btn btn-success" data-reporteditemid="' + obj.reported_item_id + '" data-type="' + obj.type + '" data-id="' + obj.id + '">' +
                '              <i class="fa fa-check"></i>' +
                '          </div>' +
                '      </div>' +
                '   </div>' +
                '</td>'
            );
            table.append(tr);
        }
    } else {
        var tr = $('<tr>');
        tr.html('<td colspan="5"> There are no unresolved reports! </td>');
        table.append(tr);
    }

    $('.input-group-addon').each(function () {
        var clicked = $(this);
        $(this).click(function () {
            var select = clicked.closest('.input-group').find('select');
            var selected = select.val();
            if (selected == 0) {
                $.alert({
                    title:           'No action selected!',
                    icon:            'fa fa-times',
                    content:         'You did not select an action to perform',
                    theme:           'black',
                    keyboardEnabled: true
                });
            } else {
                var action = (selected == 1) ? 'mark this content as acceptable?' : 'delete this post?';
                var resolution = (selected == 1) ? 'acceptable' : 'deleted';

                $.confirm({
                    title:           'Are you sure?',
                    icon:            'fa fa-info-circle',
                    content:         'Are you sure you wish to ' + action,
                    theme:           'black',
                    keyboardEnabled: true,
                    confirm:         function () {
                        $.ajax({
                            type: 'POST',
                            url:  '/report/resolve',
                            data: {
                                id:             clicked.attr('data-id'),
                                resolution:     resolution,
                                type:           clicked.attr('data-type'),
                                reportedItemId: clicked.attr('data-reporteditemid')
                            }
                        }).success(function () {
                            clicked.closest('tr').remove();

                            var inputs = $('.input-group-addon');
                            inputs.each(function () {
                                if ($(this).attr("data-type") == clicked.attr('data-type') &&
                                    $(this).attr("data-reporteditemid") == clicked.attr('data-reporteditemid')) {
                                    $(this).closest('tr').remove();
                                }
                            });

                            $('.badge').text(inputs.length);

                            if (inputs.length < 1) {
                                var tr = $('<tr>');
                                tr.html('<td colspan="5"> There are no unresolved reports! </td>');
                                table.append(tr);
                            }
                        });
                    }
                });
            }
        });
    });
}