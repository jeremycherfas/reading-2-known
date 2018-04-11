<!DOCTYPE html>
Test edit
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>PHP tests</title>
	<meta name="generator" content="BBEdit 11.6" />
</head>
<body>
<h1>Working Index</h1>
<?php

/*
*
* This seems to have all the logic and
* could be made to work with the feed content retrieved fresh
* Probably the best place to start again.
* Make sure each bit works separately
*
*/


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$feedUrl = 'https://www.reading.am/jeremycherfas/posts.rss';
$cacheFile = 'RSSCache.txt';

// Set unchanging Known variables
$known['action'] = "/like/edit";
$known['username'] = "Jeremy";
$known['known_api_key'] = "sm8nuepl3qihq0ew";
$known['token'] = base64_encode(hash_hmac('sha256',$known['action'] ,$known['known_api_key'] , true));

// retrieve $old_guid from cachefile
$old_guid = trim(file_get_contents($cacheFile));

// echo "<h1>$old_guid</h1>";

// Retrieve and parse RSS feed
$curl = curl_init($feedUrl);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //result on success FALSE on failure
	$xml = curl_exec($curl);
curl_close($curl);

// Clean up and parse XML feed
$myXMLData = str_replace("&#8217;","'",$xml);
$xml = str_replace("&#160;","",$xml); // Replace ' and nbsp
$xml=simplexml_load_string($myXMLData) or die("Error: Cannot create object"); //Creates JSON?
$json = json_encode($xml); // not an ARRAY
$results = json_decode($json, TRUE); //an array

// We now have the feed in an array

// Clean up the array
$results = $results['channel']; // discards first array
$results = $results['item']; // discards description of feed

// Echo '<h1>$results after cleanup</h1>';
// var_dump($results);

// Find and store GUID of the the most recent item.
$latest_item = array_shift($results); // pops first array element off
$latest_guid = $latest_item['guid']; // gets value for $latest_guid

// Store it in the cacheFile
$handle = fopen($cacheFile, 'w+' );
fwrite($handle, $latest_guid);

// Echo '<h1>New $latest_guid</h1>';
// var_dump($latest_guid);
// Echo '<h2>Into the loop</h2>';

// Put most recent item back
array_unshift($results, $latest_item); // prepends $latest_item Back to $results

// Where in $results is $old_guid
// and slice up to that item
$count = 0;
Foreach ($results as $result) {
	If ($result['guid']===$old_guid) {
		array_splice($results, $count); // Leaves only the new items in $results
	}
$count ++;
}

$results = array_reverse($results); // CHRONO ORDER

// Echo '<h1>$results in chrono order</h1>';
// var_dump($results);

// Now loop through and create the POST for each item
// $myicon = '<img class=\"fas fa-book\"></img>'; not working effectively, perhaps at withknown end

Foreach ($results as $result){

//Get rid of the incoming stuff
$originallink = $result['guid'];
$mylink = preg_replace('@^.+?/p/[^/]+/@', '', $originallink); // Clean link

$mytitle = $result['title'];
$mytitle = str_replace('Jeremy Cherfas is reading ','', $mytitle);
$mytitle = str_replace(' because of Jeremy Cherfas','', $mytitle);
$mytitle = $myicon . $mytitle;

$mydescription = "PESOS from " . "<a href=\"" . $mylink. "\">" . $originallink . "</a>."; // Link to Reading.am

//Create the important part of the curl payload
$mybody = ['bookmark-of' => $mylink, 'title' => $mytitle, 'description' => $mydescription]; // from zegnat, sort of

//Create the curl payload
		$known['body'] = json_encode($mybody, JSON_UNESCAPED_SLASHES);
		$known['headers'] =   array('Accept: application/json',
			'X-KNOWN-USERNAME: ' . $known['username'],
			'X-KNOWN-SIGNATURE: ' .$known['token'],
			'Content-Type: application/json',
			'Content-Length: ' . strlen($known['body']));

//Execute curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/cookiefile");
curl_setopt($ch, CURLOPT_URL, 'https://stream.jeremycherfas.net' . $known['action']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $known['body']);
curl_setopt($ch, CURLOPT_HTTPHEADER, $known['headers']);
curl_exec ($ch);
curl_close ($ch);

// Echo '<h1>$mybody</h1>';
// var_dump($mybody);

}

// retrieve $old_guid from cachefile
$old_guid = file_get_contents($cacheFile);
echo "<h1>new $old_guid </h1>";

?>
</body>
</html>
