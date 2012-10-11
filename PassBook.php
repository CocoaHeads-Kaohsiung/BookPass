<?php
  class PassBook {
    protected $certPath;
    protected $certPassword;
    protected $AppleWWDRcertPath = '';
    protected $pkpassFiles = array();
    protected $json;
    protected $SHAs;
    protected $temp = '/tmp/';
    
    private $p_error = '';
    private $uniqid = null;
    
	public function __construct($certPath = false, $certPassword = false, $json = false) {
		if($certPath != false) {
			$this->setCertificate($certPath);
		}
		if($certPassword != false) {
			$this->setCertificatePassword($certPassword);
		}
		if($json != false) {
			$this->setJSON($json);
		}
	}

	public function setCertificate($path) {
		if(file_exists($path)) {
			$this->certPath = $path;
			return true;
		}
		
		$this->p_error = 'Certificate file not found.';
		return false;
	}

	public function setCertificatePassword($p) {
		$this->certPassword = $p;
		return true;
	}

	public function setJSON($json) {
		if(json_decode($json) !== false) {
			$this->json = $json;
			return true;
		}
		$this->p_error = 'This is not a JSON format string.';
		return false;
	}
   
	public function setAppleWWDRcertPath($path) {
		$this->AppleWWDRcertPath = $path;
		return true;
	}

	public function setTempPath($path) {
		if (is_dir($path))
		{
			$this->temp = $path;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	protected function paths() {
		$paths = array(
						'pkpass' 	=> 'pass.pkpass',
						'signature' => 'signature',
						'manifest' 	=> 'manifest.json'
					  );
		
		if(substr($this->temp, -1) != '/') {
			$this->temp = $this->temp.'/';
		}

		if (empty($this->uniqid)) {
			$this->uniqid = uniqid('PassBook', true);

			if (!is_dir($this->temp.$this->uniqid)) {
				mkdir($this->temp.$this->uniqid);
			}
		}

		foreach($paths AS $pathName => $path) {
			$paths[$pathName] = $this->temp.$this->uniqid.'/'.$path;
		}
					  
		return $paths;
	}

	protected function clean() {
		$paths = $this->paths();
	
		foreach($paths AS $path) {
			if(file_exists($path)) {
				unlink($path);
			}
		}

		if (is_dir($this->temp.$this->uniqid)) {
			rmdir($this->temp.$this->uniqid);
		}

		return true;
	}
	
	protected function createManifest() {
		$this->SHAs['pass.json'] = sha1($this->json);
		$hasicon = false;
		foreach($this->pkpassFiles as $name => $path) {
			if(strtolower(basename($name)) == 'icon.png'){
				$hasicon = true;
			}
			$this->SHAs[basename($name)] = sha1(file_get_contents($path));
			
		}
		
		if(!$hasicon){
			$this->p_error = 'Error while creating PassBook. Please Check.';
			$this->clean();
			return false;
		}
		
		
		$manifest = json_encode((object)$this->SHAs);
		
		return $manifest;
	}

	protected function convertPEMtoDER($signature) {
	
//DO NOT MOVE THESE WITH TABS, OTHERWISE THE FUNCTION WON'T WORK ANYMORE!!
$begin = 'filename="smime.p7s"

';
$end = '

------';
		$signature = substr($signature, strpos($signature, $begin)+strlen($begin));    
		$signature = substr($signature, 0, strpos($signature, $end));
		$signature = base64_decode($signature);
		
		return $signature;
	}

	protected function createSignature($manifest) {
		$paths = $this->paths();
		
		file_put_contents($paths['manifest'], $manifest);
		
		$pkcs12 = file_get_contents($this->certPath);
		$certs = array();
		if(openssl_pkcs12_read($pkcs12, $certs, $this->certPassword) == true) {
			$certdata = openssl_x509_read($certs['cert']);
			$privkey = openssl_pkey_get_private($certs['pkey'], $this->certPassword);

			if(!empty($this->AppleWWDRcertPath)){

				if(!file_exists($this->AppleWWDRcertPath)){
					$this->p_error = 'Apple WWDC Certificate does not exist';
					return false;
				}
			
				openssl_pkcs7_sign($paths['manifest'], $paths['signature'], $certdata, $privkey, array(), PKCS7_BINARY | PKCS7_DETACHED, $this->AppleWWDRcertPath);
			}else{
				openssl_pkcs7_sign($paths['manifest'], $paths['signature'], $certdata, $privkey, array(), PKCS7_BINARY | PKCS7_DETACHED);
			}
			
			$signature = file_get_contents($paths['signature']);
			$signature = $this->convertPEMtoDER($signature);
			file_put_contents($paths['signature'], $signature);
			
			return true;
		} else {
			$this->p_error = 'Could not read the certificate';
			return false;
		}
	}

	public function addFile($path, $name = NULL){
		if(file_exists($path)){
			if ($name === NULL)
			{
				$this->pkpassFiles[$path] = $path;
				return true;
			}
			else
			{
				$this->pkpassFiles[$name] = $path;
				return true;
			}
		}
		$this->p_error = 'File did not exist.';
		return false;
	}

	protected function createPKPass($manifest) {
		$paths = $this->paths();
		
		// Package file in Zip (as .pkpass)
		$zip = new ZipArchive();
		if(!$zip->open($paths['pkpass'], ZIPARCHIVE::CREATE)) {
			$this->p_error = 'Could not open '.basename($paths['pkpass']).' with ZipArchive extension.';
			return false;
		}
		
		$zip->addFile($paths['signature'],'signature');
		$zip->addFromString('manifest.json',$manifest);
		$zip->addFromString('pass.json',$this->json);
		foreach($this->pkpassFiles as $name => $path){
			$zip->addFile($path, basename($name));
		}
		$zip->close();
		
		return true;
	}

	public function create($output = false) {
		$paths = $this->paths();
	
		if(!($manifest = $this->createManifest())){
			$this->clean();
			return false;	
		}
		
		if($this->createSignature($manifest) == false) {
			$this->clean();
			return false;
		}
		
		if($this->createPKPass($manifest) == false) {
			$this->clean();
			return false;
		}
		
		// Check if pass is created and valid
		if(!file_exists($paths['pkpass']) || filesize($paths['pkpass']) < 1) {
			$this->p_error = 'Error while creating PassBook. Please Check.';
			$this->clean();
			return false;
		}

		if($output == true) {
			header('Pragma: no-cache');
			header('Content-type: application/vnd.apple.pkpass');
			header('Content-length: '.filesize($paths['pkpass']));
			header('Content-Disposition: attachment; filename="'.basename($paths['pkpass']).'"');
			echo file_get_contents($paths['pkpass']);
			
			$this->clean();
		} else {
			$file = file_get_contents($paths['pkpass']);
			
			$this->clean();
			
			return $file;
		}
	}

	public function checkError(&$error) {
		if(trim($this->p_error) == '') {
			return false;
		}
		
		$error = $this->p_error;
		return true;
	}
	
	public function getError() {
		return $this->p_error;
	}

  }
?>