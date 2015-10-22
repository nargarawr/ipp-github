$(document).ready(function () {
    $('#tofromSelector').change(function () {
        updateToFromLabel();
    });

    new tableManager();

    updateToFromLabel();
});

var tableManager = Class.extend({
    init: function() {
        var rows = [];

        var lastRow = null;
        $('#loanTableTbody').children().each(function(i, row){
            if (i % 2 == 0) {
                lastRow = row;
            } else if (i % 2 == 1) {
                rows.push(new tableRow(lastRow, row, this));
            }
        });

        this.rows = rows;
    }
});

var tableRow = Class.extend({
    init: function(row, hiddenRow, manager) {
        this.row = $(row);
        this.hiddenRow = $(hiddenRow);
        this.manager = manager;

        this.caret = this.row.find('.fa-caret-down');
        this.step_id = this.row.find('.step_id').val();

        this.setupListeners();
    },
    setupListeners: function() {
        var _self = this;
        this.caret.click(function(){
            _self.toggleActions();
        });
    },
    toggleActions: function() {
        if (this.hiddenRow.hasClass('hidden')) {
            this.hiddenRow.removeClass('hidden');
        } else {
            this.hiddenRow.addClass('hidden');
        }

    }
});

function updateToFromLabel () {
    var label = $('#tofromSelector option:selected').val();
    if (label == 'owed') {
        $('#tofromLabel').text('To');
    } else if (label == 'owe') {
        $('#tofromLabel').text('From');
    }
}