<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use Zend\View\Model\JsonModel;
use FFMpeg\Filters\Video\ResizeFilter;
use FFMpeg\Coordinate\Dimension;

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
    		$dir = "files";

    		function scan($top_dir, $dir){
    			$thumbs = "thumbs";
    			$fulldir = $top_dir . $dir;
				// Is there actually such a folder/file?
	    		$files = array();
				if(file_exists($fulldir)){
					foreach(scandir($fulldir) as $f) {
						if(!$f || $f[0] == '.') {
							continue; // Ignore hidden files
						}
			
						if(is_dir($fulldir . '/' . $f)) {
							// The path is a folder
							$files[] = array(
								"name" => $f,
								"type" => "folder",
								"path" => $dir . '/' . $f,
								"items" => scan($top_dir, $dir . '/' . $f) // Recursively get the contents of the folder
							);
						} else {
							// It is a file
							$array = array(
								"name" => $f,
								"type" => "file",
								"path" => $dir . '/' . $f,
								"size" => filesize($fulldir . '/' . $f), // Gets the size of this file
								"fullname" => $fulldir . '/' . $f,
							);
							
							if (file_exists($top_dir . $thumbs . '/' . $f . '.png'))
								$files[] = array_merge($array, array("icon" => true));
							else
								$files[] = $array;
						}
					}
				}
				
				return $files;
    		}
    		
    		$response = scan($top_dir, $dir);

    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(false);
    		
    		$data = array(
    				"name" => "files",
    				"type" => "folder",
    				"path" => $dir,
    				"items" => $response,
    		);

    		$viewmodel->setVariables(array(
    				"data" => json_encode($data),
    		));
    
    		return $viewmodel;
    	}
    }
    
    public function getThumbAjaxAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$uri = $request->getUri();
    		$basePath = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
    		
    		$file = $data->file;
    		$time = $data->time;
    		
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    			
    			$thumb_path = getcwd() . '/' . basename($file) . '[' . $time_seconds . '].jpg';
    			//$thumb_file = $basePath . $file . '[' . $time_seconds . '].jpg';
    			$thumb_file = '/videojs/app' . $file . '[' . $time_seconds . '].jpg';
    			if (!file_exists($thumb_path)) {
		    		$ffmpeg = FFMpeg::create();
		    		$video = $ffmpeg->open($basePath . $file);
		    		$video
			    		->filters()
			    		//->resize(new Dimension(320, 240))
			    		->synchronize();
		    		$video
			    		->frame(TimeCode::fromSeconds($time_seconds))
			    		->save($thumb_path);
		
		    		return new JsonModel(array(
		    				'status' => 'ok',
		    				'time' => $time_seconds,
		    				'file' => $thumb_file)
		    		);
    			}
    		}
    	}
    	
    	return new JsonModel(array('status' => 'ko'));
    }
    
    public function getThumbAction()
    {
		return new ViewModel();
    }
}
