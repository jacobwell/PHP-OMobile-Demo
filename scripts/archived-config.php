<?php

//table info, used for getting random user local so no need for tunneled ssh rmyql junk
$table = "UFO";
$server = "localhost";
$database = "redacted";
$db_user = "redacted";
$db_pass = 'redacted';

//cec dev keys
$CONSUMER_KEY = 'redacted'; 
$CONSUMER_SECRET = 'redacted';

//text variables (used for converting api responses to readable text and sentences)
$heatArr = array("ELEC" => "Electric Heating", "GAS" => "Gas Heating", "OTHER" => "Other Heating");
$homeArr = array("MULTI_FAMILY" => "Multi-Family Home", "NON_RESIDENTIAL" => "Non-Residential", "SINGLE_FAMILY" => "Single Family Home");
$acArr = array("WINDOW" => "Window System", "CENTRAL" => "Central System");
$spaArr = array("SPA" => "You have a spa.", "SAUNA" => "You have a sauna.");
$poolArr =array("1" => "You have a pool.");
$wordsArr = array("heatType" =>  $heatArr, "pool" => $poolArr, "dwellingType" => $homeArr, "acType" => $acArr, "spa" => $spaArr);
$titleArr = array("heatType" => "Heat", "acType" => "AC", "pool" => "Pool", "numberOfRooms" => "Rooms", "dwellingType" => "Space", "spa" => "Spa", "homePhone" => "Phone", "email" => "Email", "squareFeet" => "Size");

//bill comparision titles
$calArr =  array("Start Date:", "End Date:");
$useArr = array("Total Days:", "Weather:");
$sumsArr = array("Usage:","Charges:");
$cellTitles = array("cal" => $calArr, "use"=>$useArr, "sums" => $sumsArr);
//global vars for total days since have to be set in calMake and then referenced in useMake
$d1 = None;
$d2 = None;

//API Key checks
$heat = array("heatType", 0, 2);
$pool = array("pool", 0, 2);
$home = array("dwellingType", 0, 2);
$ac = array("acType", 0, 2);
$rooms = array("numberOfRooms", 0, 3);
$spa = array("spa",0,2);
$phone = array("homePhone",2,3);
$email = array("email",2,3);
$sqft = array("squareFeet", 0,3);
$name = array("name",2,0);
$address = array("address", 1,0);


//functions

//table printers
function fnPrint($key, $segment) {
	$call = "specialPrint" . $key;
	$call($segment);
}

function intPrint($key, $segment) {
	
}

function arrPrint($key, $segment) {
	global $titleArr;
	global $wordsArr;
	global $responses;	
	//echo "key: $key<br>";
	$type = $responses[$segment]->{$key};
	$text= $wordsArr[$key][$type];
	$title = $titleArr[$key];
	if (isset($type) and $type !== 0 and $type !== "NONE" and $type !== false) {
		echo "<tr><td class='cat'><div class='spritebox' id='";
		echo ($key !== pool) ? $type : "POOL";
		echo "'></div>$title</td><td>$text</td></tr>";
		
	}
}

function stringPrint($key, $segment) {
	//echo "key: $key<br>";
	global $titleArr;
	global $wordsArr;
	global $responses;
	$text = $responses[$segment]->{$key};
	$title = $titleArr[$key];
	$type = strtolower($title);
	if (isset($text) and $text !== '' and $text !== "NONE") {
		if ($key == "squareFeet") {$text .= " square feet";}
		echo "<tr><td class='cat'><div class='spritebox' id='$type'></div>$title</td><td>$text</td></tr>";
	}
}

//special table printers

function specialPrintname($segment) {
	global $titleArr;
	global $wordsArr;
	global $responses;
	$fname = ucwords(strtolower($responses[$segment]->{"firstName"}));
	$lname = ucwords(strtolower($responses[$segment]->{"lastName"}));
	$suffix = ucwords(strtolower($responses[$segment]->{"nameSuffix"}));
	$string = '';
	if (isset($fname) and $fname !== '' and isset($lname) and $lname !== ''){
		$string = $fname . " " . $lname;
		$string .= (isset($suffix)) ? " " . $suffix : '';
	}
	else {
		$string = $fname . $lname;
		$string .= (isset($suffix)) ? " " . $suffix : '';
	}
	if (isset($string) and $string !== "" and strlen($string) > 1 and $string !== "NONE") {
		echo "<tr><td class='cat'><div class='spritebox' id='name'></div>Name</td><td>$string</td></tr>";
	}
}



