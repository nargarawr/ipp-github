$(document).ready(function () {
    var dataManager = new DataManager();
    dataManager.getMapStats("All");
    dataManager.getMapTickerStats("All");
});

var DataManager = Class.extend({
    init: function () {
        this.setupListeners();
    },
    setupListeners: function () {
        var _self = this;
        $('#teamSizeFilter').on('change', function () {
            _self.getMapStats($('#teamSizeFilter').val());
            _self.getMapTickerStats($('#teamSizeFilter').val());
        });
    },
    getMapStats: function(teamSize) {
        var url = "/csgo/getmapstats/";
        if (teamSize !== "All") {
            url += "team/" + teamSize;
        }

        cacheAndCallAjax(
            url,
            this.drawMapStatsNew,
            null
        );
    },
    drawMapStatsNew: function(response) {
        var data = JSON.parse(response);

        // Crazy stuff to convert these from objects to arrays, caused by having string indexes
        var maps = $.map(data, function(value, index) {
            value.stats = $.map(value.stats, function(v, index) {
                return [v];
            });
            return [value];
        });

        var mapsContainer = $('#mapsContainer');
        mapsContainer.empty();
        mapsContainer.append($('<div>').addClass('row'))
        $('#summary').empty();

        for (var i = 0; i < maps.length; i++) {
            var map = maps[i];
            console.log(map)
            var mapDOM = $('<div>').addClass('mapContainer col-md-4')
            
            var mapName = $('<div>').addClass('mapName col-md-12').html("<b>" + map.name + "</b>");
            mapDOM.append(mapName);

            var mapOverallStats = $('<div>').addClass('row');
            var wins = $('<div>').addClass('col-md-4 mapOverallStats').html(map.wins + "<p class='smlP'>Win</p>");
            var ties = $('<div>').addClass('col-md-4 mapOverallStats').html(map.ties + "<p class='smlP'>Tie</p>");
            var lose = $('<div>').addClass('col-md-4 mapOverallStats').html(map.losses + "<p class='smlP'>Lose</p>");
            mapOverallStats.append(wins, ties, lose);
            mapDOM.append(mapOverallStats);

            var twoSides = $('<div>').addClass('row');
            var tSide = $('<div>').addClass('col-md-6');
            var ctSide = $('<div>').addClass('col-md-6');

            var pcntWinBar = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "red");
            var pcntLoseBar = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "blue");
            var pcntWinBarPistol = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "red");
            var pcntLoseBarPistol = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "blue");            
            tSide.append(pcntWinBar);
            tSide.append(pcntLoseBar);
            tSide.append(pcntWinBarPistol, pcntLoseBarPistol);

            var pcntWinBar = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "red");
            var pcntLoseBar = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "blue");
            var pcntWinBarPistol = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "red");
            var pcntLoseBarPistol = $('<div>').css("height", "10px").css("width", "50%").css("float", "left").css("background-color", "blue");
            ctSide.append(pcntWinBar);
            ctSide.append(pcntLoseBar);
            ctSide.append(pcntWinBarPistol, pcntLoseBarPistol);

            twoSides.append(tSide, ctSide);
            mapDOM.append(twoSides);


            mapsContainer.append(mapDOM);
