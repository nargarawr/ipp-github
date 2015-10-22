var months = [];

$(document).ready(function () {
    $('.month_table').each(function(){
        var mon = new Month(this, (this.id).slice(4));
        months.push(mon);
    });

    $('.addBtn').each(function() {
        $(this).click(function() {
            var r = prompt('Enter an event');
            if (r !== null && r !== '') {
                $.ajax({
                    type: 'post',
                    url: '/livi/addevent/',
                    data: {
                        event: r,
                        day: this.id
                    }
                })
            }
        });
    });
});

var Month = Class.extend ({
    init: function(table, month) {
        this.table = table;
        this.month = month;
        this.tab = $('#mh_' + this.month);

        if ($('#selected_' + this.month).length > 0) {
            $(this.tab).addClass('selected');
        } else {
            $(this.table).addClass('hidden');
        }

        this.setupListeners();
    },
    setupListeners: function() {
        var _self = this;
        this.tab.click(function() {
            months.forEach(function(mon) {
                $(mon.table).addClass('hidden');
                $(mon.tab).removeClass('selected');
            });
            $(_self.table).removeClass('hidden');
            $(_self.tab).addClass('selected');
        });
    }
});
