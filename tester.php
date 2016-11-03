<?php

$config = new stdClass();
require dirname(__FILE__).'/v1.0.0/config.php';

if(!empty($_GET['clear_log']) && !empty($config->log_file))
{
	file_put_contents($config->log_file, '');
	header("Location: ".$_SERVER['PHP_SELF']);
}

$requests = [];

foreach ($config->routes as $path => $route)
{
$requests[$path] = '{
	"access_key": ""
}';
}

$requests['/auth/get'] = '{
	"username": "user1",
	"password": "testing"
}';

?>


<style>
* {
	background-color: #333;
	color: #eee;
}
#php_log {
	overflow: auto;
	border: 1px solid #999;
	padding: 5px;
	height: 240px;
}
</style>

<script>
var jsons = [];
<?php foreach ($requests as $req_key => $req_json) { ?>
	jsons["<?php echo $req_key;?>"] = '<?php echo str_replace("\n", "{{nl}}", $req_json);?>';
<?php } ?>

function update_json()
{
	document.getElementById('request').value = jsons[document.getElementById('url').value].split('{{nl}}').join("\n");
}
</script>

<form method="post" style="float:left;width: 40%">

	<select id="url" name="url" onchange="update_json();">
		<?php foreach ($requests as $req_key => $req_json) { ?>
			<option<?php if(!empty($_POST['url']) && $_POST['url'] === $req_key){?> selected="selected"<?php } ?>><?php echo $req_key;?></option>
		<?php } ?>
	</select>
	<br>
	<textarea id="request" name="request" style="padding:10px;width: 100%;height:400px;">
<?php if(!empty($_POST['request'])){ echo $_POST['request']; }else{ echo $requests['/auth/get']; } ?>
	</textarea>
	<br>
	<input type="submit" name="submit" value="Get API Response">

</form>

<div style="float:left; width: 50%; margin-left:5%;">
<?php

if(!empty($_POST['request']))
{

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "http://".$_SERVER['HTTP_HOST'].$_POST['url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt($ch, CURLOPT_POST, TRUE);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST['request']);

	$response = curl_exec($ch);
	curl_close($ch);

	echo '<pre style="white-space:normal;">';print_r($response);echo '</pre>';

	echo '<pre>';print_r(json_decode($response));echo '</pre>';

}
?>
</div>
<br style="clear:both;">
<div style="text-align: right;">
<a href="?clear_log=1">Clear Log</a>
</div>
<div id="php_log">
<pre><?php if(file_exists($config->log_file)){echo file_get_contents($config->log_file);} ?></pre>
</div>
<script>
document.getElementById('php_log').scrollTop = document.getElementById('php_log').scrollHeight;
</script>