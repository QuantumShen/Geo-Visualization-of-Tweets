<?php
header("Content-Type: application/json;charset=utf-8"); 

define('TOTAL_TWEETS',1);
define('MAX_REQUESTS',5);
define('VER',4);
//echo "Version: ". VER . "\n";



require_once('../libraries/tmhOAuth/app_tokens.php');
require_once('../libraries/tmhOAuth/tmhOAuth.php');
require_once('../libraries/db140dev/db_lib.php');

$twitter_conn = new tmhOAuth(array(
  'consumer_key'    => $consumer_key,
  'consumer_secret' => $consumer_secret,
  'user_token'      => $user_token,
  'user_secret'     => $user_secret
  ));

  //init loop
$input_count = 0;
$request_count = 0;
$search_id = 0;
$tweet_object = (object)null;
$is_rt = 0;
$twitter_error = false;
$result = "";
$result_tweets = '['; // the tweet array string



$search_params = array('q' => $_GET["q"], 'geocode' => $_GET["geo"], 'result_type' => $_GET["result_type"], 'count'=>'100', 'include_entities'=>'false');


// for debug
//$search_params = array('q' => 'drink almond milk', 'include_entities'=>'false');

// //for debug
// $search_params['max_id']   = 715351590543237121; 
// $search_params['since_id'] = 715351590543237120; 

while($input_count<TOTAL_TWEETS) {

  if($request_count>MAX_REQUESTS)
  {
    $twitter_error=true;
    break;
  }
  $twitter_conn->request('GET', $twitter_conn->url('1.1/search/tweets'), $search_params);
  $request_count++;

  // Get the HTTP response code for the API request
  $response_code = $twitter_conn->response['code'];

  // Convert the JSON response into an obj
  $response_data = json_decode($twitter_conn->response['response']);

  // A response code of 200 is a success
  if ($response_code <> 200) {
    $twitter_error = true;
    break; // early results already in database
  }

  
  for($i = 0; $i<100; $i++){

    $tweet_object = $response_data->statuses[$i];


    $search_id = $tweet_object->id; //keep currrent search id, in case object switch to retweeted one

    //use retweeted one if has
    if (isset($tweet_object->retweeted_status)) {
      $tweet_object = $tweet_object->retweeted_status;
      $is_rt = 1;
    }
    else {
      $is_rt = 0;
    }
  
    // avoid no geo tweet
    if (!isset($tweet_object->geo)) {
      //print "Request$request_count $i has no geo info\n";
      continue;
    }
    $tempname = $tweet_object->user->screen_name;
    // bad users I found
    if (($tempname === "googuns_lulz") 
      || ($tempname === "googuns_prod")
      || ($tempname === "mozatsubot")
      || ($tempname === "MarsBots")
      || ($tempname === "peter2078")
      || ($tempname === "object82")
      || (strpos($tempname, 'tmj_') !== FALSE)
      ){
      //print "Request$request_count $i has no geo info\n";
      continue;
    }
    
    $geo_lat = $tweet_object->geo->coordinates[0];
    $geo_lon = $tweet_object->geo->coordinates[1];


    $tweet_id = $tweet_object->id_str;
    $tweet_text = $tweet_object->text;  
    $created_at = $tweet_object->created_at;

    $user_object = $tweet_object->user;
    $user_id = $user_object->id_str;
    $screen_name = $user_object->screen_name;
    $name = $user_object->name;
    $profile_image_url = $user_object->profile_image_url;


    $input_count ++;
    $tweet_text_injson = $tweet_text; //$tweet_object->text;
    $tweet_text_injson = str_replace("\r", ' ', $tweet_text_injson); // single quotes recognize \n as two char
    $tweet_text_injson = str_replace("\n", ' ', $tweet_text_injson);
    $tweet_text_injson = str_replace("\\", '', $tweet_text_injson); // delete all left \
    $tweet_text_injson = str_replace('"', '\\"', $tweet_text_injson);

      

      // $tweet_text_injson = str_replace('’', '\\’', $tweet_text_injson);
      // $tweet_text_injson = str_replace('“', '\\“', $tweet_text_injson);
      // $tweet_text_injson = str_replace('”', '\\”', $tweet_text_injson);


      //Json Format Requirements:
      // " should be escaped, not ',‘,’,“,”
      //Can not add \ to something that needs no escape
      //

    $result_tweets = $result_tweets . 
    '{"tweet_id":"' . $tweet_id . '",' . 
    ' "screen_name": "' . $screen_name . '",' .
    ' "lon":'  . $geo_lon  . ',' .
    ' "lat":'  . $geo_lat  . ',' .
    ' "created_at":"'  . $created_at . '",' .
    ' "tweet_text":"'  . $tweet_text_injson . '",' .
    ' "profile_image_url":"'   . $profile_image_url . '"},' ;



    //Below output will cause json invalid and ajax error
    //Debug: 

    // print "\n";
    // print $tweet_object->text;
    // print "\n";
    // print $tweet_text;
    // print "\n";
    // print $tweet_text_injson; // exist only when it exist in db, ie effective
    // print "\n";

    // // $input_count ++; 
    // print_r($input_count."\n");
    
    //if($input_count>TOTAL_TWEETS+4){break;} // at least TOTAL_TWEETS, at most TOTAL_TWEETS+5 will be returned


  }//end for 

  //last tweet id -1 , as the max id for next search
  $search_params['max_id'] = $search_id - 1;


}//end while 


$result_tweets = trim($result_tweets, ',');
$result_tweets = $result_tweets.']';


if($twitter_error){
  $result = '{"success":false,"msg":"Twitter API requests: '. $request_count . 
  '", "found": '.$input_count.
  ', "tweets": '. $result_tweets. '}';

}
else{
  $result = '{"success":true,"msg":"Twitter API requests: '. $request_count . 
  '", "found": '.$input_count.
  ', "tweets": '. $result_tweets. '}';
}

echo $result;


?>