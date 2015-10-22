$(document).ready(function () {
    $('#addBusPurchase').click(function () {
        $('#inputItems').val('Bus');
        $('#inputCost').val(1.5);
        $('#inputCat').val(3); // Travel
        $('#newPurchaseForm').submit()
    });
});

function edValueKeyPress() {
    var inc = document.getElementById('inputCost').value;
    var ini = document.getElementById('inputItems').value;
    $('#addNewSubmit').prop('disabled', (inc === "" || ini === ""));
}