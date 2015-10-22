//$(document).ready(function () {
//    $(function () {
//        var toSelect = 'pid_' + $('#currentPeriod').val();
//        $("#revisionPeriodSelect option[id='" + toSelect + "']").attr('selected', 'selected');
//    });
//
//    $('#revisionPeriodSelect').on('change', function (ele) {
//        if ($('#revision_progress_tbody').length) {
//            location.href = '/revision/index/period/' + getCurrentPeriod();
//        } else if ($('#revision_planning_tbody').length) {
//            location.href = '/revision/plan/period/' + getCurrentPeriod();
//        }
//        setCurrentPeriodInDb();
//    });
//});
//
//function setCurrentPeriodInDb() {
//    $.ajax({
//        url:  '/revision/updaterevisionperiod',
//        data: {
//            'current_period': getCurrentPeriod()
//        }
//    }).success(function (response) {
//    });
//}
//
//function getCurrentPeriod() {
//    var id = $('#revisionPeriodSelect').find('option:selected').attr('id');
//    return id.substr(4);
//}