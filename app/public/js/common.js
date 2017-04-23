(function(win) {
	"use strict";
	
	var getThumbAjax = function(video_id, file, time, callback) {
	// 	if ($(video_id).data('requestRunning')) {
	//         return;
	//     } else
	//     	$(video_id).data('requestRunning', true);
		
		//console.log(player.id() + ' getThumbAjax');
		file = file.replace(/^.*\/\/[^\/]+/, '');
		return $.ajax({
	        type: "GET",
	        url: "getThumbAjax",
	        dataType: "json",
	        data: { file: file, time: time },
	        success: function(data) {
	    		if (typeof callback === 'function') {
	        		callback(video_id, data);
	    		}
	        },
	        complete: function() {
	        	$(video_id).data('requestRunning', false);
	        }
	    });
	};
	
	win.streamKill = function() {
		return $.ajax({
	        type: "GET",
	        url: "streamKill",
	        dataType: "json",
	    });
	};
	
	var playTranscodedVideo = function(file, time_seconds, clean) {
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
	
	win.getVideoDuration = function(player, callback) {
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
	
	win.getParameterByName = function(name, url) {
	    if (!url) {
	      url = window.location.href;
	    }
	    name = name.replace(/[\[\]]/g, "\\$&");
	    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
	        results = regex.exec(url);
	    if (!results) return null;
	    if (!results[2]) return '';
	    return decodeURIComponent(results[2].replace(/\+/g, " "));
	};
	
	win.startVideoTranscode = function(path, time_seconds, cleanFolder) {
		cleanFolder = (typeof cleanFolder === 'undefined') ? false : cleanFolder;
		
		var dfd = $.Deferred();
		var promise = dfd
			.then(function(){ return streamKill(); })
			.then(function(){ return playTranscodedVideo(path, time_seconds, cleanFolder); });
			
		dfd.resolve();
		
		return promise;
	};
	
	//Retourne la position de la souris par rapport à la vidéo
	var getMouseTime = function(clientRect, event, duration) {
		var rect = $(clientRect).get(0).getBoundingClientRect();
		//Position absolue souris
		var x = event.pageX;
		//Position absolue vidéo
		var left = rect.left;
		var right = rect.right;
	
		//Position sur la vidéo en secondes
		var time = Math.trunc(((x - left) / (right - left)) * duration);
		return time;
	};
	
	win.addPoster = function(player, data) {
	    //console.log(player.id() + ' addPoster');
		//player.poster(data.file);
	    $(player).attr('src', data.file);
	};
	
	debugger;
	win.getThumbAtMouse = function(player, clientRect, event, callback) {
		if ($(player).data('requestRunning'))
			return;
		else
			$(player).data('requestRunning', true);
	
		var dfd = $.Deferred();
		var promise = dfd
			.then(function(){ return getVideoDuration(player); })
			.then(function(data){
				var time = getMouseTime(clientRect, event, data.duration);
				//Pas de 10 pour les preview
				//time = Math.round((time / data.duration).toFixed(1) * data.duration);
				var time_seconds = "00:00:" + time;
				$(player).data('time', time);
				//Génération et affichage thumbnail
				var video_id = '#' + player.attr('id');
				var file = $(video_id).attr('data-src');
				return getThumbAjax(video_id, file, time_seconds, callback);
			}).then(function(){
				$(player).data('requestRunning', false);
			});
	
		dfd.resolve();
		
		return promise;
	};

}(window));