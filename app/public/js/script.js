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
		var percent = Math.trunc(((x - left) / (right - left)) * 100);
		$(this).attr("value", percent);
		
		//Calculate the new time
		var time = video.finalDuration * (seekBar.value / 100);

		//Restart transcode
		var path = $(video).attr('data-src');
		
		video.pause();
		var hls = $(video).data('hls');
		hls.stopLoad();
		startVideoTranscode(path, time, true)
			.then(function() {
				//Recalculer l'offset
				debugger;
				if (history.pushState) {
				    var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname;
				    var search = window.location.search;
				    search = search.substring(0, search.indexOf('&time=') + 6);
				    newurl += search + Math.trunc(time).toString();
				    window.history.pushState({ path: newurl }, '', newurl);
				    
				    //Update the video time
				    hls.startLoad();
					video.currentTime = 0;
					video.play();
				}
			});
	});

	// Event listener for the volume bar
	volumeBar.addEventListener("change", function() {
		// Update the video volume
		video.volume = volumeBar.value;
	});
};