/*


            var map = maps[i];
            var sides = $('<div>').addClass('map col-md-4');

            var mapTitle = map.name + " - Win Rate: " + map.win_percent + "% (" + map.win_and_tie_percent + "% with ties)"
            var name = $('<div>').addClass("name").html("<b>" + mapTitle + "</b>");
            $("#summary").append(mapTitle + '\n');
            sides.append(name);

            var statsTableContainer = $('<div>').addClass('statsTableContainer');
            var statsTable = $('<table>').addClass('table');
            
            var thead = $('<thead>');
            thead.append($('<th>').text(" "))
            thead.append($('<th>').text("Win"))
            thead.append($('<th>').text("Tie"))
            thead.append($('<th>').text("Loss"))
            statsTable.append(thead);

            var tbody = $('<tbody>');
            var tr = $('<tr>');
            tr.append($('<td>').text(" "));
            tr.append($('<td>').text(map.wins));
            tr.append($('<td>').text(map.ties));
            tr.append($('<td>').text(map.losses));
            tbody.append(tr);

            for (var j = 0; j < map.stats.length; j++) {
                var side = map.stats[j];
                
                var sideRow = $('<tr>');
                sideRow.append($('<td>').text("Rounds (" + side.side + ")"));
                sideRow.append($('<td>').text(side.round_wins));
                sideRow.append($('<td>').text("-"));
                sideRow.append($('<td>').text(side.round_losses));

                var pistolSideRow = $('<tr>');
                pistolSideRow.append($('<td>').text("Pistol Rounds (" + side.side + ")"));
                pistolSideRow.append($('<td>').text(side.pistol_round_wins));
                pistolSideRow.append($('<td>').text("-"));
                pistolSideRow.append($('<td>').text(side.pistol_round_losses));

                tbody.append(sideRow);
                tbody.append(pistolSideRow);
            }


            statsTable.append(tbody);
            statsTableContainer.append(statsTable);
            sides.append(statsTableContainer);
            mapsContainer.append(sides);*/
        }
    },
    drawMapStats: function(response) {
        var data = JSON.parse(response);

        // Crazy stuff to convert these from objects to arrays, caused by having string indexes
        var maps = $.map(data, function(value, index) {
            value.stats = $.map(value.stats, function(v, index) {
                return [v];
            });
            return [value];
        });

        var mapsContainer = $('#mapsContainer');
        mapsContainer.empty();
        $('#summary').empty();

        for (var i = 0; i < maps.length; i++) {
            var map = maps[i];
            var sides = $('<div>').addClass('map col-md-4');

            var mapTitle = map.name + " - Win Rate: " + map.win_percent + "% (" + map.win_and_tie_percent + "% with ties)"
            var name = $('<div>').addClass("name").html("<b>" + mapTitle + "</b>");
            $("#summary").append(mapTitle + '\n');
            sides.append(name);

            var statsTableContainer = $('<div>').addClass('statsTableContainer');
            var statsTable = $('<table>').addClass('table');
            
            var thead = $('<thead>');
            thead.append($('<th>').text(" "))
            thead.append($('<th>').text("Win"))
            thead.append($('<th>').text("Tie"))
            thead.append($('<th>').text("Loss"))
            statsTable.append(thead);

            var tbody = $('<tbody>');
            var tr = $('<tr>');
            tr.append($('<td>').text(" "));
            tr.append($('<td>').text(map.wins));
            tr.append($('<td>').text(map.ties));
            tr.append($('<td>').text(map.losses));
            tbody.append(tr);

            for (var j = 0; j < map.stats.length; j++) {
                var side = map.stats[j];
                
                var sideRow = $('<tr>');
                sideRow.append($('<td>').text("Rounds (" + side.side + ")"));
                sideRow.append($('<td>').text(side.round_wins));
                sideRow.append($('<td>').text("-"));
                sideRow.append($('<td>').text(side.round_losses));

                var pistolSideRow = $('<tr>');
                pistolSideRow.append($('<td>').text("Pistol Rounds (" + side.side + ")"));
                pistolSideRow.append($('<td>').text(side.pistol_round_wins));
                pistolSideRow.append($('<td>').text("-"));
                pistolSideRow.append($('<td>').text(side.pistol_round_losses));

                tbody.append(sideRow);
                tbody.append(pistolSideRow);
            }


            statsTable.append(tbody);
            statsTableContainer.append(statsTable);
            sides.append(statsTableContainer);
            mapsContainer.append(sides);
        }
    },
    getMapTickerStats: function(teamSize) {
        var url = "/csgo/getmatchhistory/";
        if (teamSize !== "All") {
            url += "team/" + teamSize;
        }

        cacheAndCallAjax(
            url,
            this.drawMapTicker,
            null
        );
    },
    drawMapTicker: function(response) {
        var ticker = $('#matchTicker');
        ticker.empty();

        var data = JSON.parse(response);
        for (var i = data.length-1; i >= 0; i--) {
            var match = data[i];

            var icon = (match.won == 1) ? "<i class='fa fa-trophy'></i> " : ""; 
            var order = (i+1) + ".";
            var stats = match.map + " " + match.rounds_won + " - " + match.rounds_lost + " (" + match.players + ")";
            ticker.append($('<div>').html(order + " " + icon + stats));
        }
    }
});

