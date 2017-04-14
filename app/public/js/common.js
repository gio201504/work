"use strict";

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
};

var streamKill = function() {
	debugger;
	return $.ajax({
        type: "GET",
        url: "streamKill",
        dataType: "json",
    });
};

var playTranscodedVideo = function(file, time_seconds, clean) {
	debugger;
	//var time_seconds = <?php echo $this->time; ?>;
	//var file = "<?php echo $this->path; ?>";
    //var time_seconds = "00:00:" + Math.trunc(time);
	file = file.replace(/^.*\/\/[^\/]+/, '');
	clean = (typeof clean === 'undefined') ? false : clean;

	return $.ajax({
        type: "GET",
        url: "transcodeVideo",
        dataType: "json",
        data: { file: file, time: time_seconds, clean: clean },
    });
};

var getVideoDuration = function(player, callback) {
	//console.log(player.id() + ' getVideoDuration');
	var video_id = '#' + player.attr('id');
	var file = $(video_id).attr('data-src');

	return $.ajax({
        type: "GET",
        url: "getVideoDuration",
        dataType: "json",
        data: { file: file },
        success: function(data) {
    		if (typeof callback === 'function') {
        		callback(data);
    		}
        },
    });
};

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

var startVideoTranscode = function(path, time_seconds, cleanFolder) {
	cleanFolder = (typeof cleanFolder === 'undefined') ? false : cleanFolder;
	
	debugger;
	var dfd = $.Deferred();
	var promise = dfd
		.then(function(){ return streamKill(); })
		.then(function(){ return playTranscodedVideo(path, time_seconds, cleanFolder); });
		
	dfd.resolve();
	
	return promise;
};
