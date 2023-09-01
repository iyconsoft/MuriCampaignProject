<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\MenuSession;
use App\Models\UssdUser;
use DB;

class UssdController extends Controller
{
	
	public $newLine = "\n";
	
    public function index(Request $request)
	{
		$msisdn = substr($request->msisdn,-13);
		$message = strtoupper($request->message);
		$shortcode = $request->shortcode;
		$gateway = $request->gateway;
		 
		$menuSession = MenuSession::Where('msisdn',$msisdn)->First();
		if(!$menuSession || $message == "*8011*23#" || $message == "8011*23" || $message == "*8011*23")
		{
			
			DB::delete("delete from menu_session where msisdn='".$msisdn."'");
			$menuSession = new MenuSession;
			
			$menuSession->msisdn = $msisdn;
			$menuSession->keyword = $message;
			$menuSession->level = 1;
			$menuSession->created_at = date('Y-m-d H:i:s');
			$menuSession->save();
			
			$Message = "Muri support Organization".$this->newLine."Please enter your name";
			
			$output['operation'] = "continue";		
			$output['message'] = $Message;
			
			return $output;
		}
		else
		{
			switch($menuSession->level)
			{
				case '1':
					
					$Message = "Please enter your Local Government Area";
					
					
					$output['operation'] = "continue";		
					$output['message'] = $Message;
					
					$menuSession->name = $message;
					$menuSession->keyword = $message;
					$menuSession->level = 2;
					$menuSession->save();
				break;
				
				case '2':
					
					$Message = "Please Enter a problem in your community you would want MURI to address if he wins".$this->newLine."1. Road".$this->newLine."2. Water".$this->newLine."3. Health Care".$this->newLine."4. Education".$this->newLine."5. Electricity".$this->newLine."6. Security".$this->newLine."7. Others".$this->newLine;
					
					
					$output['operation'] = "continue";		
					$output['message'] = $Message;
					
					$menuSession->local_area = $message;
					$menuSession->keyword = $message;
					$menuSession->level = 3;
					$menuSession->save();
				break;
				
				case '3':
					
					$problem = 'Others';
					switch($message)
					{
						case '1':
							$problem = 'Road';
						break;
						case '2':
							$problem = 'Water';
						break;
						case '3':
							$problem = 'Health Care';
						break;
						case '4':
							$problem = 'Education';
						break;
						case '5':
							$problem = 'Electricity';
						break;
						case '6':
							$problem = 'Security';
						break;
						case '7':
							$problem = 'Others';
						break;
					}

					
					$Message = "Select Project that requires Urgent Government Attention".$this->newLine."1. Flood Hazard".$this->newLine."2. Health Care".$this->newLine."3. Education".$this->newLine."4. Electricity".$this->newLine."5. Security".$this->newLine."6. Food Security".$this->newLine."7. Others";
					
					
					$output['operation'] = "continue";		
					$output['message'] = $Message;
					
					$menuSession->problem = $problem;
					$menuSession->keyword = $message;
					$menuSession->level = 5;
					$menuSession->save();
				break;
				
				case '5':
					
					$priorty_project = 'Others';
					switch($message)
					{
						case '1':
							$priorty_project = 'Flood Hazard';
						break;
						case '2':
							$priorty_project = 'Health Care';
						break;
						case '3':
							$priorty_project = 'Education';
						break;
						case '4':
							$priorty_project = 'Electricity';
						break;
						case '5':
							$priorty_project = 'Security';
						break;
						case '6':
							$priorty_project = 'Food Security';
						break;
						case '7':
							$priorty_project = 'Others';
						break;
					}
					
					$Message = "Hon. Muri will personally acknowledge your support".$this->newLine."Select Amount".$this->newLine."1. 200".$this->newLine."2. 300".$this->newLine."3. 400".$this->newLine."4. 500".$this->newLine."5. 1000";
					
					$output['operation'] = "continue";		
					$output['message'] = $Message;
					
					$menuSession->priorty_project = $priorty_project;
					$menuSession->keyword = $message;
					$menuSession->level = 6;
					$menuSession->save();
				break;
				
				case '6':
					
					$amount = 200;
					switch($message)
					{
						case '1':
							$amount = 200;
						break;
						case '2':
							$amount = 300;
						break;
						case '3':
							$amount = 400;
						break;
						case '4':
							$amount = 500;
						break;
						case '5':
							$amount = 1000;
						break;
					}
					
					
					
					$ussdUser = new UssdUser;
					$ussdUser->msisdn = $msisdn;
					$ussdUser->name = $menuSession->name;
					$ussdUser->local_area = $menuSession->local_area;
					$ussdUser->problem = $menuSession->problem;
					$ussdUser->priorty_project = $menuSession->priorty_project;
					$ussdUser->amount = $amount;
					$ussdUser->payment_reference = '';
					$ussdUser->is_paid = '0';
					$ussdUser->created_at = date('Y-m-d H:i:s');
					$ussdUser->save();
					
					$menuSession->Delete();
					
					$MonifyAccount = $this->createMonifyAccount($ussdUser);
					
					$account_no = "";
					$bankName = "";
					if($MonifyAccount != "")
					{
						$account_no = $MonifyAccount['responseBody']['accountNumber'];
						$bankName = $MonifyAccount['responseBody']['bankName'];
					}
					$Message = "Please, Pay #".$amount." in ".$bankName." Account: ".$account_no." (SDP-Murtala Yakubu Ajaka Gubernatorial Campaign Council) via transfer, POS, or ATM";
					
					$output['operation'] = "end";		
					$output['message'] = $Message;
					
					if(env('APP_ENV')=="production" && 1==0)
					{
						\Log::info("Send SMS: http://3.131.19.214:8802/?phonenumber=234".substr($msisdn,-10)."&text=".urlencode($Message)."&sender=SELFSERVE&user=selfserve&password=1234567891");
						file_get_contents("http://3.131.19.214:8802/?phonenumber=234".substr($msisdn,-10)."&text=".urlencode($Message)."&sender=SELFSERVE&user=selfserve&password=1234567891");
					}	
				break;
			}
		}
		return $output;
	}
	 
