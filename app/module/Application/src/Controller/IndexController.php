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
    		
    		//$dir = $request->getServer('top_dir') . "/tmp";
    		
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
    		//$log = $this->sm->get('log');
    		$data = $request->getQuery();
    		$dir = $data->dir;
    		$search = $data->search;
    		$search = empty($search) ? null : $search;
    		$cache = $this->sm->get('redis');
    		$cache_ffmpeg = $this->sm->get('redis_ffmpeg');
    		$tempCache = $this->sm->get('redis_tmp');

    		$dir = (isset($dir) && !empty($dir)) ? $dir : null;
    		$forwardPlugin = $this->forward();
    		$plugin = $this->MyPlugin();
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		
    		if (isset($data->clear)) {
    			//$cache->removeItem($top_dir . $dir);
    			$cache->flush();
    			$cache_ffmpeg->flush();
    			$tempCache->flush();
    		}

    		if ($empl === 0) {
    			//Liste des emplacements
    			$config = $this->sm->get('Config');
    			$emplacements = $config['emplacements'];
    			$items = array();
    			foreach ($emplacements as $emplacement) {
	    			$items[] = $emplacement;
    			}
    		} else {
    			//Contenu de l'emplacement courant
    			$Empl = $this->sm->get('Emplacements');
    			$Empl->setCurrentEmpl($empl);
    			//$sessionContainer = $this->sm->get('MySessionContainer');
	    		$items = $plugin->scan($Empl, $dir, $search, $forwardPlugin, null, $cache, $tempCache);
    		}
    		
    		//$t2 = $this->milliseconds();
    		//$log->info("scandir(" . $top_dir . $dir . ") " . ($t2 - $t1));

    		if ($items !== false) {
	    		$viewmodel = new ViewModel();
	    		$viewmodel->setTerminal($request->isXmlHttpRequest());
	    		
	    		$cache_ffmpeg = $this->sm->get('redis_ffmpeg');
	    		$aServer = $cache_ffmpeg->getOptions()->getServer();
	    		$playerUrl = 'http://' . $aServer['host'] . '/videojs/app/public/application/showPlayer';
	    		
	    		$data = array(
	    				"name" => $dir,
	    				"type" => "folder",
	    				"path" => $dir,
	    				"emplacement" => $empl,
	    				"items" => $items,
	    				"playerUrl"	=> $playerUrl,
	    		);
	
	    		$viewmodel->setVariables(array(
	    				//"data" => json_encode($data),
	    				"data" => $data,
	    		));
	    
	    		return $viewmodel;
    		} else {
    			$jsonmodel = new JsonModel(array('isScanning' => true));
    			return $jsonmodel;
    		}
    	}
    }
    
    public function getThumbAjaxAction()
    {
    	//$t1 = $this->milliseconds();
    	
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    		$cache = $this->sm->get('redis');
    		//$log = $this->sm->get('log');
    		
    		$file = $data->file;
    		$time = $data->time;
    		$top_dir = isset($data->top_dir) ? $data->top_dir : "";
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl !== 0) {
    			$config = $this->sm->get('Config');
    			$emplacements = $config['emplacements'];
    			$top_dir = $emplacements[$empl]['top_dir'];
    			$protocole = $emplacements[$empl]['protocole'];
    		}
    		
    		if ($protocole === 'ftp') {
    			$file = iconv("ISO-8859-1", "UTF-8", $file);
    			$file = utf8_decode($file);
    		}
    		
    		if (isset($file) && isset($time)) {
    			sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    			$time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;
    			$thumbname = basename($file) . '[' . $time_seconds . '].jpg';
    			
    			if (!$cache->hasItem($thumbname)) {
	    			$thumb_path = str_replace('\\', '/', getcwd()) . '/public/thumb/' . $thumbname;
	    			$thumb_file = '/videojs/app/public/thumb/' . $thumbname;
	
	    			if (!file_exists($thumb_path)) {
	    				$cmd = "ffmpeg.exe -ss " . $time_seconds . " -i " . "\"" . $top_dir . $file . "\" -vframes 1 -filter:v scale='200:-1' \"" . $thumb_path . "\"";
			    		exec($cmd.' 2>&1', $outputAndErrors, $return_value);
	    			}
		    		
		    		//Sauvegarde dans le cache
		    		$data_uri = $this->data_uri($thumb_path);
		    		$cache->setItem($thumbname, $data_uri);
    			} else {
    				$data_uri = $cache->getItem($thumbname);
    			}
    			
    			//$t2 = $this->milliseconds();
    			//$log->info("getThumbAjax " . $thumbname . " " . ($t2 - $t1));
    			
    			header('Access-Control-Allow-Origin: *');
    			//header('XDomainRequestAllowed: 1');
    			
    			return new JsonModel(array(
    					'time' => $time_seconds,
    					'file' => $data_uri)
    			);
    		}
    	}    	
    }
    
    private function data_uri($file, $mime = 'image/png')
    {
    	$contents = @file_get_contents($file);
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
    	//$t1 = $this->milliseconds();
    	
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    		$file = $data->file;
    		$cache = $this->sm->get('redis');
    		//$log = $this->sm->get('log');
    		$top_dir = isset($data->top_dir) ? $data->top_dir : "";
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl !== 0) {
    			$config = $this->sm->get('Config');
    			$emplacements = $config['emplacements'];
    			$top_dir = $emplacements[$empl]['top_dir'];
    			$protocole = $emplacements[$empl]['protocole'];
    		}

    		if ($protocole === 'ftp') {
    			$file = iconv("ISO-8859-1", "UTF-8", $file);
    			$file = utf8_decode($file);
    		}
    		
    		$file_duration = basename($file) . '[duration]';
    		if (!$cache->hasItem($file_duration)) {
    			$duration_path = str_replace('\\', '/', getcwd()) . '/public/thumb/' . $file_duration;
	    		if (!file_exists($duration_path)) {
		    		$cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . "\"" . $top_dir . $file . "\"";
		    		exec($cmd.' 2>&1', $outputAndErrors, $return_value);
					$duration = $outputAndErrors[0];

	    			if (is_numeric($duration)) {
	    				file_put_contents($duration_path, $duration);
	    				//Sauvegarde dans le cache
	    				$cache->addItem($file_duration, $duration);
	    			}
	    		} else {
	    			$duration = file_get_contents($duration_path);
	    			//Sauvegarde dans le cache
	    			$cache->addItem($file_duration, $duration);
	    		}
    		} else
    			$duration = $cache->getItem($file_duration);
    		
    		//$t2 = $this->milliseconds();
    		//$log->info("getVideoDuration " . $file_duration . " " . ($t2 - $t1));
    		
    		header('Access-Control-Allow-Origin: *');
    		//header('XDomainRequestAllowed: 1');
    		
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
    		//$top_dir = $request->getServer('top_dir');
    		$out_file = getcwd() . '/public/thumb/' . basename($file) . '[preview].mp4';
    		$preview_file = '/videojs/app/public/thumb/' . basename($file) . '[preview].mp4';
    
    		if (isset($file) && isset($duration) && !file_exists($out_file)) {
    			$cmd = 'ffmpeg.exe -i "' . $top_dir . $file . '" -c:v libx264 -filter_complex "[0:v]scale=w=330:h=186[scale],[scale]split=5[copy0][copy1][copy2][copy3][copy4]';
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
    		//$top_dir = $request->getServer('top_dir');
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

    		$config = $this->sm->get('Config');
    		$ffmpeg_codec = $config['ffmpeg']['codec'];
    		$temp_dir = getcwd() . '/public/tmp/';
    		
    		$cache_ffmpeg = $this->sm->get('redis_ffmpeg');
    		//$options = $cache_ffmpeg->getOptions();
    		//$resource = $options->getResourceManager();
    		//$resourceId = $options->getResourceId();
    		//$redis = $resource->getResource($resourceId);
    		
    		//Nettoyage index.m3u8
    		@unlink($temp_dir . 'index.m3u8');
    		
    		//Nettoyage fichiers .ts
    		$files = glob($temp_dir . '*.ts');
    		foreach ($files as $filename) {
    			if(is_file($filename)) {
    				unlink($filename);
    			}
    		}

    		//Nettoyage dossier de travail
    		if ($data->clean === 'true') {
    			$files = glob($temp_dir . '*');
    			foreach ($files as $filename) {
    				if(is_file($filename))
    					unlink($filename);
    				}
    		}
    		
    		//$sender = $redis->blPop('mylist', 300);
    		if ($cache_ffmpeg->hasItem('sender')) {
    			$sender = $cache_ffmpeg->getItem('sender');
    			
	    		if (!empty($sender)) {
	    			$aData = json_decode($sender);

	    			$senderUrl = $aData[2];
	    			$publishUrl = sprintf('%s://%s', 'http', $senderUrl);
	    			$gmdate = $aData[0];
	    			$file = $aData[1];
	    			$publishUrl = $publishUrl . '/videojs/app/public/video';
	    			$cmd = sprintf('start /min ffmpeg.exe -ss %s -i "%s" -c:v %s -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -hls_time 5 -hls_list_size 0 %sindex.m3u8', $gmdate, $publishUrl, $ffmpeg_codec, $temp_dir);
	    			pclose(popen(utf8_decode($cmd), "r"));
	    		}
	
	    		do {
	    			clearstatcache();
	    			$file_exists = file_exists($temp_dir . 'index.m3u8');
	    			sleep(1);
	    		} while (!$file_exists);
	
	    		$viewmodel = new ViewModel();
	    		
	    		$viewmodel->setVariables(array(
	    			"path" => $data->path,
	    			"time" => $data->time,
	    			"duration" => $data->duration,
	    			"emplacement" => $data->empl,
	    			"senderUrl"	=> $senderUrl,
	    		));
	    		
	    		return $viewmodel;
    		} else {
    			return false;
    		}
    	}
    }
    
    public function streamVideoAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
            $cache_ffmpeg = $this->sm->get('redis');

            if ($cache_ffmpeg->hasItem('sender')) {
                $sender = $cache_ffmpeg->getItem('sender');

                if (!empty($sender)) {
                    //Données Redis
                    //$aData = json_decode($sender);
                    //$senderUrl = $aData[2];
                    //$publishUrl = sprintf('%s://%s', 'http', $senderUrl);
                    //$publishUrl = $publishUrl . '/videojs/app/public/video';

                    //Données de config
                    $config = $this->sm->get('Config');
                    $ffmpeg_codec = $config['ffmpeg']['codec'];

                    //Données de la requête
                    $data = $request->getQuery();
                    $file = $data->file;
                    $empl = $data->empl;
                    $time = $data->time;
                    if ($empl !== 0) {
                        $emplacements = $config['emplacements'];
                        $top_dir = $emplacements[$empl]['top_dir'];
                    } else {
                        $top_dir = "";
                    }

                    //$top_dir = $request->getServer('top_dir');
                    if (isset($time)) {
                        sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
                        $time_seconds = isset($seconds) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;

                        $gmdate = gmdate('H:i:s', $time_seconds);
                        $cmd = sprintf('start /min ffmpeg.exe -ss %s -re -i "%s" -c:v %s -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -f flv rtmp://localhost/small/mystream', $gmdate, /*$publishUrl*/$top_dir . $file, $ffmpeg_codec);
                        //shell_exec(utf8_decode($cmd));
                        pclose(popen(utf8_decode($cmd), "r"));

                        return new JsonModel();
                    }
                }
            } else {
                return false;
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
    		$config = $this->sm->get('Config');
    		
    		$cache_ffmpeg = $this->sm->get('redis_ffmpeg');
    		//$options = $cache_ffmpeg->getOptions();
    		//$resource = $options->getResourceManager();
    		//$resourceId = $options->getResourceId();
    		//$redis = $resource->getResource($resourceId);
    
    		$file = $data->file;
    		$time_seconds = $data->time;
			$temp_dir = getcwd() . '/public/tmp/';
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl !== 0) {
    			$emplacements = $config['emplacements'];
    			$top_dir = $emplacements[$empl]['top_dir'];
    		}
    		
    		if (isset($file) && isset($time_seconds)) {
    			//Lien symbolique pointant vers la vidéo
    			$link = getcwd() . '/public/video';
    			if (is_file($link)) {
    				unlink($link);
    			}
    			symlink($top_dir . $file, $link);
    			
    			//Adresse du serveur courant
    			$uri = $this->getRequest()->getUri();
    			$senderUrl = $uri->getHost();
    			
    			$gmdate = gmdate('H:i:s', $time_seconds);
    			$file = str_replace(" ", "%20", basename($file));
    			$aData = array($gmdate, $file, $senderUrl);
    			//$rc = $redis->rPush('mylist', json_encode($aData));
    			$cache_ffmpeg->setItem('sender', json_encode($aData));
    			
    			header('Access-Control-Allow-Origin: *');
    			//header('XDomainRequestAllowed: 1');

    			return new JsonModel();
    		}
    	}
    }
    
    public function getScannedFileIndexAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$dir = $data->dir;
    		$cache = $this->sm->get('redis');
    		$empl = $data->empl;
    		$Empl = $this->sm->get('Emplacements');
    		$Empl->setCurrentEmpl($empl);
    		$emplacement = $Empl->getCurrentEmpl();
    		$top_dir = $emplacement['top_dir'];
    		$fulldir = $top_dir . $dir;
    		
    		$iFileCount = $cache->getItem($fulldir . '[iFileCount]');
    		$iFileIndex = $cache->getItem($fulldir . '[iFileIndex]');
    		
    		if ($cache->hasItem($fulldir . '[sScannedFile]') && $iFileCount !== null)
    			$file = $cache->getItem($fulldir . '[sScannedFile]');
    		else
    			$file = false;
    		
    		return new JsonModel(array(
    				'file' => $file,
    				'fileCount' => $iFileCount,
    				'fileIndex' => $iFileIndex,
    		));
    	}
    }
    
    public function waitAction()
    {
    	$request = $this->getRequest();
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		
    		$config = $this->sm->get('Config');
    		$ffmpeg_codec = $config['ffmpeg']['codec'];
    		$temp_dir = getcwd() . '/public/tmp/';
    		
    		$cache_ffmpeg = $this->sm->get('redis');
    		$options = $cache_ffmpeg->getOptions();
    		$resource = $options->getResourceManager();
    		$resourceId = $options->getResourceId();
    		$redis = $resource->getResource($resourceId);
    		
    		while(true) {
	    		$sender = $redis->blPop('mylist', 300);
	    		
	    		if (!empty($sender)) {
	    			$aData = json_decode($sender[1]);
	    			$gmdate = $aData[0];
	    			$file = $aData[1];
	    			$senderUrl = $aData[2];
	    			$publishUrl = 'http://' . $senderUrl . '/videojs/app/public/tmp/' . $file;
	    			$cmd = sprintf('ffmpeg.exe -ss %s -re -i "%s" -c:v %s -b:v 8000k -maxrate 8000k -bufsize 1000k -c:a aac -b:a 128k -ar 44100 -hls_time 5 -hls_list_size 0 %sindex.m3u8', $gmdate, $publishUrl, $ffmpeg_codec, $temp_dir);
	    			pclose(popen(utf8_decode($cmd), "r"));
	    		}
    		}
    	}
    }
    
    public function getVideoThumbsAction()
    {
    	$request = $this->getRequest();
    	
    	if ($request->isGet()) {
    		$data = $request->getQuery();
    		$data = isset($data->file) ? $data : $this->params('data');
    		$cache = $this->sm->get('redis');

    		$file = $data->file;
    		$duration = $data->duration;
    		$top_dir = isset($data->top_dir) ? $data->top_dir : "";
    		
    		$empl = (isset($data->empl) && !empty($data->empl)) ? $data->empl : 0;
    		if ($empl !== 0) {
    			$config = $this->sm->get('Config');
    			$emplacements = $config['emplacements'];
    			$top_dir = $emplacements[$empl]['top_dir'];
    			$protocole = $emplacements[$empl]['protocole'];
    		}
    
    		if (isset($file) && isset($duration)) {
    			$thumbname = basename($file) . '[thumbs].jpg';
    			
    			if (!$cache->hasItem($thumbname)) {
    				$return_value = 0;
    				$thumb_path = getcwd() . '/public/thumb/' . $thumbname;
    				
    				if (!file_exists($thumb_path)) {
    					$nb_thumbs = 20;
    					$wscale = $nb_thumbs * 250;
    					$outputs = "";
		    			$cmd_s = 'ffmpeg.exe';
		    			$cmd_m = "";
		    			for ($i = 0; $i < $nb_thumbs; $i++) {
		    				$start_time_seconds = intval(($i + 1) * $duration / ($nb_thumbs + 1));
		    				$gmdate = gmdate('H:i:s', $start_time_seconds);
		    				$cmd_s .= sprintf(' -ss %s -i "%s%s"', $gmdate, $top_dir, $file);
		    				$cmd_m .= $i > 0 ? ',' : '"';
		    				$cmd_m .= sprintf('[%s:v]setpts=PTS-STARTPTS[o%s]', $i, $i);
		    				$outputs .= sprintf('[o%s]', $i);
		    			}
		    			$cmd = $cmd_s . ' -filter_complex ' . $cmd_m;
		    			$cmd .= sprintf(',%shstack=inputs=%s[on],[on]scale=w=%s:h=-1[out]" -map "[out]" -vframes 1 "%s"', $outputs, $nb_thumbs, $wscale, $thumb_path);
		    			exec(utf8_decode($cmd).' 2>&1', $outputAndErrors, $return_value);
    				}
    				
    				//Sauvegarde dans le cache
    				$data_uri = $this->data_uri($thumb_path);
    				$cache->setItem($thumbname, $data_uri);
    			} else {
    				$data_uri = $cache->getItem($thumbname);
    				$return_value = 0;
    			}
    		} else
    			$return_value = -1;
    		
    		header('Access-Control-Allow-Origin: *');
    
    		return new JsonModel(array(
    				'return_value' => $return_value,
    				'file' => $data_uri)
    		);
    	}
    }
}
