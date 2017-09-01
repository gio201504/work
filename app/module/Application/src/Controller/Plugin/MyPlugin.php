<?php
namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class MyPlugin extends AbstractPlugin {

	//Convert file sizes from bytes to human readable units
    public function bytesToSize($bytes) {
    	$sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB');
    	if ($bytes === 0) return '0 Bytes';
    	$i = floor(log($bytes) / log(1024));
    	return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }
    		
    public function countFiles($directory, $search = null, $log) {
    	//$log->info("   countFiles " . $directory);
    	if ($search === null)
	    	$files = glob($directory . '/*');
    	else
    		$files = glob($directory . '/*' . $search . '*');
		if ($files !== false) {
			$filecount = count($files);
			//$log->info(" countFiles " . $directory);
			return $filecount;
		} else {
			//$log->info(" countFiles " . $directory);
			return 0;
		}
	}
	
	public function scan($Empl, $dir, $search = null, $forwardPlugin, $log, $cache, $tempCache) {
		$emplacement = $Empl->getCurrentEmpl();
		$top_dir = $emplacement['top_dir'];
		$isFtpFolder = $emplacement['protocole'] === 'ftp';
		$empl = $emplacement['emplacement'];
		$fulldir = $top_dir . $dir;
		
		//Stockage du nombre de fichiers du dossier scanné dans le cache APCu
		$iFileCount = $this->countFiles($fulldir, $search, $log);
		$cache->setItem($fulldir . '[iFileCount]', $iFileCount);
		
		//Tester si un scan du dossier est déjà en cours d'exécution
		if (!$tempCache->hasItem($fulldir . '[scanning]')) {
			//Verrou
			$tempCache->setItem($fulldir . '[scanning]', true);
			
			//Test existence cache
			if (/*$isFtpFolder ||*/ !$cache->hasItem($fulldir)) {
				$files = array();
				//Is there actually such a folder/file?
				if ($isFtpFolder || file_exists($fulldir)) {
					if (!$isFtpFolder) {
						$handle = opendir($fulldir);
					} else {
						$handle = opendir($fulldir . '/*');
					}
					
					$iFileIndex = 1;
					while (($f = readdir($handle)) !== false && isset($f)) {
						//$log->info($f);
						if (!$f || $f[0] == '.') {
							continue; //Ignore hidden files
						}
						
						//sleep(1);
												
						//Stockage fichier scanné dans le cache APCu
						$cache->setItem($fulldir . '[sScannedFile]', $f);
						$cache->setItem($fulldir . '[iFileIndex]', $iFileIndex);
						
						if ($isFtpFolder) {
							$f_utf8 = iconv("ISO-8859-1", "UTF-8", $f);
						} else {
							$f_utf8 = $f;
						}
						
						if ($isFtpFolder) {
							$t1 = round(microtime(true) * 1000);
							$conn_id = $Empl->getConnection($empl);
							$is_dir = @ftp_chdir($conn_id, $dir . '/' . $f);
							$t2 = round(microtime(true) * 1000);
						} else {
							$t1 = round(microtime(true) * 1000);
							$is_dir = is_dir($fulldir . '/' . $f);
							$t2 = round(microtime(true) * 1000);
						}
						//$log->info("is_dir(" . $fulldir . '/' . $f . ") " . ($t2 - $t1));
						
						if ($search !== null && strpos($f, $search) === false && !$is_dir)
							continue;
						
						if ($is_dir) {
							//The path is a folder
							$files[] = array(
									"name" => $f_utf8,
									"type" => "folder",
									"emplacement" => $empl,
									"path" => $dir . '/' . $f_utf8,
									"items" => $this->countFiles($top_dir . $dir . '/' . $f, $search, $log) 
							);
						} else {
							//It is a file
							if ($isFtpFolder) {
								$t1 = round(microtime(true) * 1000);
								$conn_id = $Empl->getConnection($empl);
								$filesize = ftp_size($conn_id, $dir . '/' . $f);
								$t2 = round(microtime(true) * 1000);
							} else {
								$filesize = @filesize($fulldir . '/' . $f);
							}
							if (!$filesize || $filesize < 0) {
								$filesize = 0;
							}
							//$log->info("filesize(" . $fulldir . '/' . $f . ") " . ($t2 - $t1));
							
							$array = array(
									"name" => $f_utf8,
									"type" => "file",
									"emplacement" => $empl,
									"path" => $dir . '/' . $f_utf8,
									"size" => $this->bytesToSize($filesize)
							//"fullname" => $fulldir . '/' . $f_utf8,
													);
							
							//Si vidéo générer thumbnail
							$filename = $fulldir . '/' . $f;
							
							//Renvoyer le type MIME
							if (!$isFtpFolder) {
								$mime = mime_content_type($fulldir . '/' . $f);
							} else {
								$mime_data = $Empl->ftp_get_contents($empl, $dir . '/' . $f, 48);
								
								$finfo = finfo_open();
								$mime = finfo_buffer($finfo, $mime_data, FILEINFO_MIME_TYPE);
								finfo_close($finfo);
							}
							
							if (strstr($mime, "video/")) {
								//Durée de la vidéo
								$data = (object) array(
										'top_dir' => $top_dir,
										'file' => $dir . '/' . $f,
										'empl' => $empl 
								);
								$result = $forwardPlugin->dispatch('Application\Controller\IndexController', array(
										'action' => 'getVideoDuration',
										'data' => $data 
								));
								$time = gmdate("H:i:s", $result->duration / 2);
								
								//Génération thumbnail
								$file = $dir . '/' . $f;
								$data = (object) array(
										'top_dir' => $top_dir,
										'file' => $file,
										'time' => $time,
										'empl' => $empl 
								);
								$result = $forwardPlugin->dispatch('Application\Controller\IndexController', array(
										'action' => 'getThumbAjax',
										'data' => $data 
								));
								$thumb = array(
										'thumb' => $result->file 
								);
								$array = array_merge($array, $thumb);
							}
							
							$files[] = $array;
						}
						
						$iFileIndex++;
					}
					closedir($handle);
				}
				
				//Sauvegarde dans le cache
				//if (!$isFtpFolder) {
					$cache->addItem($fulldir, json_encode($files));
				//}
			} else {
				$files = $cache->getItem($fulldir);
				$files = json_decode($files, true);
			}
			
			//Suppression verrou
			$tempCache->removeItem($fulldir . '[scanning]');
		} else {
			//Si un scan du dossier est en cours attendre la fin du scan
			//$start_time = time();
			//while ($tempCache->hasItem($fulldir . '[scanning]')) {
			//	sleep(1);
			//	if (time() - $start_time > 10) {
					return false;
			//	}
			//}
			
			$files = $cache->getItem($fulldir);
		}
		
		return $files;
	}

}