//need to add line ... fix to turn into two lines
function specialPrintaddress($segment) {
	global $titleArr;
	global $wordsArr;
	global $responses;
	$p = $responses[$segment];
	$initial = $p->{"zipCode"};
	if (isset($initial) and strlen($initial) == 5) {
		echo "<tr><td class='cat'><div class='spritebox' id='address'></div>Address</td><td>";
		$line1 = $p->{"streetName"};
		$line1 = ((isset($p->{"houseNumber"})) ? $p->{"houseNumber"} . " ":'') . ((isset($p->{"preDirection"})) ? $p->{"preDirection"} . ". " :'') . $line1;
		$line1 .= (isset($p->{"postDirection"})) ? " " . $p->{"postDirection"}  : '';
		$line1 .= (isset($p->{"unit"})) ? " " . $p->{"unit"}  : '';
		$line2 = $p->{"zipCode"};
		$line2 = ucwords(strtolower(((isset($p->{"city"})) ? $p->{"city"} . ", ":''))) . ((isset($p->{"state"})) ? $p->{"state"} . " ": '') . $line2;
		$line2 .= (isset($p->{"zipExtension"})) ? "-" . $p->{"zipExtension"}  : '';
		echo ucwords(strtolower($line1 . "<br>")) . $line2;
		echo "</td></tr>";
	}
	
}

//static mock up will be made dynamic soon
function quickLook() {
	global $responses;
	$printContent = "
	<center><table cellspacing='10'><tr>
	<td class='coolcat billPos'><div class='superspritebox'></div>Usage</td>
	<td class='coolcat PlanChange'><div class='superspritebox'></div>Plan</td>
	<td class='coolcat billPos'><div class='superspritebox'></div>Costs</td>
	</tr>
	<td></td>
	<td class='catacombs'>
		<img src='/images/yeltri.png' style='width:35px;'>
	</td>
	</tr>
	</table>
	<div class='tomb'>It looks like you recently changed rate plans.</div>
	</center>
	";
	return $printContent;
}

function normalizeDates($st1,$et1,$st2,$et2) {
	$su1 = strtotime($st1[0]);
	$su2 = strtotime($st2[0]);
	$eu1 = strtotime($et1[0]);
	$eu2 = strtotime($et2[0]);
	$sd1 = date('m/d/y', $su1);
	$sd2 = date('m/d/y', $su2);
	$ed1 = date('m/d/y', $eu1);
	$ed2 = date('m/d/y', $eu2);
	$d1 = round(abs($eu1-$su1)/60/60/24);
	$d2 = round(abs($eu2-$su2)/60/60/24);
	return array($sd1,$sd2,$ed1,$ed2,$d1,$d2);
}

function calMake() {
	global $responses;
	global $d1;
	global $d2;
	$billData = $responses[3];
	$S1 = split("T",$billData[0]->{"startDate"});
	$S2 = split("T",$billData[1]->{"startDate"});
	$E1 = split("T",$billData[0]->{"endDate"});
	$E2 = split("T",$billData[1]->{"endDate"});
	$pieces = normalizeDates($S1,$E1,$S2,$E2);
	$su1 = strtotime($S1[0]);
	$su2 = strtotime($S2[0]);
	$eu1 = strtotime($E1[0]);
	$eu2 = strtotime($E2[0]);
	$sd1 = date('m/d/y', $su1);
	$sd2 = date('m/d/y', $su2);
	$ed1 = date('m/d/y', $eu1);
	$ed2 = date('m/d/y', $eu2);
	$d1 = round(abs($eu1-$su1)/60/60/24);
	$d2 = round(abs($eu2-$su2)/60/60/24);
	return array(array($sd1,$sd2), array($ed1,$ed2));	
}