	function createMonifyAccount($ussdUser)
	{
		$url = "https://api.monnify.com/api/v1/invoice/create";
		
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_393441595619", "feePercentage"=> 0, "splitPercentage"=> 74.62, "feeBearer"=> false);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_422146413134", "feePercentage"=> 0, "splitPercentage"=> 4.93, "feeBearer"=> false);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_794774544824", "feePercentage"=> 0, "splitPercentage"=> 6.25, "feeBearer"=> false);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_774412756725", "feePercentage"=> 100, "splitPercentage"=> 6.25, "feeBearer"=> true);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_884513776734", "feePercentage"=> 0, "splitPercentage"=> 1.25, "feeBearer"=> false);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_820139155113", "feePercentage"=> 0, "splitPercentage"=> 5, "feeBearer"=> false);
		$incomeSplitconfig[] = array ("subAccountCode" => "MFY_SUB_184402091673", "feePercentage"=> 0, "splitPercentage"=> 1.7, "feeBearer"=> false);

		$username = 'MK_PROD_QAC28QUESH';
		$password = 'QNZXRQPRRZ4ATFWQZYBEE2QUU7QXRF3G';
		$contractCode = "768651769665";
		
		$expiryDate = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +20480 minutes"));
		$uniqeID = uniqid();
		
		$ussdUser->payment_reference = $uniqeID;
		$ussdUser->Save();
		
		$data = json_encode(array
				(
				  "amount" => $ussdUser->amount,
				  "customerName" => $ussdUser->name,
				  "customerEmail" => 'ikechukwu.kalu@iyconsoft.com',
				  "invoiceReference" => $uniqeID,
				  "paymentDescription" => "Muri Support Organisation",
				  "currencyCode" => "NGN",
				  "contractCode" => $contractCode,
				  "redirectUrl" => url('/'),
				  "expiryDate" => $expiryDate,
				  "paymentMethods" => array("CARD","ACCOUNT_TRANSFER"),
				  "incomeSplitConfig" => $incomeSplitconfig
				));
		\Log::info("GetMonifyAccount Invoice Request:".$data);
	
		$curl = curl_init();
	
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $data,
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Basic " . base64_encode("$username:$password"),
			'Content-Type: application/json'
		  ),
		));
		
		$response = curl_exec($curl);
		curl_close($curl);
		$responseData = json_decode($response,true);
		\Log::info("GetMonifyAccount Invoice Response:".$response);
		if(isset($responseData['responseCode']) && $responseData['responseCode'] == 0)
		{
			//return $responseData['responseBody']->accountNumber;
			return $responseData;
		}
		else
		{
			return '';
		}
	}
	
	function MonnifyCallback(Request $request)
	{
		$json = (file_get_contents('php://input'));
		$decodeData = json_decode($json);
		
		if(isset($decodeData->eventData))
		{
			$decodeData = $decodeData->eventData;
		}
		\Log::info('MonnifyCallback: '.$json);
		
		$ussdUser->payment_reference = $uniqeID;
		$ussdUser->Save();
		
		$info_UssdUser = UssdUser::Where('payment_reference', $decodeData->paymentReference)->First();
		$info_UssdUser->is_paid = "1";
		$info_UssdUser->Save();
		
		$Message = "Thank you ".$info_UssdUser->name." for your contribution towards rescuing our state. Together a new Kogi is possible.".$this->newLine."From: Alh. Murtala Yakubu Ajaka (MURI)";
		
		if(env('APP_ENV')=="production" && 1==0)
		{
			\Log::info("Send SMS: http://3.131.19.214:8802/?phonenumber=234".substr($info_UssdUser->msisdn,-10)."&text=".urlencode($Message)."&sender=SELFSERVE&user=selfserve&password=1234567891");
			file_get_contents("http://3.131.19.214:8802/?phonenumber=234".substr($info_UssdUser->msisdn,-10)."&text=".urlencode($Message)."&sender=SELFSERVE&user=selfserve&password=1234567891");
		}
		return "OK";
	}
}