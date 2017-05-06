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
	private $sm;
	
	public function __construct($sm) {
		$this->sm = $sm;
	}
	
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
    		
    		$dir = $request->getServer('top_dir') . "/tmp";
    		
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
    	//$t1 = $this->milliseconds();
    	
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$log = $this->sm->get('log');
    		$data = $request->getQuery();
    		$dir = $data->dir;
    		$search = $data->search;
    		$search = empty($search) ? null : $search;
    		$cache = $this->sm->get('apcucache');

    		$top_dir = $request->getServer('top_dir') . '/';
    		$dir = (isset($dir) && !empty($dir)) ? $dir : null;
    		$forwardPlugin = $this->forward();
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		
    		if (isset($data->clear))
    			$cache->removeItem($top_dir . $dir);

    		// Convert file sizes from bytes to human readable units
    		function bytesToSize($bytes) {
    			$sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB');
    			if ($bytes === 0) return '0 Bytes';
    			$i = floor(log($bytes) / log(1024));
    			return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    		}
    		
    		function countFiles($directory, $search = null, $log) {
    			//$log->info("   countFiles " . $directory);
    			if ($search === null)
	    			$files = glob($directory . '/*');
    			else
    				$files = glob($directory . '/*' . $search . '*');
	    		
	    		if ($files !== false)
	    		{
	    			$filecount = count($files);
	    			//$log->info("   countFiles " . $directory);
	    			return $filecount;
	    		}
	    		else {
	    			//$log->info("   countFiles " . $directory);
	    			return 0;
	    		}
    		}

    		function scan($empl, $top_dir, $dir, $search = null, $forwardPlugin, $log, $cache, $isFtpFolder = false) {
    			$fulldir = $top_dir . $dir;

    			//Test existence cache    			
    			if ($isFtpFolder || !$cache->hasItem($fulldir)) {
		    		$files = array();
		    		// Is there actually such a folder/file?
					if($isFtpFolder || file_exists($fulldir)) {
						if (!$isFtpFolder) {
							$handle = opendir($fulldir);
						} else {
							$handle = opendir($fulldir . '/*');
						}
						
						while(($f = readdir($handle)) !== false && isset($f)) {
							//$log->info($f);
							if(!$f || $f[0] == '.') {
								continue; // Ignore hidden files
							}
							
							$is_dir = is_dir($fulldir . '/' . $f);
							if ($search !== null
									&& strpos($f, $search) === false
		    						&& !$is_dir)
								continue;
							
							$f_utf8 = utf8_encode($f);
							if($is_dir) {
								// The path is a folder
								$files[] = array(
									"name" => $f_utf8,
									"type" => "folder",
									"emplacement" => $empl,
									"path" => $dir . '/' . $f_utf8,
									"items" => countFiles($top_dir . $dir . '/' . $f, $search, $log),
								);
							} else {
								// It is a file
								$array = array(
									"name" => $f_utf8,
									"type" => "file",
									"emplacement" => $empl,
									"path" => $dir . '/' . $f_utf8,
									"size" => bytesToSize(filesize($fulldir . '/' . $f)),
									//"fullname" => $fulldir . '/' . $f_utf8,
								);
								
								//Si vidéo générer thumbnail
								$filename = $fulldir . '/' . $f_utf8;
								
								//Renvoyer le type MIME
								if (!$isFtpFolder) {
									$mime = mime_content_type($fulldir . '/' . $f);
								} else {
 									$stream_options = array('ftp' => array('overwrite' => false));
 									$context = stream_context_create($stream_options);									
 									$mime_data = @file_get_contents($filename, false, $context, 0, 48);
									
									$finfo = finfo_open();
									$mime = finfo_buffer($finfo, $mime_data, FILEINFO_MIME_TYPE, $context);
									finfo_close($finfo);
								}
								
								if (strstr($mime, "video/")) {
									//Durée de la vidéo
									$data = (object) array(
											'top_dir' => $top_dir,
											'file' => $dir . '/' . $f_utf8,
											'empl' => $empl,
									);
									$result = $forwardPlugin->dispatch('Application\Controller\IndexController',
											array(
												'action'	=> 'getVideoDuration',
												'data'		=> $data,
											)
									);
									$time = gmdate("H:i:s", $result->duration / 2);
	
									//Génération thumbnail
									$file = $dir . '/' . $f_utf8;
									if ($isFtpFolder) {
										$file = basename($file);
									}
										
									$data = (object) array(
											'top_dir' => $top_dir,
											'file' => $file,
											'time' => $time,
											'empl' => $empl,
									);
									$result = $forwardPlugin->dispatch('Application\Controller\IndexController',
											array(
													'action'	=> 'getThumbAjax',
													'data'		=> $data,
											)
									);
									$thumb = array('thumb' => $result->file);
									$array = array_merge($array, $thumb);								
								}
								
								$files[] = $array;
							}
						}
						closedir($handle);
					}
					
					//Sauvegarde dans le cache
					if (!$isFtpFolder) {
						$cache->addItem($fulldir, $files);
					}
    			} else
    				$files = $cache->getItem($fulldir);
				
				return $files;
    		}
    		
    		//Scan des emplacements
    		$folder = $folderFTP = array();
    		
    		if ($empl === 0) {
    			$folder[] = array(
    					'name' => 'download',
    					'type' => 'folder',
    					'emplacement' => 1,
    					'path' => 'download',
    					//'items' => count($response),
    					'items' => '',
    			);
    			
    			$folderFTP[] = array(
    					'name' => 'FTP',
    					'type' => 'folder',
    					'emplacement' => 2,
    					'path' => 'files',
    					//'items' => count($responseFTP),
    					'items' => '',
    			);
    		}
    		
    		if ($empl === '1') {
	    		//Scan dossier local
	    		$folder = scan($empl, 'D:/NewsBin64/', $dir, $search, $forwardPlugin, $log, $cache);
    		}
    		
    		if ($empl === '2') {
    			//Scan dossier ftp distant
    			$folderFTP = scan($empl, 'ftp://pi:melissa@127.0.0.1/', $dir, $search, $forwardPlugin, $log, $cache, true);
    		}
    		
    		$emplacements = array_merge($folderFTP, $folder);
    		//$emplacements = $response;
    		
    		//$t2 = $this->milliseconds();
    		//$log->info("scandir(" . $top_dir . $dir . ") " . ($t2 - $t1));

    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(false);
    		
    		$data = array(
    				"name" => $dir,
    				"type" => "folder",
    				"path" => $dir,
    				"emplacement" => $empl,
    				"items" => $emplacements,
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
    	$t1 = $this->milliseconds();
    	
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    		$cache = $this->sm->get('apcucache');
    		$log = $this->sm->get('log');
    		
    		$file = $data->file;
    		$time = $data->time;
    		$top_dir = isset($data->top_dir) ? $data->top_dir : $request->getServer('top_dir') . '/';
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl === '1') {
    			$top_dir = 'D:/NewsBin64/';
    		}
    		if ($empl === '2') {
    			$top_dir = 'ftp://pi:melissa@127.0.0.1/';
    		}
    		
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    			$thumbname = basename($file) . '[' . $time_seconds . '].jpg';
    			
    			if (!$cache->hasItem($thumbname)) {
	    			$thumb_path = str_replace('\\', '/', getcwd()) . '/public/thumb/' . $thumbname;
	    			$thumb_file = '/videojs/app/public/thumb/' . $thumbname;
	
	    			if (!file_exists(utf8_decode($thumb_path))) {
	    				$cmd = "ffmpeg -ss " . $time_seconds . " -i " . "\"" . $top_dir . $file . "\" -vframes 1 -filter:v scale='200:-1' \"" . $thumb_path . "\"";
			    		shell_exec(utf8_decode($cmd));
	    			}
		    		
		    		//Sauvegarde dans le cache
		    		$data_uri = $this->data_uri($thumb_path);
		    		$cache->addItem($thumbname, $data_uri);
    			} else {
    				$data_uri = $cache->getItem($thumbname);
    			}
    			
    			$t2 = $this->milliseconds();
    			$log->info("getThumbAjax " . $thumbname . " " . ($t2 - $t1));
    			
    			return new JsonModel(array(
    					'time' => $time_seconds,
    					'file' => $data_uri)
    			);
    		}
    	}    	
    }
    
    private function data_uri($file, $mime = 'image/png')
    {
    	$contents = file_get_contents(utf8_decode($file));
    	$base64   = base64_encode($contents);
    	return 'data:' . $mime . ';base64,' . $base64;
    }
    
    private static function milliseconds() {
    	$milliseconds = round(microtime(true) * 1000);
    	return $milliseconds;
    }
    
    public function getThumbAction()
    {
		return new ViewModel();
    }
    
    public function getVideoDurationAction()
    {
    	$t1 = $this->milliseconds();
    	
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    		$file = $data->file;
    		$top_dir = isset($data->top_dir) ? $data->top_dir : $request->getServer('top_dir') . '/';
    		$cache = $this->sm->get('apcucache');
    		$log = $this->sm->get('log');
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl === '1') {
    			$top_dir = 'D:/NewsBin64/';
    		}
    		if ($empl === '2') {
    			$top_dir = 'ftp://pi:melissa@127.0.0.1/';
    		}

    		$file_duration = basename($file) . '[duration]';
    		if (!$cache->hasItem($file_duration)) {
    			$duration_path = str_replace('\\', '/', getcwd()) . '/public/thumb/' . $file_duration;
	    		if (!file_exists($duration_path)) {
	    			$cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . "\"" . $top_dir . $file . "\"";
	    			exec(utf8_decode($cmd).' 2>&1', $outputAndErrors, $return_value);
	    			$duration = $outputAndErrors[0];
	    			
	    			file_put_contents($duration_path, $duration);
	    		} else
	    			$duration = file_get_contents($duration_path);
	    		
	    		//Sauvegarde dans le cache
	    		$cache->addItem($file_duration, $duration);
    		} else
    			$duration = $cache->getItem($file_duration);
    		
    		$t2 = $this->milliseconds();
    		$log->info("getVideoDuration " . $file_duration . " " . ($t2 - $t1));
    		
    		return new JsonModel(array(
    				'duration' => $duration,
    		));
    	}
    }
    
    public function generateVideoPreviewAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    
    		$file = $data->file;
    		$duration = $data->duration;
    		$top_dir = $request->getServer('top_dir');
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';
    
    		if (isset($file) && isset($duration) && !file_exists($out_file)) {
    			$cmd = 'ffmpeg -i "' . $top_dir . $file . '" -c:v libx264 -filter_complex "[0:v]scale=w=330:h=186[scale],[scale]split=5[copy0][copy1][copy2][copy3][copy4]';
    			for ($i = 0; $i < 5; $i++) {
    				$start = intval(($i + 1) * $duration / 6);
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
    		$data = isset($data->file) ? $data : $this->params('data');

    		$file = $data->file;
    		$top_dir = $request->getServer('top_dir');
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';

    		return new JsonModel(array(
    				'return_value'	=> file_exists(utf8_decode($out_file)),
    				'file'			=> $preview_file,
    		));
    	}
    }
    
    public function showPlayerAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->path) ? $data : $this->params('data');
    		
    		$uri = $this->getRequest()->getUri();
    		$scheme = $uri->getScheme();
    		$host = $uri->getHost();
    		$base = sprintf('%s://%s', $scheme, $host);
    		$path = $base . $data->path;
    		
    		$viewmodel = new ViewModel();
    		
    		$viewmodel->setVariables(array(
    			"path" => $path,
    			"time" => $data->time,
    		));
    		
    		return $viewmodel;
    	}
    }
    
    public function streamVideoAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    
    		$file = $data->file;
    		$time = $data->time;
    		$top_dir = $request->getServer('top_dir');
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    
    			$gmdate = gmdate('H:i:s', $time_seconds);
    			$cmd = sprintf('ffmpeg -ss %s -re -i "%s" -c:v h264_nvenc -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -f flv rtmp://localhost/small/mystream', $gmdate, $top_dir . $file);
    			shell_exec(utf8_decode($cmd));
    			    			 
    			return new JsonModel();
    		}
    	}
    }
    
    public function streamKillAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$cmd = 'taskkill /F /IM ffmpeg.exe';
    		$output = shell_exec(utf8_decode($cmd));
    		
    		return new JsonModel();
    	}
    }
    
    public function transcodeVideoAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    
    		$file = $data->file;
    		$time_seconds = $data->time;
    		$top_dir = $request->getServer('top_dir');
    		$directory = $request->getServer('directory');
    		$temp_dir = $top_dir . '/' . $directory . '/tmp/';
    		
    		//Nettoyage index.m3u8
    		unlink($temp_dir . 'index.m3u8');
    		
    		//Nettoyage dossier de travail
    		if ($data->clean === 'true') {
	    		$files = glob($temp_dir . '*');
	    		foreach ($files as $filename) {
	    			if(is_file($filename))
	    				unlink($filename);
	    		}
    		}
    		
    		if (isset($file) && isset($time_seconds)) {
    			//sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			//$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    
    			$gmdate = gmdate('H:i:s', $time_seconds);
    			//$cmd = sprintf('ffmpeg -ss %s -re -i "%s" -c:v h264_nvenc -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -f flv rtmp://localhost/small/mystream', $gmdate, $top_dir . $file);
    			$cmd = sprintf('start /min ffmpeg -ss %s -re -i "%s" -c:v h264_nvenc -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -hls_time 5 -hls_list_size 0 %sindex.m3u8', $gmdate, $top_dir . $file, $temp_dir);
    			//shell_exec(utf8_decode($cmd));
    			pclose(popen(utf8_decode($cmd), "r"));
    			
    			do {
    				clearstatcache();
    				$file_exists = file_exists($temp_dir . 'index.m3u8');
    			} while (!$file_exists);

    			return new JsonModel();
    		}
    	}
    }
}
