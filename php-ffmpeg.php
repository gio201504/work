<?php
use FFMpeg\Format\Video\X264;
use FFMpeg\Format\Video\WMV;
use FFMpeg\Format\Video\WebM;
echo "test";

// spl_autoload_register(function($pClassName) {
// 	$sources = array(
// 		__DIR__ . '/vendor/php-ffmpeg/php-ffmpeg/src/' . $pClassName . ".php",
// 		__DIR__ . '/vendor/doctrine/cache/lib/' . $pClassName . ".php",
// 		__DIR__ . '/vendor/alchemy/binary-driver/src/' . $pClassName . ".php",
// 		__DIR__ . '/vendor/evenement/evenement/src/' . $pClassName . ".php",
// 		__DIR__ . '/vendor/symfony/process/' . $pClassName . ".php",
// 	);
	
// 	foreach ($sources as $source) {
// 		if (file_exists($source)) {
// 			require_once $source;
// 		}
// 	}
// });

require_once 'vendor/autoload.php';

$ffmpeg = FFMpeg\FFMpeg::create();
$video = $ffmpeg->open('isa.mkv');
$video
	->filters()
	//->resize(new FFMpeg\Coordinate\Dimension(320, 240))
	->synchronize();
$video
	->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
	->save('frame.png');
$video
	->save(new X264('libmp3lame'), 'export-x264.mp4')
	->save(new WMV('wmav2'), 'export-wmv.wmv')
	->save(new WebM('wmav2'), 'export-webm.webm');

$format = new X264('libmp3lame');
$format->on('progress', function ($video, $format, $percentage) {
	echo "$percentage % transcoded";
});

$format
	-> setKiloBitrate(1000)
	-> setAudioChannels(2)
	-> setAudioKiloBitrate(256);

$video->save($format, 'video.avi');
