$(document).ready(function () {
    if ($('#sendMessageBtn').length > 0) {
        $('#sendMessageBtn').click(function () {
            $.ajax({
                type: 'POST',
                url:  '/labs/sendmessage',
                data: {
                    message:   $('#messageText').val(),
                    timestamp: '20:30'
                }
            }).success(function (response) {
                $('#messageText').val("")
            });
        });

        var pusher = new Pusher('f43c16c9bdcce3f6e731');
        var channel = pusher.subscribe('chat_channel');
        channel.bind('send_message', function (data) {
            var pTag = $('<p>');
            pTag.html("(" + data.timestamp + ") <b>" + data.sender + " says:</b> " + data.message);
            $('#newsfeedContainer').prepend(pTag);
        });
    }
});