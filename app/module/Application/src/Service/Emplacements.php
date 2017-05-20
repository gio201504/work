<?php
namespace Application\Service;

class Emplacements {
	private $connections = array();
	
	private $emplacements = array();
	
	private $currentEmpl;
	
	public function __construct($sm) {
		$config = $sm->get('Config');
		$emplacements = $config['emplacements'];
		foreach ($emplacements as $emplacement) {
			$protocole = $emplacement['protocole'];
			if ($protocole === 'ftp') {
				$top_dir = $emplacement['top_dir'];
				$num_empl = $emplacement['emplacement'];
				$conn = $this->getFtpConnection($top_dir);
				$this->connections[$num_empl] = $conn;
			}
		}
		$this->emplacements = $emplacements;
	}
	
	private function getFtpConnection($uri) {
		// Split FTP URI into:
		// $match[0] = ftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match("/ftp:\/\/(.*?):(.*?)@(.*?)(\/.*)/i", $uri, $match);
	
		// Set up a connection
		$conn = ftp_connect($match[3]);
	
		// Login
		if (ftp_login($conn, $match[1], $match[2]))
		{
			// Change the dir
			ftp_chdir($conn, dirname($match[4]));
	
			// Return the resource
			return $conn;
		}
	
		// Or retun null
		return null;
	}
	
	private function getConnection($empl) {
		return $this->connections[$empl];
	}
	
	public function ftp_get_contents($empl, $filename, $maxlen) {
		$conn_id = $this->getConnection($empl);
		
		//Create temp handler
		$tempHandle = fopen('php://memory', 'r+');
	
		//Initate the download
		$ret = ftp_nb_fget($conn_id, $tempHandle, $filename, FTP_BINARY);
		if ($ret !== FTP_FAILED) {
			rewind($tempHandle);
			$content = stream_get_contents($tempHandle, $maxlen);
			fclose($tempHandle);
			return $content;
		} else {
			fclose($tempHandle);
			return false;
		}
	}
	
	public function ftp_get_to_tcp($empl, $filename, $maxlen) {
		$conn_id = $this->getConnection($empl);
		
		$address = '127.0.0.1';
		$port = 8000;
		 
		if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
			echo "socket_create() a échoué : raison : " . socket_strerror(socket_last_error()) . "\n";
		}
		 
		if (socket_bind($sock, $address, $port) === false) {
			echo "socket_bind() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
		}
		 
		if (socket_listen($sock, 5) === false) {
			echo "socket_listen() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
		}
		 
		if (($msgsock = socket_accept($sock)) === false) {
			echo "socket_accept() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
		}
	
		//     			if (socket_connect($sock, $address, $port) === false) {
		//     				echo "socket_bind() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
		//     			}
		 
		//Create temp handler
		//$tempHandle = fopen('php://memory', 'r+');
		$tempHandle = fopen('D:/Newsbin64/download/tmp_ftp', 'w+');
	
		//Initate the download
		$ret = ftp_nb_fget($conn_id, $tempHandle, $filename, FTP_BINARY);
		if ($ret !== FTP_FAILED) {
			//rewind($tempHandle);
			while ($ret === FTP_MOREDATA) {
				//rewind($tempHandle);
				//$content = stream_get_contents($tempHandle, 2048);
				//socket_write($sock, $content, strlen($content));
				//$sent = socket_write($msgsock, $content, strlen($content));
				//$maxlen -= $sent;
				$ret = ftp_nb_continue($conn_id);
			}
			fclose($tempHandle);
	
			$tempHandle = fopen('D:/Newsbin64/download/tmp_ftp', 'r');
			$sent = 1;
			while (!feof($tempHandle) && $sent > 0) {
				$content = fread($tempHandle, 8192);
				$sent = socket_write($msgsock, $content, strlen($content));
			}
			fclose($tempHandle);
			//$sent = socket_write($msgsock, file_get_contents('D:/Newsbin64/download/tmp_ftp'), strlen($content));
			socket_close($sock);
			return $content;
		} else {
			fclose($tempHandle);
			socket_close($sock);
			return false;
		}
	}
	
	public function getCurrentEmpl() {
		return $this->emplacements[$this->currentEmpl];
	}
	
	public function setCurrentEmpl($num_empl) {
		$this->currentEmpl = $num_empl;
	}
}
