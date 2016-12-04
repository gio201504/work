<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
//use FFMpeg\FFMpeg;
//use FFMpeg\Coordinate\TimeCode;
use Zend\View\Model\JsonModel;
//use FFMpeg\Filters\Video\ResizeFilter;
//use FFMpeg\Coordinate\Dimension;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function getFilesAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$dir = $data->dir;
    		
    		$dir = "D:/NewsBin64/download/tmp";
    		
    		// Open a directory, and read its contents
    		$fileList = array();
    		if (is_dir($dir)){
    			if ($dh = opendir($dir)){
    				while(($file = readdir($dh)) !== false){
    					$fileList[] = $file;
    				}
    				closedir($dh);
    			}
    		}
    		
    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(true);
    		    		    		    		
    		$viewmodel->setVariables(array(
    			'data' => $fileList,
    		));
    		
    		return $viewmodel;
    	}
    }
    
    public function scanAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$dir = $data->dir;
    
    		$top_dir = "D:/NewsBin64/download/";
    		$dir = isset($dir) ? $dir : "files";
    		
    		function countFiles($directory) {
	    		$files = glob($directory . '/*');
	    		
	    		if ( $files !== false )
	    		{
	    			$filecount = count($files);
	    			return $filecount;
	    		}
	    		else
	    			return 0;
    		}

    		function scan($top_dir, $dir){
//     			$thumbs = "thumbs";
    			$fulldir = $top_dir . $dir;
				// Is there actually such a folder/file?
	    		$files = array();
				if(file_exists($fulldir)){
					foreach(scandir($fulldir) as $f) {
						$f_utf8 = utf8_encode($f);
						if(!$f || $f[0] == '.') {
							continue; // Ignore hidden files
						}
			
						if(is_dir($fulldir . '/' . $f)) {
							// The path is a folder
							$files[] = array(
								"name" => $f_utf8,
								"type" => "folder",
								"path" => $dir . '/' . $f_utf8,
								"items" => countFiles($top_dir . $dir . '/' . $f),
							);
						} else {
							// It is a file
							$files[] = array(
								"name" => $f_utf8,
								"type" => "file",
								"path" => $dir . '/' . $f_utf8,
								"size" => filesize($fulldir . '/' . $f), // Gets the size of this file
								"fullname" => $fulldir . '/' . $f_utf8,
							);
							
							/*
							if (file_exists($top_dir . $thumbs . '/' . $f . '.png'))
								$files[] = array_merge($array, array("icon" => true));
							else
								$files[] = $array;
							*/
						}
					}
				}
				
				return $files;
    		}
    		
    		$response = scan($top_dir, $dir);

    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(false);
    		
    		$data = array(
    				"name" => $dir,
    				"type" => "folder",
    				"path" => $dir,
    				"items" => $response,
    		);

    		$viewmodel->setVariables(array(
    				//"data" => json_encode($data),
    				"data" => $data,
    		));
    
    		return $viewmodel;
    	}
    }
    
    public function getThumbAjaxAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
//     		$uri = $request->getUri();
//     		$basePath = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
    		
    		$file = $data->file;
    		$time = $data->time;
    		$top_dir = "D:/NewsBin64/download";
    		
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    			
//     			$filetmp = dirname($file) . '/' . rawurlencode(basename($file));
//     			$file = $filetmp;
    			$thumb_path = getcwd() . '/public/thumb/' . basename($file) . '[' . $time_seconds . '].jpg';
    			//$thumb_file = $basePath . $file . '[' . $time_seconds . '].jpg';
    			$thumb_file = '/videojs/app/public/thumb/' . basename($file) . '[' . $time_seconds . '].jpg';
    			if (!file_exists($thumb_path)) {
// 		    		$ffmpeg = FFMpeg::create();
// 		    		$video = $ffmpeg->open($basePath . $file);
// 		    		$video
// 			    		->filters()
// 			    		//->resize(new Dimension(320, 240))
// 			    		->synchronize();
// 		    		$video
// 			    		->frame(TimeCode::fromSeconds($time_seconds))
// 			    		->save(rawurldecode($thumb_path));
					//$time_formatted = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
					//$cmd = "ffmpeg -ss " . $time_seconds . " -i " . $basePath . $file . " -vframes 1 -filter:v scale='200:-1' \"" . rawurldecode($thumb_path) . "\"";
    				$cmd = "ffmpeg -ss " . $time_seconds . " -i " . "\"" . $top_dir . $file . "\" -vframes 1 -filter:v scale='200:-1' \"" . $thumb_path . "\"";
		    		//shell_exec("/usr/local/bin/ffmpeg -i test.mp3 -codec:a libmp3lame -b:a 128k out.mp3 2>&1");
		    		shell_exec(utf8_decode($cmd));
    			}
    		}
    		
    		return new JsonModel(array(
    				'time' => $time_seconds,
    				'file' => $thumb_file)
    		);
    	}    	
    }
    
    public function getThumbAction()
    {
		return new ViewModel();
    }
    
    public function getVideoDurationAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();    
    		$file = $data->file;
    		$top_dir = "D:/NewsBin64/download";
    
    		if (isset($file)) {
    			$cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . "\"" . $top_dir . $file . "\"";
    			exec(utf8_decode($cmd).' 2>&1', $outputAndErrors, $return_value);
    			$duration = $outputAndErrors[0];
    		}
    
    		return new JsonModel(array(
    			'duration' => $duration,
    		));
    	}
    }
    
    public function getVideoPreviewAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    
    		$file = $data->file;
    		$duration = $data->duration;
    		$top_dir = "D:/NewsBin64/download";
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';
    
    		if (isset($file) && isset($duration) && !file_exists($out_file)) {
    			$cmd = 'ffmpeg -i "' . $top_dir . $file . '" -c:v libx264 -filter_complex "[0:v]scale=w=330:h=186[scale],[scale]split=5[copy0][copy1][copy2][copy3][copy4]';
    			for ($i = 0; $i < 5; $i++) {
    				$start = intval($i * $duration / 5);
    				$end = $start + 1;
    				$cmd .= ',[copy' . $i . ']trim=' . $start . ':' . $end . ',setpts=PTS-STARTPTS[part' . $i . ']';
    			}
    			$cmd .= ',[part0][part1][part2][part3][part4]concat=n=5[out]" -map "[out]" "' . $out_file . '"';
    			exec(utf8_decode($cmd).' 2>&1', $outputAndErrors, $return_value);
    		} else
    			$return_value = 0;

    		return new JsonModel(array(
    			'return_value' => $return_value,
    			'file' => $preview_file)
    		);
    	}
    }
    
    public function checkVideoPreviewExistsAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();

    		$file = $data->file;
    		$top_dir = "D:/NewsBin64/download";
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';

    		return new JsonModel(array(
    				'return_value'	=> file_exists($out_file),
    				'file'			=> $preview_file,
    		));
    	}
    }
}
