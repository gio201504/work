var getThumbAjax = function(video_id, file, time, callback) {
// 	if ($(video_id).data('requestRunning')) {
//         return;
//     } else
//     	$(video_id).data('requestRunning', true);
	
	//console.log(player.id() + ' getThumbAjax');
	return $.ajax({
        type: "GET",
        url: "getThumbAjax",
        dataType: "json",
        data: { file: file, time: time },
        success: function(data) {
    		if (typeof callback === 'function') {
        		callback(data);
    		}
        },
        complete: function() {
        	$(video_id).data('requestRunning', false);
        }
    });
}

