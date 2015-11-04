var tm;

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

    tm = new TableManager($('#routeTable'));



});


var TableManager = Class.extend({
    init:    function (table) {
        this.table = table;
        this.rows = this.getRows();

    },
    getRows: function () {
        var rowClasses = this.table.find($('.routeRow'));
        var rows = [];
        for (var i = 0; i < rowClasses.length; i += 2) {
            rows.push(new TableRow(rowClasses[i], rowClasses[i + 1]));
        }
        return rows;
    }
});

var TableRow = Class.extend({
    init:           function (row, pointsRow) {
        this.row = $(row);
        this.pointsRow = $(pointsRow);

        this.caretDown = this.row.find('.fa-chevron-circle-down');
        this.caretUp = this.row.find('.fa-chevron-circle-up');

        this.setupListeners();
    },
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


