<?php


require_once('../libraries/tmhOAuth/app_tokens.php');


require_once('../libraries/phirehose/Phirehose.php');
require_once('../libraries/phirehose/OauthPhirehose.php');

class Consumer extends OauthPhirehose
{
  // A database connection is established at launch and kept open permanently
  public $oDB;
  public function db_connect() {

    require_once('../libraries/db140dev/db_lib.php');
    $this->oDB = new db;
  }

  protected $input_count = 0;

  // This function is called automatically by the Phirehose class
  // when a new tweet is received with the JSON data in $status
  public function enqueueStatus($status) {

    if($this->input_count <1000){

      $tweet_object = json_decode($status);

  		// Ignore tweets without a properly formed tweet id value
      if (!(isset($tweet_object->id_str))) { return;}

      $tweet_id = $tweet_object->id_str;


      //reccor retweeted one if has
      if (isset($tweet_object->retweeted_status)) {
        $is_rt = 1;
      }
      else {
        $is_rt = 0;
      }

      // avoid no geo tweet
      if (!isset($tweet_object->geo)) {
        //extract place geo

        if(!isset($tweet_object->place)){
          return; // leave the function!
        }

        $coordinates = $tweet_object->place->bounding_box->coordinates[0];

        $geo_lon = ($coordinates[0][0] + $coordinates[2][0])*0.5;
        $geo_lat = ($coordinates[0][1] + $coordinates[2][1])*0.5;

      }else{

        $geo_lat = $tweet_object->geo->coordinates[0];
        $geo_lon = $tweet_object->geo->coordinates[1];
      }
      

      $tempname = $tweet_object->user->screen_name;
      // bad users I found
      if (($tempname === "googuns_lulz") 
        || ($tempname === "googuns_prod")
        || ($tempname === "mozatsubot")
        || ($tempname === "MarsBots")
        || ($tempname === "peter2078")
        || ($tempname === "object82")
        || (strpos($tempname, 'tmj_') !== FALSE)){
        return;
      }
    



      // avoid redundant tweet
      // if ($this->oDB->in_table('tweets','tweet_id=' . $tweet_id )) {continue;}

      // extract data
      $tweet_text = $this->oDB->escape($tweet_object->text);  
      $created_at = $this->oDB->date($tweet_object->created_at);

      $user_object = $tweet_object->user;
      $user_id = $user_object->id_str;
      $screen_name = $this->oDB->escape($user_object->screen_name);
      $name = $this->oDB->escape($user_object->name);
      $profile_image_url = $user_object->profile_image_url;

      //write to the two tables
      //Add the new tweet

      $field_values = 'tweet_id = ' . $tweet_id . ', ' .
      'tweet_text = "' . $tweet_text . '", ' .
      'created_at = "' . $created_at . '", ' .
      'geo_lat = ' . $geo_lat . ', ' .
      'geo_long = ' . $geo_lon . ', ' .
      'user_id = ' . $user_id . ', ' .
      'screen_name = "' . $screen_name . '", ' .
      'name = "' . $name . '", ' .
      'profile_image_url = "' . $profile_image_url . '", ' . 
      'is_rt = ' . $is_rt ;

      $this->oDB->insert('tweets',$field_values);

      //test existence!

      if($this->oDB->in_table('tweets','tweet_id=' . $tweet_id ))
      {
        $this->input_count ++;
      }
         
      // Add a new user row or update an existing one
      $field_values = 'screen_name = "' . $screen_name . '", ' .
      'profile_image_url = "' . $profile_image_url . '", ' .
      'user_id = ' . $user_id . ', ' .
      'name = "' . $name . '", ' .
      'location = "' . $this->oDB->escape($user_object->location) . '", ' .
      'url = "' . $user_object->url . '", ' .
      'description = "' . $this->oDB->escape($user_object->description) . '", ' .
      'created_at = "' . $this->oDB->date($user_object->created_at) . '", ' .
      'followers_count = ' . $user_object->followers_count . ', ' .
      'friends_count = ' . $user_object->friends_count . ', ' .
      'statuses_count = ' . $user_object->statuses_count . ', ' .
      'time_zone = "' . $user_object->time_zone . '", ' .
      'last_update = "' . $this->oDB->date($tweet_object->created_at) . '"' ;



      if ($this->oDB->in_table('users','user_id="' . $user_id . '"')) {
        $this->oDB->update('users',$field_values,'user_id = "' .$user_id . '"');
      } else {
        $this->oDB->insert('users',$field_values);
      }



      if($this->input_count >= 1000){

        $end_time = date('Y-m-d H:i:s');
        $field_values = 'end_time = "' . $end_time . '", status_flag = "ended"';


        $result = $this->oDB->select('SELECT max(update_id) FROM update_record');
        $row = mysqli_fetch_assoc($result);
        $update_id = $row['max(update_id)'];

        $where = 'update_id='.$update_id;

        //$where = 'update_id=(SELECT max(update_id) FROM update_record)'; // error: the table to UPDATE is used in FROM

        $this->oDB->update('update_record',$field_values,$where);

      }
    } // if($this->input_count <1000) {
    else{

      $sql = 'SELECT status_flag FROM update_record ORDER BY update_id DESC LIMIT 1'; 
      $result = $this->oDB->select($sql);
      $row = mysqli_fetch_assoc($result);

      if($row['status_flag']==='started')
      {
        $this->input_count = 0;

        // I try to do the following, but the variable is outside the class and the function!!!
        //$stream->oDB->select("truncate users");

        $this->oDB->select("truncate users");  
        $this->oDB->select("truncate tweets");


        
      }
    } // if($this->input_count <1000) else {
  }// end enqueueStatus()
}//end class

// Open a persistent connection to the Twitter streaming API
$stream = new Consumer($user_token, $user_secret, Phirehose::METHOD_FILTER);
//$stream = new Consumer($user_token, $user_secret); // default:: Phirehose::METHOD_SAMPLE
$stream->consumerKey=$consumer_key;
$stream->consumerSecret=$consumer_secret;

// print_r($stream);


// Establish a MySQL database connection
$stream->db_connect();
$stream->oDB->select("truncate users");
$stream->oDB->select("truncate tweets");
  //$stream->oDB->select("truncate json_cache");

// a new record
$start_time = date('Y-m-d H:i:s');
$field_values = 'start_time = "' . $start_time . '", status_flag = "started"';  
$stream->oDB->insert('update_record',$field_values);



// The keywords for tweet collection are entered here as an array
// More keywords can be added as array elements
// For example: array('recipe','food','cook','restaurant','great meal')
//$stream->setTrack(array('food'));

$stream->setLocations(array(array(-180,-90,180,90)));

// Start collecting tweets
// Automatically call enqueueStatus($status) with each tweet's JSON data

date_default_timezone_set("America/New_York");

$stream->consume();

?>