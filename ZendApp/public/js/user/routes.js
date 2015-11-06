/**
 * On ready function wish assigns listeners to the delete button, as well as instantiating the table manager class.
 *
 * @author Craig Knott
 */
$(document).ready(function () {
    $('.delBtn').click(function (e) {
        var url = this.href;
        e.preventDefault();
        $.confirm({
            title:           'Delete point?',
            icon:            'fa fa-warning',
            content:         'Are you sure you wish to delete this route? This action is irreversible.',
            theme:           'black',
            confirmButton:   'Delete',
            keyboardEnabled: true,
            confirm:         function () {
                window.location = url;
            }
        });
    });

    var tm = new TableManager($('#routeTable'));
});

/**
 * Class TableManager
 *
 * Manages the the rows of the table of results present on the user/routes page
 *
 * @author Craig Knott
 *
 */
var TableManager = Class.extend({
    /**
     * Initialises the class, and assigns values to the member variables
     *
     * @author Craig Knott
     *
     * @param  Jquery_Object table The Jquery object representing the table
     */
    init:    function (table) {
        this.table = table;
        this.rows = this.getRows();
    },
    /**
     * Loops through all the rows in the given table, creates a new TableRow object for each of these, and passes
     * this array back to be used
     *
     * @author Craig Knott
     *
     * @returns {Array} of TableRow objects representing each row of this.table
     */
    getRows: function () {
        var rowClasses = this.table.find($('.routeRow'));
        var rows = [];
        for (var i = 0; i < rowClasses.length; i += 2) {
            rows.push(new TableRow(rowClasses[i], rowClasses[i + 1]));
        }
        return rows;
    }
});

/**
 * Class TableRow
 *
 * Represents each individual row of the table and allows actions to be applied to them
 *
 * @author Craig Knott
 *
 */
var TableRow = Class.extend({
    /**
     * Initialises the object and assigns the values of all member variables, as well as calling the listener setup
     * function
     *
     * @author Craig Knott
     *
     * @param Jquery_Object row The jquery object representing this specific row
     * @param Jquery_Object pointsRow The jquery object representing the row accompanying this row
     */
    init:           function (row, pointsRow) {
        this.row = $(row);
        this.pointsRow = $(pointsRow);

        this.caretDown = this.row.find('.fa-chevron-circle-down');
        this.caretUp = this.row.find('.fa-chevron-circle-up');

        this.setupListeners();
    },
    /**
     * Assigns listeners to each of the interactive elements of the row
     *
     * @author Craig Knott
     */
    setupListeners: function () {
        var _self = this;

        this.caretUp.click(function () {
            _self.pointsRow.addClass("hidden");
            _self.caretUp.addClass("hidden");
            _self.caretDown.removeClass("hidden");
            _self.pointsRow.find("td").removeClass("noBorder");
            _self.row.find("td").removeClass("noBottomPad");
        });

        this.caretDown.click(function () {
            _self.pointsRow.removeClass("hidden");
            _self.caretDown.addClass("hidden");
            _self.caretUp.removeClass("hidden");
            _self.pointsRow.find("td").addClass("noBorder");
            _self.row.find("td").addClass("noBottomPad");
        });
    }

});
