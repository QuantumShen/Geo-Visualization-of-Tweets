<?php
header("Content-Type: application/json;charset=utf-8"); 


require_once('../libraries/db140dev/db_lib.php');

$db_conn = new db;


$sql = 'SELECT status_flag, start_time, end_time FROM update_record ORDER BY update_id DESC LIMIT 1';
$result = $db_conn->select($sql);
$row = mysqli_fetch_assoc($result);

if($row['status_flag']==='started')
{
	$response = '{"success":false,	"start_time": "'. $row['start_time']. '", "found": 0}';
}
else {
	
	$end_time = $row['end_time'];
	
	$sql = 'select * from tweets'; //be carefull that input do not need last ';'
	$result = $db_conn->select($sql);
	
	$input_count = 0;
	$result_tweets = '['; // the tweet array string
	
	
	while($row = mysqli_fetch_assoc($result)) {
	    $tweet_id = $row['tweet_id'];
	    $tweet_text = $row['tweet_text'];
	    $created_at = $row['created_at'];
	    $geo_lat = $row['geo_lat'];
	    $geo_lon = $row['geo_long'];
	    $user_id = $row['user_id'];
	    $screen_name = $row['screen_name'];
	    $name = $row['name'];
	    $profile_image_url = $row['profile_image_url'];
	    $is_rt = $row['is_rt'];
	
	
	    $tweet_text_injson = $tweet_text; //$tweet_object->text;
	    $tweet_text_injson = str_replace("\r", ' ', $tweet_text_injson); // single quotes recognize \n as two char
	    $tweet_text_injson = str_replace("\n", ' ', $tweet_text_injson);
	    $tweet_text_injson = str_replace("\\", '', $tweet_text_injson); // delete all left \
	    $tweet_text_injson = str_replace('"', '\\"', $tweet_text_injson);
	
	    $result_tweets = $result_tweets . 
	    '{"tweet_id":"' . $tweet_id . '",' . 
	    ' "screen_name": "' . $screen_name . '",' .
	    ' "lon":'  . $geo_lon  . ',' .
	    ' "lat":'  . $geo_lat  . ',' .
	    ' "created_at":"'  . $created_at . '",' .
	    ' "tweet_text":"'  . $tweet_text_injson . '",' .
	    ' "profile_image_url":"'   . $profile_image_url . '"},' ;
	
	    $input_count ++;
	}
	
	
	$result_tweets = trim($result_tweets, ',');
	$result_tweets = $result_tweets.']';
	
	$response = '{"success":true,"end_time": "'. $end_time.
	  '", "found": '.$input_count.', "tweets": '. $result_tweets. '}';
}

$db_conn->close();

echo $response;


?>