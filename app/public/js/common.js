(function(win) {
	"use strict";
	
	var getThumbAjax = function(video_id, file, time, callback) {
	// 	if ($(video_id).data('requestRunning')) {
	//         return;
	//     } else
	//     	$(video_id).data('requestRunning', true);
		
		//console.log(player.id() + ' getThumbAjax');
		file = file.replace(/^.*\/\/[^\/]+/, '');
		var url = getSenderPath() + "getThumbAjax";

		return $.ajax({
	        type: "GET",
	        url: url,
	        dataType: "json",
	        data: { empl: win.emplacement, file: file, time: time },
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
		
		var url = getSenderPath() + "transcodeVideo";
	
		return $.ajax({
	        type: "GET",
	        url: url,
	        dataType: "json",
	        data: { empl: win.emplacement, file: file, time: time_seconds, clean: clean },
	    });
	};
	
	win.getVideoDuration = function(player, callback) {
		//console.log(player.id() + ' getVideoDuration');
		var video_id = '#' + player.attr('id');
		var file = $(video_id).attr('data-src');
	
		var url = getSenderPath() + "getVideoDuration";

		return $.ajax({
	        type: "GET",
	        url: url,
	        dataType: "json",
	        data: { empl: win.emplacement, file: file },
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
		//Evénement touch
		if (event.type === 'touchmove') {
			x = event.originalEvent !== undefined ? event.originalEvent.touches[0].pageX : event.touches[0].pageX; 
		}
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
	
	win.getThumbAtMouse = function(player, clientRect, event, callback, forced) {
		if (typeof forced === "undefined") {
			forced = false;
		}

		if (forced === false) {
			if ($(player).data('requestRunning'))
				return;
			else
				$(player).data('requestRunning', true);
		}
		
		var duration = $(player).data('duration');
	
		var dfd = $.Deferred();
		var promise = dfd
			//.then(function(){ return getVideoDuration(player); })
			.then(function(data){
				//var time = getMouseTime(clientRect, event, data.duration);
				var time = getMouseTime(clientRect, event, duration);
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
	
	var getScannedFileIndexAjax = function(dir, empl) {
		$.ajax({
	        type: "GET",
	        url: "getScannedFileIndex",
	        dataType: "json",
	        data: { dir: dir, empl: empl},
	        success: function(data) {
	        	var str;
	        	if (data.file !== false) {
	        		str = "(" + data.fileIndex + "/" + data.fileCount + ") " + data.file;
	        		$("#scanningDiv").show();
		        	$("#scanningDiv").html(str);
	        	}
	        }
	    });
	};
	
	win.scanFolderAjax = function(dir, empl) {
		var intervalId = setInterval(function() { getScannedFileIndexAjax(dir, empl); }, 1000);
		
		$.ajax({
	        type: "GET",
	        url: "scan",
	        //dataType: "html",
	        data: { dir: dir, empl: empl },
	        success: function(data) {
	        	if (!data.isScanning) {
	        		clearInterval(intervalId);
		        	$("div.filemanager").parent(".container").html(data);
		        	$("#scanningDiv").hide();
	        	}
	        },
	    });
	};
	
	var getSenderPath = function() {
		if (typeof senderUrl !== 'undefined' && senderUrl.length > 0) {
			var path = "http://" + senderUrl + "/videojs/app/public/application/";
			return path;
		} else {
			return "";
		}
	};
	
	win.getVideoThumbs = function(player, clientRect, event, callback) {
		var time = getMouseTime(clientRect, event, duration);
		var video_id = '#' + player.attr('id');
		
		var thumbs = $(player).data('thumbs');
		
		if (typeof thumbs === 'undefined') {
			var duration = $(player).data('duration');
			var file = $(video_id).attr('data-src');
			
			var url = getSenderPath() + "getVideoThumbs";
			return $.ajax({
		        type: "GET",
		        url: url,
		        dataType: "json",
		        data: { empl: win.emplacement, file: file, duration: duration },
		        success: function(data) {
		        	$(player).data('thumbs', data.file);
		        	
		    		if (typeof callback === 'function') {
		        		callback(video_id, data.file, time);
		    		}
		        },
		    });
		} else {
			callback(video_id, thumbs, time);
		}
	};

}(window));