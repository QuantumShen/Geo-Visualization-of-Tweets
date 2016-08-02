<?php
header("Content-Type: application/json;charset=utf-8"); 

require_once('../libraries/db140dev/db_lib.php');

// how to check the updating process is running in the background
// Read 3 characters starting from the 1st character
//$status = file_get_contents('./stream_proc_status.txt', NULL, NULL, 0,3);

// //if($status === 'AAA'){
$db_conn = new db;

// // read lock
// // write lock

// $lock_sql = "LOCK TABLES users READ, users AS users_write WRITE, tweets READ, tweets AS tweets_write WRITE";
// $db_conn->select($lock_sql);

// $db_conn->select("truncate users");    =====> decided to truncate in get_tweets_stream.php
// $db_conn->select("truncate tweets");

// $db_conn->select("UNLOCK TABLES");

// // // write unlock
// // read unlock

$sql = 'SELECT status_flag, start_time FROM update_record ORDER BY update_id DESC LIMIT 1';
$result = $db_conn->select($sql);
$row = mysqli_fetch_assoc($result);

if($row['status_flag']==='started')
{
	$start_time = $row['start_time'];
	$response = '{"success":false,"start_time": "'. $start_time . '"}';
	
}
else{
	
	date_default_timezone_set("America/New_York");
	
	$start_time = date('Y-m-d H:i:s');
	$field_values = 'start_time = "' . $start_time . '", status_flag = "started"';  
	
	// the id field with be incremented automatically
	$db_conn->insert('update_record',$field_values);

	$response = '{"success":true,"start_time": "'. $start_time . '"}';
}



$db_conn->close();
echo $response;


?>