function useMake() {
	global $responses;
	global $d1;
	global $d2;
	$d1.=" days";
	$d2.=" days";
	$billData = $responses[3];
	$T1 = round($billData[0]->{"averageTemperature"},1);
	$T2 = round($billData[1]->{"averageTemperature"},1);
	if (isset($T1) and isset($T2)) return array(array($d1,$d2),array("Avg. " .$T1."&deg;","Avg. " .$T2."&deg;"));
	else if (isset($T1)) return array(array($d1,$d2),array("Avg. " .$T1."&deg;","?"));
	else if (isset($T2)) return array(array($d1,$d2),array("?","Avg. " .$T2."&deg;"));
	else return array(array($d1,$d2));
}

function sumsMake() {
	global $responses;
	$billData = $responses[3];
	$U1 = round($billData[0]->{"usage"},2);
	$U2 = round($billData[1]->{"usage"},2);
	$C1 = round($billData[0]->{"charges"},2);
	$C2 = round($billData[1]->{"charges"},2);
	$Cc1 = split("\.", $C1);
	$Cc2 = split("\.",$C2);
	if (strlen($Cc1[1]) ==1) $C1.='0';
	if (strlen($Cc2[1]) ==1) $C2.='0';
	return array(array($U1." KWH", $U2 . " KWH"),array("$". $C1, "$" .$C2));
}

function cellMaker($cellIDs) {
	global $cellTitles;
	foreach ($cellIDs as $id) {
		$call = $id . "Make";
		$values = $call();
		printCell(array($id, $cellTitles[$id], $values));
	}
}

function printCell($cell) {
	if (isset($cell[2])) {
		$rows = count($cell[2]);
		$moreVars = ($rows==1) ? 'RTC RBC' : 'RTC';
		echo "
		<table cellspacing='0' display='block'>
			<tr>
				<td class='blackcat LTC LBC R1_2' rowspan='".$rows."'><div class='superspritebox' id='".$cell[0]."'></div></td>
				<td class='bluecat R1'>".$cell[1][0]."</td>
				<td class='bluecat R1'>".$cell[2][0][0]."</td>
				<td class='redcat ". $moreVars ." R1'>".$cell[2][0][1]."</td>
			</tr>";
		if ($rows ==2) {
			echo "<tr class='R2'>
					<td class='bluecat'>".$cell[1][1]."</td>
					<td class='bluecat'>".$cell[2][1][0]."</td>
					<td class='redcat RBC'>".$cell[2][1][1]."</td>
				</tr>";
		echo "</table>";
		echo "<br>";
		}
	}
}


//oauth send request fn
function send_request($http_method, $url, $auth_header=null, $postData=null) {
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FAILONERROR, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  switch($http_method) {
    case 'GET':
      if ($auth_header) {curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header)); }
      break;
    case 'POST':
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml',$auth_header)); 
      curl_setopt($curl, CURLOPT_POST, 1);                                       
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'PUT':
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml',$auth_header)); 
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
      break;
    case 'DELETE':
      curl_setopt($curl, CURLOPT_HTTPHEADER, array($auth_header)); 
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method); 
      break;
  }
  $response = curl_exec($curl);
  if (!$response) {$response = curl_error($curl);}
  curl_close($curl);
  return $response;
}





//large html variables
$head = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01//EN'
'http://www.w3.org/TR/html4/strict.dtd'>
<html>
	<head>
		<title>
			OPOWER Mobile
		</title>
		<link rel='icon' type='image/ico' href='/images/favicon.ico'>
		<meta name='viewport' content='width=device-width, user-scalable=no'>
		<link rel='stylesheet' href='scripts/main.css' type='text/css' media='screen'>
		<script type='text/javascript' src='scripts/jquery.js'></script>
		<script type='text/javascript' src='scripts/functions.js'></script>
	</head>
	<body>
		<div class='menuDownMode' id='title' onclick='menuDown()'>
			<span id='opow'>
				<span id='OP'>OP</span><img id='thePlug' src='images/ologo.png' alt='O' name='thePlug'><span id='WER'>WER </span><span id='onPage'>Mobile</span>
			</span>
			<span id='clickTo'></span>
		</div>
		<div id='tabholder'>
			<table id='tabmenu' cellspacing='0'>
				<tr>
					<td class='notActive' id='1' onclick='tabToggler(1);menuUp(0);'>
						<img src='images/meter.png' value='tab 2'><br>
						Usage
					</td>
					<td id='2' class='notActive' onclick='tabToggler(2);menuUp(1);'>
						<img type='image' src='images/costs.png' value='tab 1'><br>
						Costs
					</td>
					<td id='3' class='notActive' onclick='tabToggler(3);menuUp(2);'>
						<img src='images/bulb.png' value='tab 4'><br>
						Tips
					</td>
					<td id='4' class='active' onclick='tabToggler(4);menuUp(3);'>
						<img src='images/cont.png' value='tab 3'><br>
						Profile
					</td>
				</tr>
			</table>
		</div>
		<div id='major'>
		";

