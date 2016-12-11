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

        $conn = __getconnection();
        $stmt = $conn->prepare("select count(*) as q from buckets where bucket_name=?");
		$stmt->execute(array($name));
        $row = $stmt->fetch();
        if ($row['q']>0) {
            return __error("BUCKET_ALREADY_EXISTS", "Bucket already exists");
        }

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

        $stmt = $conn->prepare("delete from buckets where bucket_name=?");
        if ($stmt->execute(array($name))){
		    return __success($name);
        }
        else {
 			$error = $stmt->errorInfo();
            return __error("COULD_NOT_REMOVE_BUCKET","Error occurred while removing bucket (" . $error[2] . ")");
        }
	}

	public function listObjects($bucket, $search) {
		$search = str_replace("*","%",$search);

		$objects = array();
		$objects[] = array("key"=>"aasss", "dateCreated"=>date('Y-m-d H:i:s'), "dateModified"=>date('Y-m-d H:i:s'), "contentMD5"=>md5("hola"), "contentType"=>"text/plain", "contentSize"=>121, "url" => "http://211.222.222.222/download/a/a7a7a");
		$objects[] = array("key"=>"aasss", "dateCreated"=>date('Y-m-d H:i:s'), "dateModified"=>date('Y-m-d H:i:s'), "contentMD5"=>md5("hola"), "contentType"=>"text/plain", "contentSize"=>1212, "url" => "http://211.222.222.222/download/a/a7a7a");

		return __success($objects);
	}

	public function putObject($bucket, $key, $contentType, $content) {
		$result = array("key"=>"aasss", "dateCreated"=>date('Y-m-d H:i:s'), "dateModified"=>date('Y-m-d H:i:s'), "contentMD5"=>md5($content), "contentType"=>$contentType, "contentSize"=>122, "url" => "http://211.222.222.222/download/a/a7a7a");
		return __success($result);
	}

	public function uploadObject($bucket, $key, $contentType) {
        //will check $_FILES array
        if (!is_array($_FILES) || !isset($_FILES['file'])) {
            return __error("COULD_NOT_READ_POST_FILE","Please check that that multipart/form-data is set and 'file' is being posted");
        }

		$result = array("key"=>"aasss", "dateCreated"=>date('Y-m-d H:i:s'), "dateModified"=>date('Y-m-d H:i:s'), "contentMD5"=>md5("aslas"), "contentType"=>$contentType, "contentSize"=>122, "url" => "http://211.222.222.222/download/a/a7a7a");
		return __success($result);
	}

	public function getObject($bucket, $key) {
		$result = array("key"=>"aasss", "dateCreated"=>date('Y-m-d H:i:s'), "dateModified"=>date('Y-m-d H:i:s'), "contentMD5"=>md5($content), "contentType"=>$contentType, "contentSize"=>122, "url" => "http://211.222.222.222/download/a/a7a7a");
		return __success($result);
	}

    private function isSimpleName($str) {
        return preg_match('/^[a-z0-9.\-\_]+$/i', $str);
    }
}

