<?php

$CLIENT_FILE = dirname(__FILE__) . "/../fstorage_client/fstorage_client.php";
$SERVER = "http://localhost/~julian/fstorage/fstorage_server/api.php"; //http://fstorage.julian.dev/fstorage_server/api.php";
$USER = "test";
$PASS = "test";
$REMOVE_OBJECTS = false;

require($CLIENT_FILE);

$client = new \FStorage\API_Client($SERVER,$USER,$PASS);

//create test stuff
$fname = "/tmp/fstorage-temp.txt";
file_put_contents($fname,"Simple text file");

//big file
$bigFile = "/tmp/fstorage-temp-big.bin";
$cmd = "/bin/dd of=$bigFile bs=50m count=1 if=/dev/random";
//echo "=== $cmd === ";
system($cmd);
$bigMD5 = md5_file($bigFile);

$rand_name = "random_" . rand(1, 10000); 

$client->removeBucket("dummy");
$client->createBucket("dummy", "Test Bucket");

?>
<html>
<style>
html {
font-family:Sans-Serif;
}

</style>
<body>
<h1>FStorage Client Tests</h1>
<code>
$client = new \FStorage\Client("<?=$SERVER?>", "<?=$USER?>", "<?=$PASS?>");
</code>

<h2>Test Connection (NOOP)</h2>
<code>
$client-&gt;noop();
</code>
<div class="result">
<pre>
<?php
var_dump($client->noop());
?>
</pre>
</div>

<h2>List Buckets</h2>
<code>
$client-&gt;listBuckets();
</code>
<div class="result">
<pre>
<?php
var_dump($client->listBuckets());
?>
</pre>
</div>

<h2>Create Bucket (OK)</h2>
<code>
$client-&gt;createBucket("test_bucket", "My Test Bucket");
</code>
<div class="result">
<pre>
<?php
var_dump($client->createBucket("test_bucket", "My Test Bucket"));
?>
</pre>
</div>

<h2>Create Bucket (Duplicate Error)</h2>
<code>
$client-&gt;createBucket("test_bucket", "My Test Bucket");
</code>
<div class="result">
<pre>
<?php
var_dump($client->createBucket("test_bucket", "My Test Bucket"));
?>
</pre>
</div>

<h2>Create Bucket (Bad Name)</h2>
<code>
$client-&gt;createBucket("my test * bucket", "My * Test Bucket");
</code>
<div class="result">
<pre>
<?php
var_dump($client->createBucket("my test * bucket", "My * Test Bucket"));
?>
</pre>
</div>

<?php if($REMOVE_OBJECTS) {?>
<h2>Remove Bucket (OK)</h2>
<code>
$client-&gt;removeBucket("test_bucket");
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeBucket("test_bucket"));
?>
</pre>
</div>

<h2>Remove Bucket (not exists)</h2>
<code>
$client-&gt;removeBucket("<?=$rand_name?>");
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeBucket($rand_name));
?>
</pre>
</div>
<?}?>

<h2>Put Object (simple)</h2>
<code>
$client-&gt;putObject("dummy", "path/to/my/file", "application/json", "{foo:'bar'}");
</code>
<div class="result">
<pre>
<?php
var_dump($client->putObject("dummy", "path/to/my/file", "application/json", "{foo:'bar'}"));
?>
</pre>
</div>

<h2>Upload Object (error)</h2>
<code>
$client-&gt;uploadObject("dummy", "path/to/my/file", "application/octet-stream", "/path/to/none/file");
</code>
<div class="result">
<pre>
<?php
var_dump($client->uploadObject("dummy", "path/to/my/file", "application/octet-stream", "/path/to/none/file"));
?>
</pre>
</div>

<h2>Upload Object (simple file)</h2>
<code>
$client-&gt;uploadObject("dummy", "/tmp/dummy.txt", "text/plain", "<?=$fname?>");
</code>
<div class="result">
<pre>
<?php
var_dump($client->uploadObject("dummy", "/tmp/dummy.txt", "text/plain", $fname));
?>
</pre>
</div>

<h2>Upload Object (big file <?=$bigFile?>. Original MD5 <?=$bigMD5?>)</h2>
<code>
$client-&gt;uploadObject("dummy", "/tmp/dummy.bin", "application/octet-stream", "<?=$bigFile?>");
</code>
<div class="result">
<pre>
<?php
var_dump($client->uploadObject("dummy", "/tmp/dummy.bin", "application/octet-stream", $bigFile));
@unlink($bigFile);
?>
</pre>
</div>

<h2>List Objects</h2>
<code>
$client-&gt;listObjects("dummy", "*");
</code>
<div class="result">
<pre>
<?php
var_dump($client->listObjects("dummy", "*"));
?>
</pre>
</div>

<?php if ($REMOVE_OBJECTS){ ?>
<h2>Remove Bucket (ERROR)</h2>
<code>
$client-&gt;removeBucket("dummy");
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeBucket("dummy"));
?>
</pre>
</div>

<h2>Remove Objects (OK)</h2>
<code>
$client-&gt;removeObject("dummy", "path/to/my/file");<br>
$client-&gt;removeObject("dummy", "/tmp/dummy.txt");<br>
$client-&gt;removeObject("dummy", "/tmp/dummy.bin");<br>
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeObject("dummy", "path/to/my/file"));
var_dump($client->removeObject("dummy", "/tmp/dummy.txt"));
var_dump($client->removeObject("dummy", "/tmp/dummy.bin"));
?>
</pre>
</div>

<h2>Remove Object (ERROR)</h2>
<code>
$client-&gt;removeObject("dummy", "<?=$rand_name?>");
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeObject("dummy", $rand_name));
?>
</pre>
</div>

<h2>Prune bucket</h2>
<code>
$client-&gt;pruneBucket("dummy");
</code>
<div class="result">
<pre>
<?php
var_dump($client->pruneBucket("dummy"));
?>
</pre>
</div>

<h2>Remove Bucket (OK)</h2>
<code>
$client-&gt;removeBucket("dummy");
</code>
<div class="result">
<pre>
<?php
var_dump($client->removeBucket("dummy"));
?>
</pre>
</div>
<?}?>

<h2>Download Object (OK)</h2>
<code>
$client-&gt;downloadObject("dummy","/tmp/dummy.txt");
</code>
<div class="result">
<a href="dl_test.php?SERVER=<?=urlencode($SERVER)?>&USER=<?=urlencode($USER)?>&PASS=<?=urlencode($PASS)?>&bucket=<?=urlencode("dummy")?>&key=<?=urlencode("/tmp/dummy.txt")?>">Try</a>
</div>

<h2>Save Object( OK)</h2>
<code>
$client-&gt;saveObject("dummy","/tmp/dummy.txt", "/tmp/save.test");
</code>
<div class="result">
<pre>
<?php
var_dump($client->saveObject("dummy", "/tmp/dummy.txt", "/tmp/save.test"));
?>
</pre>
</div>
</body>
</html>