$tail = 	"<br><br><div style='width:95%; text-align:right; '><form action='index.php' method='post' id'theForm' name='theForm'><input type='hidden' name='logOut' value='1'> <a href='#' onclick='document.theForm.submit()'>Logout</a></form></div></div></div></body></html>";


$loginpage = "			<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01//EN'
			'http://www.w3.org/TR/html4/strict.dtd'>
			<html>
				<head>
					<title>
						OPOWER Mobile
					</title>
					<link rel='icon' type='image/ico' href='/images/favicon.ico'>
					<meta name='viewport' content='width=device-width, user-scalable=no'>
					<link rel='stylesheet' href='scripts/main.css' type='text/css' media='screen'>
						<script type='text/javascript' src='functions.js'></script>
				</head>
				<body>
					<div class='menuDownMode' id='title'><span id='opow'>OP<img id='thePlug' src='images/ologo.png' alt='O' name='thePlug'>WER Mobile</span>
					</div>
					<div id='major'>
						<div id='logindiv'>
							<div id='horizon'>
								<div id='content'>
									<form action='index.php' method='post'>
										<input type='text' name='userID' size='20' value='Username' onclick=\"this.value=''\">
										<div class='gap'></div>
										<input disabled type='password' name='password' size='20' value='nullnullnull' onclick=\"this.value=''\"> <a href='forgot.php' style='font-size:12px;display:none;'>Forgot Password</a>
										<div class='gap'></div>
										<input type='checkbox' name='remem' value='1' id='remem'><a style='font-size:11px'>Remember Me</a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										<input type='submit' value='Go'>

								</div>
							</div>
						</div>
					</div>
				</body>
			</html>";


$tippage = "<div class='notActiveTab' id='tab3'>
	<div align='center'>
		<form name='selector' id='selector'>
			<select id='someID' style='font-size:16px; background-color:green' name='someID' onchange='selectForm(this.options[this.selectedIndex].value);hideTime()' class='styled'>
				<option>
					Select a Category
				</option>
				<option value='1'>
					Heating
				</option>
				<option value='2'>
					Cooling
				</option>
				<option value='3'>
					Hot Water
				</option>
				<option value='4'>
					Lighting
				</option>
				<option value='5'>
					Appliances
				</option>
				<option value='6'>
					Others
				</option>
			</select>
		</form>
		<div style='font-size: 12pt'>
			<div id='allForms'>
				<form id='form1' class='aForm' name='form1'>
					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent pretium porta massa. Cras accumsan adipiscing arcu, in fermentum felis luctus nec. Vivamus facilisis tempor augue vel blandit. Nam et nulla at turpis lobortis vestibulum. In dignissim congue lectus, quis cursus ligula condimentum sed. Integer vestibulum facilisis leo, ac elementum dolor egestas non. Morbi odio nibh, vehicula eu rhoncus a, dignissim tempus est. Duis cursus diam id lorem fermentum at dignissim nulla mollis. Morbi dapibus, erat sed fringilla elementum, lacus diam pulvinar nibh, id iaculis ligula eros a libero. Curabitur dictum auctor tellus at semper. Donec tempor risus justo, vel viverra elit. Donec aliquam enim ut nulla vestibulum pellentesque. Donec rutrum dictum nisi, et viverra sem suscipit consequat. Integer posuere ultrices venenatis. Aliquam erat volutpat. Mauris eu nisi nisi. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;
				</form>
				<form id='form2' class='aForm' name='form2'></form>
			</div>
		</div>
	</div>
</div>";

?>