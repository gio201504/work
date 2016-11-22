<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
    
    public function getThumbAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$dir = $data->dir;
    
    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(false);
    
//     		$viewmodel->setVariables(array(
//     				'data' => $fileList,
//     		));
    
    		return $viewmodel;
    	}
    }
}
