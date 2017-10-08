var timer;

window.onload = function() {
	// Video
	var video = document.getElementById("player");

	// Buttons
	var playButton = document.getElementById("play-pause");
	var muteButton = document.getElementById("mute");
	var fullScreenButton = document.getElementById("full-screen");

	// Sliders
	var seekBar = document.getElementById("seek-bar");
	var volumeBar = document.getElementById("volume-bar");


	// Event listener for the play/pause button
	playButton.addEventListener("click", function() {
		if (video.paused == true) {
			// Play the video
			video.play();

			// Update the button text to 'Pause'
			playButton.innerHTML = "Pause";
		} else {
			// Pause the video
			video.pause();

			// Update the button text to 'Play'
			playButton.innerHTML = "Play";
		}
	});


	// Event listener for the mute button
	muteButton.addEventListener("click", function() {
		if (video.muted == false) {
			// Mute the video
			video.muted = true;

			// Update the button text
			muteButton.innerHTML = "Unmute";
		} else {
			// Unmute the video
			video.muted = false;

			// Update the button text
			muteButton.innerHTML = "Mute";
		}
	});


	// Event listener for the full-screen button
	fullScreenButton.addEventListener("click", function() {
		if (video.requestFullscreen) {
			video.requestFullscreen();
		} else if (video.mozRequestFullScreen) {
			video.mozRequestFullScreen(); // Firefox
		} else if (video.webkitRequestFullscreen) {
			video.webkitRequestFullscreen(); // Chrome and Safari
		}
	});


//	// Event listener for the seek bar
//	seekBar.addEventListener("change", function() {
//		// Calculate the new time
//		var time = video.duration * (seekBar.value / 100);
//
//		// Update the video time
//		video.currentTime = time;
//	});

	
	// Update the seek bar as the video plays
	video.addEventListener("timeupdate", function() {
		// Calculate the slider value
		var offset = parseInt(getParameterByName('time'));
		var value = (100 / video.finalDuration) * (video.currentTime + offset);

		// Update the slider value
		seekBar.value = value;
	});

	// Pause the video when the seek handle is being dragged
	seekBar.addEventListener("mousedown", function() {
		video.pause();
	});
	
	//Faire apparaître les thumbnails
	seekBar.addEventListener("mouseenter", function() {
		$('#thumbs').show();
	});
	
	//Faire disparaître les thumbnails
	seekBar.addEventListener("mouseleave", function() {
		//$('#thumbs').hide();
	});

	// Play the video when the seek handle is dropped
	seekBar.addEventListener("mouseup", function(event) {
		//Progress bar
		var rect = this.getBoundingClientRect();
		//Position absolue souris
		var x = event.pageX;
		//Position absolue vidéo
		var left = rect.left;
		var right = rect.right;

		//Position sur la vidéo en secondes
		var percent = ((x - left) / (right - left)) * 100;
		$(this).attr("value", percent);
		
		//Calculate the new time
		var time = video.finalDuration * (seekBar.value / 100);

		//Restart transcode
		var path = $(video).attr('data-src');
		
		video.pause();
		var hls = $(video).data('hls');
		hls.stopLoad();
		//hls.detachMedia();
		//RAZ buffers
		hls.trigger(Hls.Events.BUFFER_RESET);
		
		startVideoTranscode(path, time, true)
			.then(function() {
				//Recalculer l'offset
				if (history.pushState) {
				    var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname;
				    var search = window.location.search;
				    search = search.substring(0, search.indexOf('&time=') + 6);
				    newurl += search + Math.trunc(time).toString();
				    window.history.pushState({ path: newurl }, '', newurl);
				    
				    window.location.reload();
				    
				    //Update the video time
				    //hls.attachMedia(video);
				    //hls.startLoad();
					//video.currentTime = 0;
					//video.play();
				}
			});
	});
	
	//Génération thumbnails de la barre de progression
	seekBar.addEventListener('mousemove', function(event) {
		initDisplayThumb(event, video);
	});
	seekBar.addEventListener('touchmove', function(event) {
		initDisplayThumb(event, video);
	});

	// Event listener for the volume bar
	volumeBar.addEventListener("change", function() {
		// Update the video volume
		video.volume = volumeBar.value;
	});
};

//Gérérer un thumbnail au niveau du pointeur de souris
//var initDisplayThumb = function(event, video) {
//	clearTimeout(timer);
//	getThumbAtMouse($(video), $('#seek-bar'), event, displayThumb);
//	
//	timer = setTimeout(function() {
//		getThumbAtMouse($(video), $('#seek-bar'), event, displayThumb, true);
//	}, 300);
//};

//Afficher le thumbnail au niveau du pointeur de souris
var initDisplayThumb = function(event, video) {
	getVideoThumbs($(video), $('#seek-bar'), event, displayThumbMulti);
};

var displayThumb = function(video_id, data) {
	var player = $(video_id);
	var duration = player.get(0).finalDuration;
	var time = data.time;
	var thumbs = $('#thumbs');
	var img = $(thumbs).find('> img');
	$(img).attr('src', data.file);
	
	//Player
	var rectp = player.get(0).getBoundingClientRect();
	var leftp = rectp.left;
	
	//Progress bar
	var rect = $('#seek-bar').get(0).getBoundingClientRect();
	var left = rect.left;
	var right = rect.right;
	
	//Positionnement thumbs
	var percent = time / duration;
	var xpos = left - leftp + percent * (right - left);
	$(thumbs).css('left', xpos + "px");
	var height = $(thumbs).height();
	$(thumbs).css('top', -height + "px");
};

var displayThumbMulti = function(video_id, data, time) {
	var player = $(video_id);
	var duration = player.get(0).finalDuration;
	var thumbs = $('#thumbs');
	var img = $(thumbs).find('> img');
	$(img).attr('src', data.file);
	
	//Player
	var rectp = player.get(0).getBoundingClientRect();
	var leftp = rectp.left;
	
	//Progress bar
	var rect = $('#seek-bar').get(0).getBoundingClientRect();
	var left = rect.left;
	var right = rect.right;
	
	//Positionnement thumbs
	var percent = time / duration;
	var xpos = left - leftp + percent * (right - left);
	$(thumbs).css('left', xpos + "px");
	var height = $(thumbs).height();
	$(thumbs).css('top', -height + "px");
	
	//Sélection thumbnail à afficher parmi les 10
	var img_width = $(img).width();
	var thumb_width = img_width / 10;
	var thumb_num = (percent * 10).toFixed();
	var lcrop = thumb_num * thumb_width;
	var rcrop = img_width - lcrop - thumb_width;
	var inset = 'inset(0px ' + rcrop + 'px 0px ' + lcrop + 'px)';
	//crop image: inset(top right bottom left)
	$(img).css('clip-path', inset);
};
