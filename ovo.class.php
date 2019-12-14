<?php
/*
//////////////////////////////////////////////////////////////////////////
 Unofficial OVO API 
 Author : ReniRails (Reni Mikazuki)
 Build Purpose : Private Usage
//////////////////////////////////////////////////////////////////////////
*/

class Ovo
{
    const OVO_API		= "https://api.ovo.id/";
    const OVO_AWS_API	        = "https://apigw01.aws.ovo.id/";
    const PUSH 			= "FCM|f4OXYs_ZhuM:APA91bGde-ie2YBhmbALKPq94WjYex8gQDU2NMwJn_w9jYZx0emAFRGKHD2NojY6yh8ykpkcciPQpS0CBma-MxTEjaet-5I3T8u_YFWiKgyWoH7pHk7MXChBCBRwGRjMKIPdi3h0p2z7";
    
    private $id 		= "C7UMRSMFRZ46D9GW9IK7";
	private $version  	= "2.15.0";
	private $os  		= "Android";
	private $os_version = "8.1.0";
	private $mac 		= "02:00:00:44:55:66";
	private $device 	= "5324a620-dc68-3214-a812-dbdfc0a63341";
	private $token;

	
	public function __construct($token = null){
		$this->token = $token;
	}
	
	public function checkDanaMasuk($uniq){
		$trans = $this->getTransaction();
	
		foreach($trans as $trf){
			if($uniq == $trf['transaction_amount']){
				return true;
				exit;
			}
		}
		return false;

	}
	
	public function transferOvo($phone, $jumlah, $pesan=""){
		$data = array(
            'amount'   => $jumlah,
            'message'  => $pesan == "" ? 'Sent from OVOID' : $pesan,
            'to'       => $phone,
            'trxId'    => $this->generateTrxId($jumlah, 'trf_ovo')
        );

		return $this->curl(self::OVO_API . 'v1.0/api/customers/transfer', $data, $this->headers($this->token));
		
	}
	
	public function transferBank($nama, $OvoCard, $norek, $jumlah, $kodebank, $namabank, $message, $notes){
		$data = array(
            'accountName'           => $nama,
            'accountNo'             => $OvoCard,
            'accountNoDestination'  => $norek,
            'amount'                => $jumlah,
            'bankCode'              => $kodebank,
            'bankName'              => $namabank,
            'message'               => $message,
            'notes'                 => $notes,
            'transactionId'         => $this->generateTrxId($jumlah, 'trf_other_bank')
        );
		return $this->curl(self::OVO_API . 'transfer/direct', $data, $this->headers($this->token));
	}
	
	protected function generateTrxId($jumlah, $mark){
		$data = array(
            'actionMark' => $mark,
            'amount'     => $jumlah
        );
		return $this->curl(self::OVO_API . 'v1.0/api/auth/customer/genTrxId', $data, $this->headers($this->token))['trxId'];
	}
	
	public function getTransaction(){
		return $this->curl(self::OVO_API . 'wallet/v2/transaction?page=1&limit=10&productType=001', null, $this->headers($this->token))['data']['0']['complete'];
	}
	
	public function accInfo(){
		return $this->curl(self::OVO_API . 'v1.0/api/front/', null, $this->headers($this->token));
	}
	
	public function getBalance(){
		return $this->curl(self::OVO_API . 'v1.0/api/front/', null, $this->headers($this->token))['balance']['001']['card_balance'];
	}
	
	public function logout(){
		return $this->curl(self::OVO_API . 'v1.0/api/auth/customer/logout', null, $this->headers($this->token));
	}
	
	public function auth2FA($phone){

        $data = array(
            'deviceId' => $this->generateID(),
            'mobile'   => $phone
        );
		
		return $this->curl(self::OVO_API . 'v2.0/api/auth/customer/login2FA', $data, $this->headers())['refId'];
	}
	
	public function authVerify($ref, $otp, $phone){
		$data = array(
            'appVersion'        => $this->version,
            'deviceId'          => $this->generateID(),
            'macAddress'        => $this->mac,
            'mobile'            => $phone,
            'osName'            => $this->os,
            'osVersion'         => $this->os_version,
            'pushNotificationId'=> self::PUSH,
            'refId'             => $ref,
            'verificationCode'  => $otp
        );
		
		return $this->curl(self::OVO_API . 'v2.0/api/auth/customer/login2FA/verify', $data, $this->headers())['updateAccessToken'];
	}
	
	public function authPIN($pin, $upToken){
		$data = array(
            'deviceUnixtime'   => 1543693061,
            'securityCode'     => $pin,
            'updateAccessToken'=> $upToken,
            'message'          => ''
        );
		
		return $this->curl(self::OVO_API . 'v2.0/api/auth/customer/loginSecurityCode/verify', $data, $this->headers())['token'];
	}
	
	protected function headers($auth=false){
		if($auth){
			$headers = array(
				'app-id: ' .$this->id,
				'App-Version: ' .$this->version,
				'OS: ' .$this->os,
				'Authorization: ' .$auth,
				'Content-Type: application/json;charset=UTF-8'
			);
		}else{
			$headers = array(
				'app-id: ' .$this->id,
				'App-Version: ' .$this->version,
				'OS: ' .$this->os,
				'Content-Type: application/json;charset=UTF-8'
			);
		}
		return $headers;
	}
	
	public function isOVO($totalAmount, $mobilePhone)
    {
        $data = array(
            'totalAmount' => $totalAmount,
            'mobile'      => $mobilePhone
        );
		return $this->curl(self::OVO_API . 'v1.1/api/auth/customer/isOVO', $data, $this->headers($this->token));
    }
	
    protected function curl($url, $data = NULL, $headers = NULL)
    {
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($data) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }else{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		}
        
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
	
	protected function generateID()
	{
		return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
		);
	}
}
