<?php
require("config.inc.php");
date_default_timezone_set(FSTORAGE_TZ);

$__CONN = null;
function __getconnection() {
	global $__CONN;
	if ($__CONN==null) {
		$dsn = sprintf('mysql:host=%s;dbname=%s', FSTORAGE_MYSQL_HOST, FSTORAGE_MYSQL_DB);
		$__CONN = new \PDO($dsn
			, FSTORAGE_MYSQL_USER
			, FSTORAGE_MYSQL_PASS);
	}
	return $__CONN;
}

function __error($errorCode, $errorText) {
	return array("status"=>"error", "errorCode"=>$errorCode, "errorText"=>$errorText);
}

function __success($info) {
	return array("status"=>"ok", "result"=>$info);
}

class FStorage_API {

    public function __construct() {
        if (!is_dir(FSTORAGE_ROOT) || !is_writable(FSTORAGE_ROOT)) {
            throw new \Exception("FSTORAGE_ROOT=" . FSTORAGE_ROOT . " must exists and be writable");
        }
    }

    public $lastError = null;

    private function isSimpleName($str) {
        return preg_match('/^[a-z0-9.\-\_]+$/i', $str);
    }

    public function bucketExists($bucket) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select count(*) as q from buckets where bucket_name=?");
		$stmt->execute(array($bucket));
        $row = $stmt->fetch();
        return ($row['q']>0);
    }

    public function fetchBucketKey($bucket, $key) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select * from objects where bucket_name=? and object_key=?");
		$stmt->execute(array($bucket, $key));
        $row = $stmt->fetch();
        return $row;
    }

    public function fetchLocation($location) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select * from objects where fs_location=?");
		$stmt->execute(array($location));
        $row = $stmt->fetch();
        return $row;
    }

    public function formatObject($row) {
        return array('bucket' => $row['bucket_name']
            , 'key' => $row['object_key']
            , 'dateCreated' => $row['date_created']
            , 'dateModified' => $row['date_modified']
            , 'contentMD5' => $row['content_md5']
            , 'contentType' => $row['content_type']
            , 'contentSize' => intval($row['content_size'])
            , 'url' => $this->objectURL($row['fs_location'])
        );
    }

    public function getBucketFolder($bucket) {
        return FSTORAGE_ROOT . "/$bucket";
    }

    public function getObjectFolder($bucket, $location) {
        return dirname( $this->getBucketFolder($bucket) . "/" . $location);
    }

    public function getObjectFile($bucket, $location) {
        return $this->getBucketFolder($bucket) . "/" . $location;
    }

    public function getObjectMetaFile($bucket, $location) {
        return $this->getBucketFolder($bucket) . "/" . $location . ".meta";
    }

    public function objectURL( $location ) {
        $protocol = "http"; // 
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST'])?$_SERVER['HTTP_X_FORWARDED_HOST']:$_SERVER['HTTP_HOST'];
        $path = str_replace("api.php","dl.php",$_SERVER['SCRIPT_NAME']);
        return sprintf("%s://%s%s?%s", $protocol, $host, $path, http_build_query(array("l"=>$location)));
    }

    private function baseLocation($bucket, $key) {
        $hash = md5($bucket . $key);
        $final = "";
        $splits = 10;
        for ($i=0; $i<$splits; $i++){
            $final .= '/' . $hash[$i];
        }
        $final .= '/' . $hash;
        return $final;
    }

    public function locationExists($fsLocation) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select count(*) as q from objects where fs_location=?");
		$stmt->execute(array($fsLocation));
        $row = $stmt->fetch();
        return ($row['q']>0);
    }

    public function createBaseObject($bucket, $key) {
        $conn = __getconnection();
        $now = date('Y-m-d H:i:s');
        $fsLocation = $this->baseLocation($bucket, $key);
        $i = 0;
        //check location not exists
        while($this->locationExists($fsLocation)) {
            $i++;
            $fsLocation = $this->baseLocation($bucket, $key.$i);
        }

        $stmt = $conn->prepare("insert into objects (bucket_name,object_key,date_created,date_modified,fs_location) values (?,?,?,?,?)");
        if ($stmt->execute(array($bucket, $key, $now, $now, $fsLocation))) {
            return $this->fetchBucketKey($bucket, $key);
        }
        else {
 			$error = $stmt->errorInfo();
            $this->lastError = $error[2];
        }
        return false;
    }

    public function deleteBaseObject($bucket, $key) {
        $conn = __getconnection();
        $stmt = $conn->prepare("delete from objects where bucket_name=? and object_key=?");
        return $stmt->execute(array($bucket, $key));
    }

    public function updateBaseObject($obj) {
        $conn = __getconnection();
        $stmt = $conn->prepare("update objects set date_created=:date_created
            , date_modified=:date_modified
            , content_md5=:content_md5
            , content_type=:content_type
            , fs_location=:fs_location
            , content_size=:content_size
            where bucket_name=:bucket_name and object_key=:object_key");
        return $stmt->execute(array(
                            ":date_created"=>$obj['date_created']
                            , ":date_modified"=>$obj['date_modified']
                            , ":content_md5"=>$obj['content_md5']
                            , ':content_type'=>$obj['content_type']
                            , ':fs_location'=>$obj['fs_location']
                            , ':content_size'=>$obj['content_size']
                            , ':bucket_name'=>$obj['bucket_name']
                            , ':object_key'=>$obj['object_key']
                        ));
    }

    public function noop() {
		return __success("NOOP successfully received");
    }

    public function listBuckets() {
        $result = array();
        $conn = __getconnection();
        foreach( $conn->query("select * from buckets") as $row) {
            $result[] = $row;
        }
		return __success($result);
    }

	public function createBucket($name, $description) {
        if(!$this->isSimpleName($name)) {
			return __error("INVALID_BUCKET_NAME", "Only letters, numbers and - _");
		}

        if ($this->bucketExists($name)) {
            return __error("BUCKET_ALREADY_EXISTS", "Bucket already exists");
        }

        $conn = __getconnection();
        $stmt = $conn->prepare("insert into buckets (bucket_name,bucket_description) values (?,?)");
        if ($stmt->execute(array($name, $description))) {
		    return __success($name);
        }
        else {
 			$error = $stmt->errorInfo();
            return __error("COULD_NOT_CREATE_BUCKET","Error occurred while creating bucket (" . $error[2] . ")");
        }
	}

	public function removeBucket($name) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select count(*) as q from objects where bucket_name=?");
		$stmt->execute(array($name));
        $row = $stmt->fetch();
        if ($row['q']>0) {
            return __error("BUCKET_NOT_EMPTY", "Bucket must be empty first");
        }

        $stmt = $conn->prepare("select count(*) as q from buckets where bucket_name=?");
		$stmt->execute(array($name));
        $row = $stmt->fetch();
        if ($row['q']==0) {
            return __error("BUCKET_NOT_EXISTS", "Bucket does not exist");
        }

        $this->pruneBucket($name);
        $stmt = $conn->prepare("delete from buckets where bucket_name=?");
        if ($stmt->execute(array($name))){
            rmdir($this->getBucketFolder($name));
		    return __success($name);
        }
        else {
 			$error = $stmt->errorInfo();
            return __error("COULD_NOT_REMOVE_BUCKET","Error occurred while removing bucket (" . $error[2] . ")");
        }
	}

    public function pruneBucket($bucket) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select count(*) as q from buckets where bucket_name=?");
		$stmt->execute(array($bucket));
        $row = $stmt->fetch();
        if ($row['q']==0) {
            return __error("BUCKET_NOT_EXISTS", "Bucket does not exist");
        }

        $bucketDir = $this->getBucketFolder($bucket);
        $cmd = ("find '$bucketDir' -type d -empty -print -delete");
        //echo "===$cmd===";
        $output = array();
        exec($cmd,$output);
        //print_r($output);
        //die();
        return __success("PRUNE OK");
    }

	public function listObjects($bucket, $search) {
        //check bucket
        if (!$this->bucketExists($bucket)) {
            return __error("INVALID_BUCKET", "Bucket does not exist");
        }

		$search = str_replace("*","%",$search);
        $conn = __getconnection();
        $stmt = $conn->prepare("select * from objects where bucket_name=? and object_key like ?");
		$stmt->execute(array($bucket, $search));
        $objects = array();
        while($row = $stmt->fetch()) {
            $objects[] = $this->formatObject($row);
        }
		return __success($objects);
	}

	public function getObject($bucket, $key) {
        $conn = __getconnection();
        $stmt = $conn->prepare("select * from objects where bucket_name=? and object_key=?");
		$stmt->execute(array($bucket, $key));
        $objects = array();
        if ($row = $stmt->fetch()) {
            return __success($this->formatObject($row));
        }
        else {
		    return __error("OBJECT_NOT_FOUND", "Object not found ($bucket @ $key");
        }
	}

	public function putObject($bucket, $key, $contentType, $content) {
        return $this->putOrUploadObject($bucket, $key, $contentType, $content);
	}

	public function uploadObject($bucket, $key, $contentType) {
        return $this->putOrUploadObject($bucket, $key, $contentType, false);
	}

    public function putOrUploadObject($bucket, $key, $contentType, $content) {
        //check bucket
        if (!$this->bucketExists($bucket)) {
            return __error("INVALID_BUCKET", "Bucket does not exist");
        }
        
        //check input file if not content
        if ($content===false) {
            //will check $_FILES array
            if (!is_array($_FILES) || !isset($_FILES['file']) || $_FILES['file']['error']!="") {
                return __error("COULD_NOT_READ_POST_FILE","Please check that that multipart/form-data is set and 'file' is being posted");
            }
        }

        $obj = $this->fetchBucketKey($bucket, $key);
        if (!$obj) {
            $obj = $this->createBaseObject($bucket, $key);
            if (!$obj) {
                return __error("ERROR_CREATING_OBJECT", $this->lastError);
            }
            $destDir = $this->getObjectFolder($bucket, $obj['fs_location']);
            if (!is_dir($destDir) && !mkdir($destDir, 0777, true)) {
                //delete object
                $this->deleteBaseObject($bucket, $key);
                return __error("COULD_NOT_CREATE_FOLDER","Please check permissions of FSTORAGE_ROOT");
            }
        }
        else {
            $destDir = $this->getObjectFolder($bucket, $obj['fs_location']);
        }

        $destFile = $this->getObjectFile($bucket, $obj['fs_location']);
        $metaFile = $this->getObjectMetaFile($bucket, $obj['fs_location']);

        if (!is_writable($destDir)) {
            //no dirs?
            if (!is_dir($destDir) && !mkdir($destDir, 0777, true)) {
                return __error("COULD_NOT_CREATE_FOLDER","Please check permissions of FSTORAGE_ROOT");
            }
            elseif(!is_writable($destDir)) {
                return __error("COULD_NOT_WRITE_FILE","Could not write file '$destFile'");
            }
        }

        //have fs location and record
        //write data
        if ($content===false) { //file upload
            $obj['content_md5'] = md5_file($_FILES['file']['tmp_name']);
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $destFile)) {
                return __error("COULD_NOT_CREATE_FILE","Could not create file");
            }
            chmod($destFile,0777);
        }
        else { //direct write
            $obj['content_md5'] = md5($content);
            if(!file_put_contents($destFile, $content)) {
                return __error("COULD_NOT_CREATE_FILE","Could not create file");
            }
            chmod($destFile,0777);
        }
        $obj['content_type'] = $contentType;
        $obj['content_size'] = filesize($destFile);
        $obj['date_modified'] = date('Y-m-d H:i:s');

        //write metadata file
        file_put_contents($metaFile,json_encode($obj, JSON_PRETTY_PRINT));
        $this->updateBaseObject($obj);

        return __success($this->formatObject($obj));
    }

    public function removeObject($bucket, $key) {
        $obj = $this->fetchBucketKey($bucket, $key);
        if (!$obj) {
            return __error("OBJECT_NOT_EXISTS","Object $bucket @ $key does not exists");
        }

        //remove from file system
        unlink($this->getObjectFile($obj['bucket_name'], $obj['fs_location']));
        unlink($this->getObjectMetaFile($obj['bucket_name'], $obj['fs_location']));

        //remove row
        $conn = __getconnection();
        $stmt = $conn->prepare("delete from objects where bucket_name=? and object_key=?");
        if ($stmt->execute(array($bucket, $key))) {
            return __success("OK");
        }
        else {
 			$error = $stmt->errorInfo();
            return __error("COULD_NOT_DELETE_DB_OBJECT","Error occurred while deleting object (" . $error[2] . ")");
        }
    }

}

