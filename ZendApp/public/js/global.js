/*
 @param url - url to get with the ajax call
 @param callbackFunction - function to process results with
 @param params - object of params, in the form {p1 : v1, p2: v2}
 */
var cache = [];
function cacheAndCallAjax(url, callbackFunction, params, debug) {
    if (cache[url] === undefined) {
        if (debug) {
            console.log('cache miss: ' + url);
        }
        $.ajax({
            url: url
        }).success(function (response) {
            callbackFunction(response, params);
            cache[url] = response;
        });
    } else {
        if (debug) {
            console.log('cache hit: ' + url);
        }
        callbackFunction(cache[url], params);
    }
}

function precedeWithZero(val) {
    return (val < 10) ? "0" + val : val;
}

function convertToAmericanDate(dateString) {
    var s = dateString.split('/');
    return s[2] + "-" + s[1] + "-" + s[0]
}

function convertFromDateObj(date, type) {
    var d = new Date(date || Date.now()),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) {
        month = '0' + month;
    }
    if (day.length < 2) {
        day = '0' + day;
    }

    if (type === 'en_us') {
        return [year, month, day].join('-');
    } else if (type === 'en_gb') {
        return [day, month, year].join('/');
    }
}

/**
 * Used to quickly make frequently used DOM nodes
 */
var DOMCreator = Class.extend({
    init: function() {
    },
    div: function(params) {
        var div = $('<div>');

        if (params.hasOwnProperty('id')) {
            div.attr('id', params.id);
        }

        if (params.hasOwnProperty('class')) {
            div.attr('class', params.class);
        }

        return div;
    },
    row: function() {
        return this.div({
            class: 'row'
        });
    },
    col: function(screen, cols) {
        return this.div({
            class: 'col-' + screen + '-' + cols
        });
    }
});

var dom = new DOMCreator();