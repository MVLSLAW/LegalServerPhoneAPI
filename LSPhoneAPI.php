<?php
/**
* Legal Server Phone API
* 
* @copyright  Copyright (c) 2017 Maryland Volunteer Lawyers Service
* @version    Release: 1.1
* @Author Matthew Stubenberg
* @Last Updated January 31, 2017
*
* Basic usage
* $phonegetter = new LSPhoneAPI('LSUsername','LSPassword','OrgSubdomain');
* try{ 
* 	print_r($phonegetter->searchPhoneNumber('3018675309'));
*	print_r($phonegetter->getMatter(01234567));
* }catch(Exception $e){
*	echo "Error: " . $e->getMessage();
* }
*/
class LSPhoneAPI{
	
	/************SSL Variable******************/
	//If you are on a localhost and getting "SSL certificate problem: unable to get local issuer certificate" error, for testing purposes you can set this to false.
	public $verifypeerSSL = true;
	//Or you can install a SSL cert on your localhost by following the link in the readme.
	/************SSL Variable******************/
	private $username;
	private $password;
	private $orgname;
	public $phonenumberarray;
	public $numericarray;
	public $matterarray;
	public $rawphoneapireturn;
	
	public function __construct($username,$password,$orgname){
		//Sets the Legal Server username,password and the org name. 
		//Note the orgname should be whatever the LegalServer subdomain is xxxx.legalserver.org
		$this->username = $username;
		$this->password = $password;
		$this->orgname = $orgname;
	}
	public function searchPhoneNumber($phonenumber){
		//Public function to search the phone number.
		
		//See if the phonenumber is an array or a single phonenumber
		if(is_array($phonenumber)){
			$this->phonenumberarray = $phonenumber;
		} else{
			//Means the phone number is just 1 so we need to make it into an array;
			$this->phonenumberarray[0] = $phonenumber;
		}
		foreach($this->phonenumberarray as $phonenum){
			$cleanedupnumber = $this->cleanUpNumber($phonenum);
			$url = $this->constructURL($cleanedupnumber);
			$tempreturnarray = $this->sendCurl($url);
			$this->rawphoneapireturn = $tempreturnarray;
			
			if(!isset($tempreturnarray))  throw new Exception("There was an error in the curl process");
			if($tempreturnarray['error'] != false) throw new Exception("There was an error in getting the phone number.-" . $tempreturnarray['error_text']);			
			if(count($tempreturnarray['results']) == 0) return false; //No matters were found.
			
			foreach($tempreturnarray['results'] as $case){
				//Saves the results to the numericarray.
				
				$exists = false;
				for($x=0;$x<count($this->numericarray);$x++){
					//Checks to see if this case is already part of the raw return array.
					if($this->numericarray[$x]['id'] == $case['id']){
						$exists = true;
						break;
					}					
				}
				if($exists == false) $this->numericarray[] = $case;
				$this->matterarray[$case['id']['text_value']] = $case;
			}
		}
		
		return $this->numericarray;
	}
	
	public function getMatter($matterid){
		if(isset($this->matterarray[$matterid])){
			return $this->matterarray[$matterid];
		}else{
			return false;
		}
	}
	public function cleanUpNumber($phonenumber){
		//This cleans up the phone number by removing non numeric characters and spaces.
		$newnumber = '';
		for($x=0;$x<strlen($phonenumber);$x++){
			if(is_numeric($phonenumber[$x])) $newnumber .= $phonenumber[$x];
		}
		return $newnumber;
	}
	private function constructURL($phonenumber){
		//Creates the URL to look like the one below
		//https://USERNAME:PASSWORD@FIRM.legalserver.org/matter/api/caller_id_search/?number=PHONENUMBER
		$url = "https://" . $this->username . ":" . $this->password . "@" . $this->orgname . ".legalserver.org/matter/api/caller_id_search/?number=" . $phonenumber;
		return $url;
	}
	public function sendCurl($request){
		$decoderesponse = array();
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_FAILONERROR=>false,
			CURLOPT_SSL_VERIFYPEER=>$this->verifypeerSSL,
			CURLOPT_SSL_VERIFYHOST=>2,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_TIMEOUT=>15,
			CURLOPT_URL => $request));
		$resp = curl_exec($curl);
		//echo $resp;
		if($resp === false){
			$decoderesponse['error'] = true;
			$decoderesponse['error_text'] = curl_error($curl); //Means there was an error with the curl.
		}else{
			$decoderesponse = json_decode($resp, true); //Since I only deal with JSON returns I go ahead and convert from JSON to a regular PHP array.
		}		
		curl_close($curl); 
		return $decoderesponse;
	}
	public function getMatterArray(){
		return $this->matterarray;
	}
	public function getNumericArray(){
		return $this->numericarray;
	}
}