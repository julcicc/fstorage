<?php

$CLIENT_FILE = "/Users/julian/Sites/fstorage/fstorage_client/fstorage_client.php";

require($CLIENT_FILE);


$client = new \FStorage\API_Client("http://localhost/~julian/fstorage/fstorage_server/api.php", "test", "test");
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
$client = new \FStorage\Client("http://localhost/~julian/fstorage/", "test", "test");
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

<h2>Remove Bucket</h2>
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
<? 
$rand_name = "random_" . rand(1, 10000); 
?>
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
<?php
$fname = "/tmp/fstorage-temp.txt";
file_put_contents($fname,"Simple text file");
?>
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

</body>
</html>


