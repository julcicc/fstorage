<?
namespace FStorage;

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
    private function curl() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url );
    	curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
/*
    curl_setopt($ch, CURLOPT_UPLOAD, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 86400); // 1 Day Timeout
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_NOPROGRESS,false); 
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localFile));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
*/
		return $ch;
    }

    /**
     * @ignore
     */
	private function setParams($ch, $params) {
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
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
	private function getResult($ch) {
		$response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($res===false) {
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
     * Put object (if object already exists it **WILL be replaced**)
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

}
