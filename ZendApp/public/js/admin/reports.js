/**
 * Called on page load, creates the table manager object
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    var tableManager = new TableManager();
});

/**
 * Class TableSortingManager
 *
 * Used to manage the sorting of the reports table, by clicking on the carets in the table header
 *
 * @author Craig Knott
 */
var TableSortingManager = Class.extend({
    /**
     * Initialise the class and assigns member variables
     *
     * @author Craig Knott
     */
    init:                function (tm) {
        this.reportsTableHeader = $('#reportsTableHeader');
        this.sortByArrows = this.reportsTableHeader.find('.fa');
        this.tableManager = tm;

        this.setupListeners();
    },
    /**
     * Assigns click listeners to the carets and sorts the table after a click
     *
     * @author Craig Knott
     */
    setupListeners:      function () {
        var _self = this;

        this.sortByArrows.click(function () {
            var button = $(this);
            var direction = "DESC";
            var sortBy = button.closest('th').attr('data-sortby');

            if (button.hasClass('fa-caret-up')) {
                direction = "ASC";
            }

            _self.updateSelectedCaret(button);
            _self.sortTable(sortBy, direction);
        });
    },
    /**
     * Redraws the table, sorting by sortBy, in direction of direction
     *
     * @author Craig Knott
     *
     * @param sortBy
     * @param direction
     */
    sortTable:           function (sortBy, direction) {
        this.tableManager.getTableData(this.tableManager.drawReportTable, sortBy, direction)
    },
    /**
     * Updates the selected caret with the selected class, and removes this from all other carets
     *
     * @author Craig Knott
     *
     * @param caret The caret that was selected
     */
    updateSelectedCaret: function (caret) {
        this.sortByArrows.each(function () {
            $(this).removeClass('selectedCaret');
        });

        caret.addClass('selectedCaret');
    }
});

/**
 * Class TableManager
 *
 * Manages the reports table, including getting data and displaying it
 *
 * @author Craig Knott
 *
 */
var TableManager = Class.extend({
    /**
     * Initialises the class and assigns members variables, as well as creating other manager classes
     *
     * @author Craig Knott
     */
    init:             function () {
        this.tableSortingManager = new TableSortingManager(this);
        this.inputs = [];

        this.getTableData(this.drawReportTable);
    },
    /**
     * Adds click listeners to the resolution submission buttons
     *
     * @author Craig Knott
     */
    setupListeners:   function () {
        var _self = this;

        this.inputs.unbind('click');
        this.inputs.click(function () {
            var select = $(this).closest('.input-group').find('select');
            var selected = select.val();

            // User selected no option
            if (selected == 0) {
                _self.noOptionSelected();
            } else {
                var action = (selected == 1) ? 'mark this content as acceptable?' : 'delete this post?';
                var resolution = (selected == 1) ? 'acceptable' : 'deleted';
                _self.resolveReport($(this), action, resolution)
            }
        });
    },
    /**
     * Applies a resolution to the selected report.
     *
     * @author Craig Knott
     *
     * @param clicked The button clicked
     * @param action The action to appear in the popup confirmation
     * @param resolution Resolution to be sent to the database
     */
    resolveReport:    function (clicked, action, resolution) {
        var _self = this;
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

                    _self.inputs = $('.input-group-addon');
                    _self.inputs.each(function () {
                        if ($(this).attr("data-type") == clicked.attr('data-type') &&
                            $(this).attr("data-reporteditemid") == clicked.attr('data-reporteditemid')) {
                            $(this).closest('tr').remove();
                        }
                    });

                    $('.badge').text(_self.inputs.length);

                    if (_self.inputs.length < 1) {
                        $('#reportsTable').append(_self.createTableRow(null, true));
                    }
                });
            }
        });
    },
    /**
     * Displays a popup informing the user they have not selected any options for resolutions
     *
     * @author Craig Knott
     */
    noOptionSelected: function () {
        $.alert({
            title:           'No action selected!',
            icon:            'fa fa-times',
            content:         'You did not select an action to perform',
            theme:           'black',
            keyboardEnabled: true
        });
    },
    /**
     * Gets all the reports from the database and passes this data to the provided callback function
     *
     * @author Craig Knott
     *
     * @param callback  The function to pass the results of the Ajax call to
     * @param sortBy    The field to sort on
     * @param direction The direction to sort by (ASC, DESC or NULL)
     */
    getTableData:     function (callback, sortBy, direction) {
        var _self = this;

        $.ajax({
            url:     '/report/get',
            type:    'post',
            data:    {
                sortBy:    sortBy,
                direction: direction
            },
            success: function (response) {
                response = JSON.parse(response);
                callback(response, _self);
            }
        });
    },
    /**
     * Draws the report table
     *
     * @author Craig Knott
     *
     * @param data The reports to draw
     * @param _self This
     */
    drawReportTable:  function (data, _self) {
        var table = $('#reportsTable');
        table.empty();

        if (data.length > 0) {
            for (var i = 0; i < data.length; i++) {
                table.append(_self.createTableRow(data[i]));
            }
        } else {
            table.append(_self.createTableRow(null, true));
        }

        _self.inputs = $('.input-group-addon');
        _self.setupListeners();
    },
    /**
     * Generates the HTML for a row in the table
     *
     * @param obj A single results from the response object
     * @param empty Whether or not this row should be the 'empty' row
     *
     * @returns {string} HTML string for a row in the table
     */
    createTableRow:   function (obj, empty) {
        var tr = $('<tr>');

        if (empty) {
            tr.html('<td colspan="5"> There are no unresolved reports! </td>');
            return tr;
        }

        var content = (obj.type == 'comment'
            ? obj.reported_content
            : '<a href="' + obj.reported_content + '"> Route ' + obj.reported_item_id + '</a>');

        datetime = moment(obj.datetime).format('DD/MM/YYYY');

        // [0] Gets the HTML of the JQuery objects created
        var usernameCol = $('<td>').text(obj.username)[0];
        var datetimeCol = $('<td>').text(datetime)[0];
        var reportMsgCol = $('<td>').text(obj.report_message)[0];
        var contentCol = $('<td>').html(content)[0];
        var actionsCol = $('<td>').append(
            $('<div>').addClass('form-group').append(
                $('<div>').addClass('input-group').append([
                    $('<select>').addClass('form form-control form-control-smaller').append([
                        $('<option>').attr('value', 0).text('Action...'),
                        $('<option>').attr('value', 1).text('Mark content as acceptable'),
                        $('<option>').attr('value', 2).text('Delete content')
                    ]),
                    $('<div>').addClass('input-group-addon btn btn-success')
                        .attr('data-reporteditemid', obj.reported_item_id)
                        .attr('data-type', obj.type)
                        .attr('data-id', obj.id)
                        .append($('<i>').addClass('fa fa-check'))
                ])
            )
        )[0];

        tr.html([
            usernameCol,
            datetimeCol,
            reportMsgCol,
            contentCol,
            actionsCol
        ]);

        return tr;
    }
});