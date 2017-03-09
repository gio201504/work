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
    		
    		$dir = apache_getenv('top_dir') . "/tmp";
    		
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
    		$log = $this->sm->get('log');
    		$data = $request->getQuery();
    		$dir = $data->dir;
    		$search = $data->search;
    		$search = empty($search) ? null : $search;

    		$top_dir = apache_getenv('top_dir') . '/';
    		$dir = (isset($dir) && !empty($dir)) ? $dir : apache_getenv('directory');
    		$forwardPlugin = $this->forward();
    		
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

    		function scan($top_dir, $dir, $search = null, $forwardPlugin, $log) {
    			$fulldir = $top_dir . $dir;
    			$log->info("scandir(" . $fulldir . ")");

    			//Test existence cache
    			$files = apcu_fetch($fulldir);
    			
    			if ($files === false) {
		    		$files = array();
		    		// Is there actually such a folder/file?
					if(file_exists($fulldir)) {
						$handle = opendir($fulldir);
						while(($f = readdir($handle)) !== false) {
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
									"path" => $dir . '/' . $f_utf8,
									"items" => countFiles($top_dir . $dir . '/' . $f, $search, $log),
								);
							} else {
								// It is a file
								$array = array(
									"name" => $f_utf8,
									"type" => "file",
									"path" => $dir . '/' . $f_utf8,
									"size" => bytesToSize(filesize($fulldir . '/' . $f)),
									"fullname" => $fulldir . '/' . $f_utf8,
								);
								
								//Si vidéo générer thumbnail
								$filename = $fulldir . '/' . $f_utf8;
								$mime = mime_content_type($fulldir . '/' . $f);
								if (strstr($mime, "video/")) {
									//Durée de la vidéo
									$data = (object) array('file' => $dir . '/' . $f_utf8);
									$result = $forwardPlugin->dispatch('Application\Controller\IndexController',
											array(
												'action'	=> 'getVideoDuration',
												'data'		=> $data,
											)
									);
									$time = gmdate("H:i:s", $result->duration / 2);
	
									//Génération thumbnail
									$data = (object) array('file' => '/' . $dir . '/' . $f_utf8, 'time' => $time);
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
					apcu_store($fulldir, $files);
    			}
				
    			$log->info("scandir(" . $fulldir . ")");
				return $files;
    		}
    		
    		$response = scan($top_dir, $dir, $search, $forwardPlugin, $log);

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
    		$data = isset($data->file) ? $data : $this->params('data');
    		
    		$file = $data->file;
    		$time = $data->time;
    		$top_dir = apache_getenv('top_dir');
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    			$thumbname = basename($file) . '[' . $time_seconds . '].jpg';
    			
    			$data_uri = apcu_fetch($thumbname);
    			if ($data_uri === false) {
	    			$thumb_path = getcwd() . '/public/thumb/' . $thumbname;
	    			$thumb_file = '/videojs/app/public/thumb/' . $thumbname;
	
	    			if (!file_exists($thumb_path)) {
	    				$cmd = "ffmpeg -ss " . $time_seconds . " -i " . "\"" . $top_dir . $file . "\" -vframes 1 -filter:v scale='200:-1' \"" . $thumb_path . "\"";
			    		shell_exec(utf8_decode($cmd));
	    			}	    			
		    		
		    		//Sauvegarde dans le cache
		    		$data_uri = $this->data_uri($thumb_path);
		    		apcu_store($thumbname, $data_uri);
    			}
    			
    			return new JsonModel(array(
    					'time' => $time_seconds,
    					'file' => $data_uri)
    			);
    		}
    	}    	
    }
    
    private function data_uri($file, $mime = 'image/png')
    {
    	$contents = file_get_contents($file);
    	$base64   = base64_encode($contents);
    	return 'data:' . $mime . ';base64,' . $base64;
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
    		$data = isset($data->file) ? $data : $this->params('data');
    		$file = $data->file;
    		$top_dir = apache_getenv('top_dir') . '/';
    
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
    
    public function generateVideoPreviewAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    
    		$file = $data->file;
    		$duration = $data->duration;
    		$top_dir = apache_getenv('top_dir');
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
    		$top_dir = apache_getenv('top_dir');
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';

    		return new JsonModel(array(
    				'return_value'	=> file_exists(utf8_decode($out_file)),
    				'file'			=> $preview_file,
    		));
    	}
    }
}
