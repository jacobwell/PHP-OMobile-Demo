<?php
ob_start();
require_once('scripts/OAuth.php');
//all functions and variables
require_once("scripts/archived-config.php");


//random user selector
$id = rand(1,438000);
mysql_connect($server, $db_user, $db_pass) or die (mysql_error()); 
$result = mysql_db_query($database, "select * from $table WHERE id=$id") or die (mysql_error()); 

   while ($qry = mysql_fetch_array($result)) { 
		$uid = $qry['uid'];
		$cid = $qry['cid'];
	}
	
//API URL elements
//need to deal with aid cid uid junk
//$cs =  "customers?accountNumber=" . $aid;
$us = 'utility_accounts/' . $uid . '/site';
$bc = 'utility_accounts/' . $uid . '/bill_comparison';
$base = 'https://api-dev.redacted.com/v1/cec/';
$base_feed = $base . $cs;
	
//Login checking
if (isset($_POST['logOut'])) {
	setcookie("loggedin", "TRUE",time()-(3600 * 24), "/",".mbp.local" );
	die("<meta HTTP-EQUIV='REFRESH' content='0; url=index.php'>Reloading...");
}
else if (isset($_POST['userID'])) {
	$box = $_POST['remem'];
	if ($box) {
		setcookie("loggedin", "".$_POST['userID']."", time()+(3600 * 24 * 7), "/", ".mbp.local");
	}
	else {
		setcookie("loggedin", "".$_POST['userID']."", 0, "/", ".mbp.local");
	}
	die("<meta HTTP-EQUIV='REFRESH' content='0; url=index.php'>Reloading...");
}

//if (!isset($_COOKIE['loggedin'])) die($loginpage);
$username = $_COOKIE['loggedin'];
//end of login verification

//querying information

//keys generation
$consumer = new OAuthConsumer($CONSUMER_KEY, $CONSUMER_SECRET, NULL);
$consumer2 = new OAuthConsumer($CONSUMER_KEY, $CONSUMER_SECRET, NULL);
$consumer3 = new OAuthConsumer($CONSUMER_KEY, $CONSUMER_SECRET, NULL);
$consumer4 = new OAuthConsumer($CONSUMER_KEY, $CONSUMER_SECRET, NULL);

//query customers for cID
$request = OAuthRequest::from_consumer_and_token($consumer, NULL, 'GET', $base_feed, NULL);
$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
$returned = send_request($request->get_normalized_http_method(), $base_feed, $request->to_header());
$obj = json_decode($returned);
$obj2 = $obj->{"response"};
//$newCID=$obj2[0]->{"id"};
//cheating for cid since api was beinging buggy
$newCID = $cid;
//end of query
//query site and contact for user information
$base_feed2 = $base . "customers/" . $newCID.'/contact'; 
$base_feed3 = $base . $us;
$base_feed4 = $base . $bc;
$request2 = OAuthRequest::from_consumer_and_token($consumer2, NULL, 'GET', $base_feed2, NULL);
$request2->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer2, NULL);
$returned2 = send_request($request2->get_normalized_http_method(), $base_feed2, $request2->to_header());
$request3 = OAuthRequest::from_consumer_and_token($consumer3, NULL, 'GET', $base_feed3, NULL);
$request3->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer3, NULL);
$returned3 = send_request($request3->get_normalized_http_method(), $base_feed3, $request3->to_header());
$request4 = OAuthRequest::from_consumer_and_token($consumer4, NULL, 'GET', $base_feed4, NULL);
$request4->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer4, NULL);
$returned4 = send_request($request4->get_normalized_http_method(), $base_feed4, $request4->to_header());
//end of querying

//query bill comparison for info
//end of query
//table constructor items takes API responses and adds them to $responses array for use later
$contact = json_decode($returned2);
$contactR = $contact->{"response"};
$site = json_decode($returned3);
$siteR = $site->{"response"};
$parcel = $siteR->{"parcel"};
$addressS = $siteR->{"address"};
$billC = json_decode($returned4);
$billCR = $billC->{"response"};
$billCErr = $billCR->{"error"};
$billCRef = $billCR->{"reference"};
$billCComp = $billCR->{"compared"};
$billCRes = $billCR->{"analysisResults"};
$billInfo = array($billCRef,$billCComp,$billCRes);

//need to add other queries to here
$responses = array($parcel, $addressS, $contactR,$billInfo);

//which fields to try to print for profile info
$printKeys = array($name, $phone, $email, $address, $home, $sqft, $heat, $ac, $pool, $spa, $rooms);

//which fields to try to print for bill comparison
$printCells = array("cal", "use", "sums");


//check if recieved valid responses from queries
$gotaccount = (isset($addressS->{"zipCode"})) ? true : false;
//not setup yet
$gotusage = 0;
$gotcosts = (!isset($billCErr) and isset($billCRef->{"charges"})) ? true : false;


//begin printing page

//hidden meta paramaters for testing
if ($_GET["testMode"]=="thequickbrownfoxomg98838459758575984794") {
	echo "<meta name='testCID' content='$cid' />\n\n<meta name='testUID' content='$uid' />\n\n";
}

//print header
echo $head . "\n\n";
echo "<div class='notActiveTab' id='tab1'>\n\n";


echo $returned . "<br>" . $returned2. "<br>" . $returned3 . "<br><br>". $returned4 . "<br><br>"; //echo returned jsons (for debugging)

//print each tab's info if queries were sucessful
if ($gotusage) {}
else {echo "No information was found.";}
echo "</div>\n\n<div class='notActiveTab' id='tab2'>\n\n";				
if ($gotcosts) {
	echo quickLook();
	cellMaker($printCells);
}
else {echo "No information was found.";}
echo "</div>\n\n";
//print tab 3 the tips page, currently in theory will be static content
//ideally would be dynamic, currently not even set just a place holder
echo $tippage;
echo "</div>\n\n<div class='activeTab' id='tab4'>\n\n";
if ($gotaccount) {
	echo "<table id='info'>\n\n";
	foreach ($printKeys as $query) {
		if ($query[2]==0) fnPrint($query[0], $query[1]);
		if ($query[2]==1) intPrint($query[0], $query[1]);
		if ($query[2]==2) arrPrint($query[0], $query[1]);
		if ($query[2]==3) stringPrint($query[0], $query[1]);
		echo "\n\n";
	}
	echo "</table>\n\n";
}
else {echo "No information was found.";}
echo "</div>";

//echo end of html			
echo $tail;



ob_end_flush();
?>