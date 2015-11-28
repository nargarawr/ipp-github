$(document).ready(function(){
    $("#ageDatePicker").datepicker({
        changeMonth: true,
        changeYear: true
    });
    $("#ageDatePicker").datepicker( "option", "dateFormat", "dd/mm/yy");
    $("#ageDatePicker").val(
        moment($("#dateField").val()).format("DD/MM/YYYY")
    );
});
