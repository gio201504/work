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
    
    		$dir = "D:/NewsBin64/download/tmp";

			// Is there actually such a folder/file?
    		$files = array();
			if(file_exists($dir)){
				foreach(scandir($dir) as $f) {
					if(!$f || $f[0] == '.') {
						continue; // Ignore hidden files
					}
		
					if(is_dir($dir . '/' . $f)) {
						// The path is a folder
						$files[] = array(
							"name" => $f,
							"type" => "folder",
							"path" => $dir . '/' . $f,
							"items" => scan($dir . '/' . $f) // Recursively get the contents of the folder
						);
					} else {
						// It is a file
						$files[] = array(
							"name" => $f,
							"type" => "file",
							"path" => $dir . '/' . $f,
							"size" => filesize($dir . '/' . $f) // Gets the size of this file
						);
					}
				}
			}

    		$viewmodel = new ViewModel();
    		$viewmodel->setTerminal(true);
    
    		$viewmodel->setVariables(array(
    				'files' => $files,
    		));
    
    		return $viewmodel;
    	}
    }
}
