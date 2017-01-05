<?
namespace FStorage;
if (!defined("__FSTORAGE_CLIENT_PHP__")) {

    define("__FSTORAGE_CLIENT_PHP__",1);

/**
 * Generic result for API calls
 */
class API_Result {
    /**
     * OK/ERROR
     */
    public $status;

    /**
     * Specific result from API (see method documentation)
     */
    public $result;

    /**
     * In case of error an error code
     */
    public $errorCode;

    /**
     * In case of error an error text
     */
    public $errorText;

}

/**
 * Result buckets
 */
class Result_Buckets {
    public $bucket_name;
    public $bucket_description;
}

/**
 * Result objects
 */
class Result_Objects {
    /**
     * Bucket name
     */
    public $bucket;
    /**
     * Object key
     */
    public $key;
    /**
     * Format YYYY-MM-DD HH:II:SS
     */
    public $dateCreated;
    /**
     * Format YYYY-MM-DD HH:II:SS
     */
    public $dateModified;
    /**
     * MD5 of the object content (32 char)
     */
    public $contentMD5;
    /**
     * Content-type (MIME)
     */
    public $contentType;
    /**
     * Content-size in bytes
     */
    public $contentSize;
    /**
     * Internal URL of the object used to retrieve the data using API_Client
     */
    public $url;
}

/**
 * Provides access to all public apis of FStorage server
 */
class API_Client {
    /**
     * @ignore
     */
    private $url;
    /**
     * @ignore
     */
    private $secret;
    /**
     * @ignore
     */
    private $key;

	/**
	 *
	 * Initializes Client instance
	 * 
	 * @param string $url URL of the server API e.g. http://localhost/fstorage/api.php
	 * @param string $user User
	 * @param string $pass Password
	 */
    public function __construct($url, $user, $pass) {
        $this->url = $url;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * @ignore
     */
    private function authURL($url) {
        return $url . '&user=' . urlencode($this->user) . '&pass=' . urlencode($this->pass);
    }

	/**
	 * @ignore
	 */
    private function curl() {
		$ch = curl_init();

        //XXX debug
        //$out = fopen("/tmp/fstorage-api-debug.log","at");
        //curl_setopt($ch, CURLOPT_VERBOSE, true);  
        //curl_setopt($ch, CURLOPT_STDERR, $out);  
        //XXX end debug

		curl_setopt($ch, CURLOPT_URL, $this->url );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3 );
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    	curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
		return $ch;
    }

    /**
     * @ignore
     */
    private function setUploadOptions($ch, $fileSize) {
    //    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
    }

    /**
     * @ignore
     */
	private function setParams($ch, $params) {
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

    /**
     * @ignore
     */
	private function error($code, $description) {
        $obj = new \stdclass();
        $obj->status = "error";
        $obj->result = null;
        $obj->errorCode = $code;
        $obj->errorText = $description;
        return $obj;
    }

    /**
     * @ignore
     */
	private function getResult($ch, $debug=0) {
		$response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response===false) {
            $obj = $this->error(curl_errno($ch), curl_error($ch) );
		    curl_close($ch);
            return $obj;
		}	
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        if ($http_code!="200") {
            $obj = $this->error("HTTP_" . $http_code, trim(strip_tags($body)));
		    curl_close($ch);
            return $obj;
        }
		curl_close($ch);
		$obj = @json_decode($body);
        if ($obj===null) {
            $obj = $this->error("JSON_ERROR", trim(strip_tags($body)));
        }
        return $obj;
	}

    /**
     * @ignore
     */
	private function getBasicParams($method="", $moreParams = array()) {
		return array_merge(array("user"=>$this->user,"pass"=>$this->pass,"method"=>$method), $moreParams);
	}

    /**
     * Performs a NOOP against the server. Useful for checking connection
     *
     * @return API_Result Text from the server
     */
    public function noop() {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("noop"));
		return $this->getResult($ch);
    }

    /**
     * List all available buckets on the server
     *
     * @return API_Result An array of {@see \FStorage\Result_Buckets}
     */
	public function listBuckets() {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("listBuckets"));
		return $this->getResult($ch);
	}

    /**
     * Creates a new bucket
     *
     * Bucket names must contains only: letters, numbers, '_', and '-'
     *
     * If the bucket already exists an error is returned
     *
     * @param string $name The name of the bucket
     * @param string $description Short description of bucket
     * @return API_Result The name of the new bucket
     */
	public function createBucket($name, $description) {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("createBucket", array("name"=>$name,"description"=>$description)));
		return $this->getResult($ch);
	}

    /**
     * Removes a bucket
     *
     * Bucket must be empty to perform this action
     *
     * @param string $name The name of the bucket to be deleted
     * @return API_Result Name of the removed bucket
     */
    public function removeBucket($name) {
		$ch = $this->curl();
        $this->setParams($ch, $this->getBasicParams("removeBucket", array("name"=>$name)));
		return $this->getResult($ch);
	}

    /**
     * List all objects in a bucket
     *
     * @param string $bucket Bucket name
     * @param string $search Object key to search (non case sensitive). You can use '*' wildcard. Not recommended for large buckets
     * @return API_Result An array of {@see \FStorage\Result_Objects}
     */
	public function listObjects($bucket, $search) {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("listObjects", array("bucket"=>$bucket,"search"=>$search)));
		return $this->getResult($ch);
	}

    /**
     * Get an object
     *
     * @param string $bucket Bucket name
     * @param string $key Object key
     * @return API_Result An instance of {@see \FStorage\Result_Objects}
     */
	public function getObject($bucket, $key) {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("getObject", array("bucket"=>$bucket,"key"=>$key)));
		return $this->getResult($ch);
	}

    /**
     * Put object (if object already exists it **WILL be replaced**) with some content (bytes as argument)
     *
     * @param string $bucket Bucket name
     * @param string $key    Object key (may contain / and other special characters, use at your own risk)
     * @param string $contentType Content-type (MIME)
     * @param string $content Object content
     * @return API_Result    The new object {@see \FStorage\Result_Objects}
     */
	public function putObject($bucket, $key, $contentType, $content) {
		$ch = $this->curl();
		$this->setParams($ch, $this->getBasicParams("putObject", array("bucket"=>$bucket,"key"=>$key,"contentType"=>$contentType, "content"=>$content)));
		return $this->getResult($ch);
	}

    /**
     * Upload object (if object already exists it **WILL be replaced**), from a localFile.
     * You can call this method with an uploaded file. as in $_FILES['myfile']['tmp_name']
     *
     * @param string $bucket Bucket name
     * @param string $key    Object key (may contain / and other special characters, use at your own risk)
     * @param string $contentType Content-type (MIME)
     * @param string $localFile Local file upload
     * @return API_Result    The new object {@see \FStorage\Result_Objects}
     */
	public function uploadObject($bucket, $key, $contentType, $localFile) {
		$ch = $this->curl();
        //check is file and is readable
        if (!is_file($localFile) || !is_readable($localFile)) {
            $obj = $this->error("ERROR_UPLOAD_FILE", "'$localFile' not file/readable");
            curl_close($ch);
            return $obj;
        }
        $size = @filesize($localFile);
        if ($size===false) {
            $obj = $this->error("ERROR_UPLOAD_FILESIZE", "Could not determine file size of '$localFile'");
            curl_close($ch);
            return $obj;
        }
        $filename = basename($localFile);
        $this->setUploadOptions($ch, $size);
        $cfile = new \CURLFile($localFile, $contentType, $filename);
		$this->setParams($ch, $this->getBasicParams("uploadObject", array("bucket"=>$bucket,"key"=>$key,"contentType"=>$contentType,"file"=>$cfile)));
		return $this->getResult($ch);
	}

    /**
     * Remove object from DB and storage (cannot be undone)
     *
     * @param string $bucket Bucket name
     * @param string $key    Object key (may contain / and other special characters, use at your own risk)
     * @return API_Result    OK or ERROR is returned on status
     */
	public function removeObject($bucket, $key) {
		$ch = $this->curl();
        $this->setParams($ch, $this->getBasicParams("removeObject", array("bucket"=>$bucket,"key"=>$key)));
		return $this->getResult($ch);
    }

    /**
     * Removes any empty folders inside the bucket (admin)
     *
     * @param string $bucket Bucket name
     * @return API_Result    OK or ERROR is returned on status
     */
	public function pruneBucket($bucket) {
		$ch = $this->curl();
        $this->setParams($ch, $this->getBasicParams("pruneBucket", array("bucket"=>$bucket)));
		return $this->getResult($ch);
    }

    /**
     * Download object to browser (handles HTTP headers, and download). It will be finish 
     * script execution
     *
     * @param string $bucket Bucket name
     * @param string $key    Object key (may contain / and other special characters, use at your own risk)
     * @return void
     */
	public function downloadObject($bucket, $key) {
        $obj = $this->getObject($bucket, $key);
        if (is_object($obj) && $obj->status == "ok") {
            $url = $obj->result->url;
            $this->downloadURL($url);
        }
    }

    /**
     * Executes a HEAD command on the given object URL and returns the headers
     *
     * @param string $url   Object URL
     * @return array    An assoc array with key value for each header
     */
	public function headURL($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->authURL($url));
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
		$response = curl_exec($ch);
        if ($response === false) return false;
        else {
            $headersString = trim($response);
            $lines = explode("\n", $headersString);
            $headers = array();
            foreach($lines as $line) {
                if (strpos($line, "HTTP")===0) {
                    $headers['HTTP'] = $line;
                }
                elseif (($ix=strpos($line,':'))!==false) {
                    $headers[ substr($line, 0, $ix) ] = trim(substr($line, $ix+1));
                }
            }
            return $headers;
        }
    }

    /**
     * Download object to browser directly (handles HTTP headers, and download). It will be finish 
     * script execution
     *
     * @param string $url Object URL
     * @return void
     */
    public function downloadURL($url) {

        if (ob_get_level()) {
            ob_end_clean();
        }
        header_remove();

        $headers = $this->headURL($url);

        //now check headers
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($headers['ETag']==$_SERVER['HTTP_IF_NONE_MATCH']) {
                header('Not Modified',true,304);
                exit;
            }
        }

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientTM = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            $fileTM = strtotime($headers['Last-Modified']);
            if ($clientTM && $fileTM <= $clientTM) {
                header('Not Modified',true,304);
                exit;
            }
        }

        $proxyHeaders = array( "Last-Modified", "ETag", "Accept-Ranges", "Content-Type" );
        foreach($proxyHeaders as $h) {
            header("$h: " . $headers[$h]);
        }

        $http_options = array("method"=>"GET");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $http_options['header'] = "Range: " . $_SERVER['HTTP_RANGE'] . "\r\n";
        }

        //check ranges and call fsockopen
        $opts = array( 'http'=>$http_options );
        $context = stream_context_create($opts);
    
        $fp = fopen($this->authURL($url), 'rb', false, $context);
        fpassthru($fp);
        exit;
    }

    /**
     * Save object to file
     *
     * @param string $bucket Bucket name
     * @param string $key    Object key (may contain / and other special characters, use at your own risk)
     * @param string $filename Filename to write to
     * @return boolean True en caso correcto/false en caso de error
     */
	public function saveObject($bucket, $key, $filename) {
        $obj = $this->getObject($bucket, $key);
        if (is_object($obj) && $obj->status == "ok") {
            $url = $obj->result->url;
            return $this->saveURL($url, $filename);
        }
        return false;
    }

    /**
     * Save object to file
     *
     * @param string $url Object URL
     * @param string $filename Filename to write to
     * @return boolean True en caso correcto/false en caso de error
     */
    public function saveURL($url, $filename) {
        return copy($this->authURL($url), $filename);
    }
}
}
