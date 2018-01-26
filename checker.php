<?php
function doCurl($url,$isPost=false,$postData=null,$header=null,$customRequest=null,$http_code=false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if(isset($postData) curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    if(isset($isPost)) curl_setopt($ch, CURLOPT_POST, 1);
    if(isset($header)) curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    if(isset($customRequest)) curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
    $output = curl_exec ($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close ($ch);
    if($http_code){
        return $httpcode;
    }
    else{
        return $output;
    }
}

function queryStatus($key,$username,$tid){
    $url="https://app.statuscake.com/API/Alerts/?";
    if(isset($tid)) $url.= 'TestID='.$tid.'&';
    $res = doCurl($url,false,null,array(
            'API: '.$key,
            'Username: '.$username
        ));
    $res_arr = json_decode($res,true);
    if(isset($res_arr[0])){
        return $res_arr[0]['Status'];
    }
    return null;
}

function queryRecords($key,$aid,$zone,$recordId){
    $res = doCurl('https://api.dnsimple.com/v2/'.$aid.'/zones/'.$zone.'/records/'.$recordId,false,null,array(
            'Authorization: Bearer '.$key,
            'Accept: application/json'
        ));
    $res_arr = json_decode($res,true);
    if(isset($res_arr['data'])){
        return $res_arr['data']['content'];
    }
    return null;
}

function updateRecords($key,$aid,$zone,$recordId,$name,$content,$ttl=60){
    $res = doCurl('https://api.dnsimple.com/v2/'.$aid.'/zones/'.$zone.'/records/'.$recordId,false,json_encode(array(
            'name' => $name,
            'content' => $content,
            'ttl' => $ttl
        )),array(
            'Authorization: Bearer '.$key,
            'Accept: application/json',
            'Content-Type: application/json'
        ),'PATCH',true);
    return $res == "200";
}

define('STATUSCAKE',getenv('StatusCakeAPI'));
define('DNSIMPLE',getenv('DNSimpleAPI'));
define('USERNAME','milkice');
define('TESTID',2503968);
define('ACCOUNTID',71603);
define('DOMAINZONE','milkice.me');

function logger($mark,$text){
    echo('['.$mark.'] '.$text."\n");
}

$status = queryStatus(STATUSCAKE,USERNAME,TESTID);
if(!isset($status)){
    logger('!','Empty Status. Exiting...');
    return;
}
if($status == 'Up'){
    logger('+','Website Up. Existing...');
    return;
}

$currentRecord = queryRecords(DNSIMPLE,ACCOUNTID,DOMAINZONE,13356972);
$currentSwitchDate = queryRecords(DNSIMPLE,ACCOUNTID,DOMAINZONE,13357390);
if(!isset($currentRecord) || !isset($currentSwitchDate)){
    logger('!','Empty Record or Date. Exiting...');
    return;
}

$timestampDate = intval((time() + 8*60*60)/(60*60*24));
logger('*','The timestamp today is '.$timestampDate);

if($currentRecord == 'milkice.me.cdn.cloudflare.net' && $currentSwitchDate == $timestampDate){
    logger('!','Website still down');
    return;
}
else if($currentRecord == 'actual.milkice.me' && $currentSwitchDate != $timestampDate){
    logger('*','Detect website down at the first time. Switching...');
    updateRecords(DNSIMPLE,ACCOUNTID,DOMAINZONE,13356972,'','milkice.me.cdn.cloudflare.net',60);
    updateRecords(DNSIMPLE,ACCOUNTID,DOMAINZONE,13357390,'switch_date',$timestampDate,60);
}
else if($currentRecord == 'milkice.me.cdn.cloudflare.net' && $currentSwitchDate != $timestampDate){
    logger('*','Try recovering on next day...');
    updateRecords(DNSIMPLE,ACCOUNTID,DOMAINZONE,13356972,'','actual.milkice.me',60);
}
?>
