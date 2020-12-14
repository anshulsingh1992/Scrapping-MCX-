<?php
/*ini_set("error_reporting", E_ERROR);
ini_set("error_reporting", E_ALL);
ini_set("display_errors", 1);*/

include_once dirname(dirname(__FILE__)) .'/includes/common.php';
//include_once BASE_PATH.'/includes/simple_html_dom.php';
include_once BASE_PATH.'/includes/inc.common.nseit.php';
include_once BASE_PATH.'/includes/MysqliDb.php';

$mysqli = new Mysqlidb (DB_HOST_INT, DB_USER_INT, DB_PASSWORD_INT, DB_NAME_INT);

//$response = $harvest->curlRequest("https://www.bseindia.com/markets/Commodity/PolledSpotPrice.aspx");

$headers = array ( 'Host'  =>  'www.mcxindia.com',
'Connection' =>  'keep-alive',
'Content-Length' =>  '91',
'Accept' =>  'application/json, text/javascript, */*; q=0.01',
'X-Requested-With' =>  'XMLHttpRequest',
'User-Agent' =>  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36',
'Content-Type' =>  'application/json',
'Origin' =>  'https => //www.mcxindia.com',
'Sec-Fetch-Site' =>  'same-origin',
'Sec-Fetch-Mode' =>  'cors',
'Sec-Fetch-Dest' =>  'empty',
'Referer' =>  'https => //www.mcxindia.com/market-data/spot-market-price',
'Accept-Encoding' =>  'gzip, deflate, br',
'Accept-Language' =>  'en-US,en;q=0.9',
'Cookie' =>  '_ga=GA1.2.378012585.1606288114; ASP.NET_SessionId=sbrurscgrc0omadh331vrpwd; _gid=GA1.2.1137896965.1606671229'
);

echo $date = date('Ymd');

$param = array( "Product"  => "GOLD","Location" => "ALL","Fromdate" => $date,"Session" => "0","Todate" => $date ) ;
$param = json_encode($param);
//$response = $harvest->curlRequest("https://www.mcxindia.com/market-data/spot-market-price");
$response = $harvest->curlRequest("https://www.mcxindia.com/backpage.aspx/GetSpotMarketArchive", $param, 1, 1, $headers);

echo "<pre>";

$responseGold = json_decode($response, true);
echo "Gold ==> ";
print_r($responseGold['d']['Data']);
echo "========>";


$headersSil = array(
	'Accept' =>  'application/json, text/javascript, */*; q=0.01',
	'Accept-Encoding' =>  'gzip, deflate, br',
	'Accept-Language' =>  'en-US,en;q=0.9',
	'Connection' =>  'keep-alive',
	'Content-Length' =>  '93',
	'Content-Type' =>  'application/json',
	'Cookie' => '_ga=GA1.2.378012585.1606288114; ASP.NET_SessionId=sbrurscgrc0omadh331vrpwd; _gid=GA1.2.1137896965.1606671229',
	'Host' =>  'www.mcxindia.com',
	'Origin' =>  'https://www.mcxindia.com',
	'Referer' =>  'https://www.mcxindia.com/market-data/spot-market-price',
	'Sec-Fetch-Dest' =>  'empty',
	'Sec-Fetch-Mode' =>  'cors',
	'Sec-Fetch-Site' =>  'same-origin',
	'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36',
	'X-Requested-With' =>  'XMLHttpRequest'
);

$paramSil = array("Product" => "SILVER","Location" => "ALL","Fromdate" => $date,"Session" => "0","Todate" => $date);

$paramSil = json_encode($paramSil);

$responseSil = $harvest->curlRequest("https://www.mcxindia.com/backpage.aspx/GetSpotMarketArchive", $paramSil, 1, 1, $headersSil);

echo "Silver ==> ";

$responseSilver = json_decode($responseSil, true);
print_r($responseSilver['d']['Data']);
//exit;

$dataArray= array();
foreach ($responseGold['d']['Data'] as $key => $value) {
	$dataArray[$value['Symbol']][] =  $value;
}
foreach ($responseSilver['d']['Data'] as $key => $valueSil) {
	$dataArray[$valueSil['Symbol']][] =  $valueSil;
}

print_r($dataArray);

foreach ($dataArray as $key => $mcxArray) {

		foreach ($mcxArray as $mcxkey => $mcxvalue) {
				echo " in : $key => ";
				print_r($mcxvalue);

				$strtime = preg_replace("/[^0-9]/", "", $mcxvalue['Date'] );

				$strtime = $strtime/1000 ;
				$getdate = date('d-m-Y',$strtime);
				$getTime = date('h:i A',$strtime);
				$getHOur =  date('h',$strtime);
				$session = (($getHOur <= 4) ? 1 : 2);

				//check for same date with session value exists or not , update if yes or insert if not.
				$mysqli->where("post_date",strtotime($getdate));
				$mysqli->where("source","mcx");
				$mysqli->where("commodity",$key);
				$mysqli->where("session",$session);
				$mcx_exists = $mysqli->getOne("confab_intext_nseit.nse_spot_prices");
				echo $mysqli->getLastQuery();
				echo "entry";
				print_r($mcx_exists);
				//exit;
				if(isset($mcx_exists) && count($mcx_exists) > 1){
					$mcx_update_data = array(
						'unit' => $mcxvalue['Unit'],
						'price' => $mcxvalue['TodaysSpotPrice'],
						'updated_time' => $getTime,
						'post_time' => strtotime($getdate." ".$getTime),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->where("id",$mcx_exists['id']);
					$mysqli->update("confab_intext_nseit.nse_spot_prices", $mcx_update_data);
					echo $mysqli->getLastQuery();
					echo " Updated Succsessfully for $key with date $getdate. ";
				}else{
					$mcx_data = array(
						'source' => 'mcx',
						'commodity' => $key,
						'unit' => $mcxvalue['Unit'],
						'price' => $mcxvalue['TodaysSpotPrice'],
						'session' => $session,
						'raw_date' => $getdate,
						'updated_time' => $getTime,
						'post_date' => strtotime($getdate),
						'post_time' => strtotime($getdate." ".$getTime),
						'insert_time' => time(),
						'status' => 1,
					);
					$mysqli->setQueryOption("IGNORE");
					$int_mcx_id = $mysqli->insert("confab_intext_nseit.nse_spot_prices",$mcx_data,1);
					echo "<pre>";
					print_r($mcx_data);
				}





			//exit;
		}
}




?>