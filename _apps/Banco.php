<?php

//Write your custome class/methods here
namespace Apps;

class Banco extends Core
{
	public function __construct()
	{
		parent::__construct();
	}

	public function TransferInfo($id)
	{
		$TransferInfo = mysqli_query($this->dbCon, "select * from banco_transfers where id='$id'");
		$TransferInfo = mysqli_fetch_object($TransferInfo);
		return $TransferInfo;
	}


	public function AddTransferTransaction($accid, $transferid)
	{
		$TransferInfo = $this->TransferInfo($transferid);
		$transid = time();
		if ($this->Debit($accid, $TransferInfo->amount)) {
			$notes = "Funds transfer: #{$transferid}/{$transid}/{$TransferInfo->name}";
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,transferid,amount,type,notes) VALUES('$transid','$accid','$transferid','$TransferInfo->amount','DEBIT','$notes')");
			return $this->getLastId();
		}
		return false;
	}

	public function AddDebitTransaction($accid, $amount, $type, $notes)
	{
		$transid = time();
		if ($this->Debit($accid, $amount)) {
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,amount,type,notes) VALUES('$transid','$accid','$amount','$type','$notes')");
			return $this->getLastId();
		}
		return false;
	}


	public function AddCreditTransaction($accid, $amount, $type, $notes)
	{
		$transid = time();
		if ($this->Credit($accid, $amount)) {
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,amount,type,notes) VALUES('$transid','$accid','$amount','$type','$notes')");
			return $this->getLastId();
		}
		return false;
	}



	public function CreateAccount($currency, $account_type, $email, $mobile, $pasword, $firstname, $lastname, $address, $address2, $zipcode, $city, $state, $country)
	{
		mysqli_query($this->dbCon, "INSERT INTO banco_accounts(currency, account_type, email, mobile,pasword, firstname, lastname, address, address2, zipcode, city, state, country) VALUES('$currency', '$account_type', '$email', '$mobile', '$pasword', '$firstname', '$lastname', '$address', '$address2', '$zipcode', '$city', '$state', '$country')");
		//mysqli_query($this->dbCon, "INSERT INTO banco_accounts(email, mobile,pasword, firstname, lastname) VALUES('$email', '$mobile', '$pasword', '$firstname', '$lastname')");
		return $this->getLastId();
	}


	public function AdminDebitTransaction($accid, $amount, $type, $notes)
	{
		$Temp = new Template;
		$admin = $Temp->data['accid'];
		$transid = time();
		if ($this->Debit($accid, $amount)) {
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,amount,type,notes,by_admin) VALUES('$transid','$accid','$amount','$type','$notes','$admin')");
			return $this->getLastId();
		}
		return false;
	}


	public function AdminCreditTransaction($accid, $amount, $type, $notes)
	{
		$Temp = new Template;
		$admin = $Temp->data['accid'];
		$transid = time();
		if ($this->Credit($accid, $amount)) {
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,amount,type,notes,by_admin) VALUES('$transid','$accid','$amount','$type','$notes','$admin')");
			return $this->getLastId();
		}
		return false;
	}

	public function Debit($accid, $amount)
	{
		mysqli_query($this->dbCon, "UPDATE banco_accounts SET balance=balance-'$amount' where accid='$accid'");
		return mysqli_affected_rows($this->dbCon);
	}

	public function Credit($accid, $amount)
	{
		mysqli_query($this->dbCon, "UPDATE banco_accounts SET balance=balance+'$amount' where accid='$accid'");
		return mysqli_affected_rows($this->dbCon);
	}


	public function addTransaction($accid, $transferid)
	{
		$TransferInfo = $this->TransferInfo($transferid);
		$transid = time();
		if ($this->Debit($accid, $TransferInfo->amount)) {
			$notes = "Funds transfer: #{$transferid}/{$transid}/{$TransferInfo->name}";
			mysqli_query($this->dbCon, "INSERT INTO banco_transactions(transid,accid,transferid,amount,type,notes) VALUES('$transid','$accid','$transferid','$TransferInfo->amount','DEBIT','$notes')");
			return mysqli_affected_rows($this->dbCon);
		}
		return false;
	}


	public function GenOTP($length = 10)
	{
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return strtoupper($randomString);
	}


	public function genAccid()
	{
		$genAccid = mysqli_query($this->dbCon, "SELECT MAX(accid) AS maxaccid from banco_accounts");
		$genAccid = mysqli_fetch_object($genAccid);
		$maxaccid =  (int)$genAccid->maxaccid;
		if (($maxaccid == 1234567890) || ($maxaccid == 0)) {
			$maxaccid = (int)start_accid;
		} else {
			$maxaccid = $maxaccid + 1;
		}
		return $maxaccid;
	}


	public function LoadCountriesToSelect($selcountry = "")
	{
		$html = "";
		$LoadCountriesToSelect = mysqli_query($this->dbCon, "SELECT * FROM banco_countries");
		while ($country = mysqli_fetch_object($LoadCountriesToSelect)) {
			if ($selcountry == $country->name) {
				$html .= "<option value=\"{$country->name}\" selected>{$country->name}</option>";
			} else {
				$html .= "<option value=\"{$country->name}\">{$country->name}</option>";
			}
		}
		return $html;
	}

	public function LoadCurrenciesToSelect($selcurrency = "")
	{
		$html = "";
		$LoadCurrenciesToSelect = mysqli_query($this->dbCon, "SELECT * FROM banco_currencies");
		while ($currency = mysqli_fetch_object($LoadCurrenciesToSelect)) {
			if ($selcurrency == $currency->name) {
				$html .= "<option value=\"{$currency->code}\" selected>{$currency->name} ({$currency->code})</option>";
			} else {
				$html .= "<option value=\"{$currency->code}\">{$currency->name} ({$currency->code})</option>";
			}
		}
		return $html;
	}



	public function Monify($amount)
	{
		$Temp = new Template;
		$accid = $Temp->storage('accid');
		$User = $this->UserInfo($accid);
		$Curr = $this->CurrInfo($User->currency);
		$amount = number_format($amount, 2, ".", ",");
		return "{$Curr->sign} " . $amount;
	}

	public function UserBalance($accid, $amount)
	{
		$Temp = new Template;
		$User = $this->UserInfo($accid);
		$Curr = $this->CurrInfo($User->currency);
		$amount = number_format($amount, 2, ".", ",");
		return "{$Curr->sign} " . $amount . " {$Curr->code}";
	}


	public function Balance($amount)
	{
		$Temp = new Template;
		$accid = $Temp->storage('accid');
		$User = $this->UserInfo($accid);
		$Curr = $this->CurrInfo($User->currency);
		$amount = number_format($amount, 2, ".", ",");
		return "{$Curr->sign} " . $amount . " {$Curr->code}";
	}


	public function MonifyCredits()
	{
		$Temp = new Template;
		$accid = $Temp->storage('accid');
		$User = $this->UserInfo($accid);
		$Curr = $this->CurrInfo($User->currency);
		$Sum = $this->SumTransactions("CREDIT");
		$amount = number_format($Sum, 2, ".", ",");
		return "{$Curr->sign} " . $amount;
	}

	public function MonifyDebits()
	{
		$Temp = new Template;
		$accid = $Temp->storage('accid');
		$User = $this->UserInfo($accid);
		$Curr = $this->CurrInfo($User->currency);
		$Sum = $this->SumTransactions("DEBIT");
		$amount = number_format($Sum, 2, ".", ",");
		return "{$Curr->sign} " . $amount;
	}

	public function SumTransactions($type = 'CREDIT')
	{
		if ($type == "CREDIT") {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions WHERE type='CREDIT' OR type='REVERSE'");
		} elseif ($type == "DEBIT") {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions WHERE type='DEBIT' OR type='FEES'");
		} else {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions");
		}
		$SumTransactions = mysqli_fetch_object($SumTransactions);
		return $SumTransactions->transum;
	}


	public function SumUserTransactions($accid, $type = 'CREDIT')
	{
		if ($type == "CREDIT") {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions WHERE accid = '$accid' AND (type='CREDIT' OR type='REVERSE')");
		} elseif ($type == "DEBIT") {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions WHERE accid = '$accid' AND (type='DEBIT' OR type='FEES')");
		} else {
			$SumTransactions = mysqli_query($this->dbCon, "SELECT SUM(amount) AS transum FROM banco_transactions WHERE accid = '$accid'");
		}
		$SumTransactions = mysqli_fetch_object($SumTransactions);
		return $SumTransactions->transum;
	}
	public function UserInfo($username)
	{
		$UserInfo = mysqli_query($this->dbCon, "SELECT * FROM banco_accounts WHERE accid='$username' OR email='$username'");
		$UserInfo = mysqli_fetch_object($UserInfo);
		return $UserInfo;
	}


	public function CheckKYC($route = "ibanking.dashboard")
	{
		$Temp = new Template;
		$accid = $Temp->storage('accid');
		$User = $this->UserInfo($accid);

		if ($User->reset_password || $User->new_account) {
			return "ibanking.changepassword";
		} else {
			if ($User->kyc_approved) {
				return $route;
			} else {
				return "ibanking.kyc";
			}
		}
	}


	public function CurrInfo($curr)
	{
		$CurrInfo = mysqli_query($this->dbCon, "select * from banco_currencies where code='$curr'");
		$CurrInfo = mysqli_fetch_object($CurrInfo);
		return $CurrInfo;
	}

	public function getRate($curr = "USD")
	{
		$CurrInfo = $this->CurrInfo($curr);
		$now = time();
		$calltime = $CurrInfo->calltime;
		if (($now - $calltime) >= 864000) {
			$ch = curl_init("http://api.exchangeratesapi.io/v1/latest?access_key=1646af9a10bd8d8f62fa37033a5e9a03");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = json_decode(curl_exec($ch));
			curl_close($ch);
			$conversion_rates = $output->rates;
			$curr_rate = $conversion_rates->$curr;
			$out_result = number_format($curr_rate, 2, '.', ',');

			$this->SetCurrRateInfo($curr, "curr_rate", $out_result);
			$this->SetCurrRateInfo($curr, "calltime", time());

			return $out_result;
		}
		return $CurrInfo->curr_rate;
	}


	public function ShowArrow($code)
	{
		$arrow = "";
		$rate = (float)$this->getRate($code);
		$CurrInfo = $this->CurrInfo($code);
		$oldRate = (float)$CurrInfo->curr_rate;

		if ($rate < $oldRate) {
			$arrow = "<i class=\"material-icons text-danger vm small\">arrow_downward</i>";
		} elseif ($rate >= $oldRate) {
			$arrow = "<i class=\"material-icons text-success vm small\">arrow_upward</i>";
		}
		$this->SetCurrRateInfo($code, "curr_rate", $rate);

		return $arrow;
	}

	public function ShowArrowColor($code)
	{
		$color = "";
		$rate = (float)$this->getRate($code);
		$CurrInfo = $this->CurrInfo($code);
		$oldRate = (float)$CurrInfo->curr_rate;

		if ($rate < $oldRate) {
			$color = "danger";
		} elseif ($rate >= $oldRate) {
			$color = "success";
		}
		return $color;
	}

	public function cardCurrencies()
	{
		$cardCurrencies = mysqli_query($this->dbCon, "select * from banco_currencies ORDER BY id ASC");
		return $cardCurrencies;
	}


	public function LogActivity($accid, $get_ip, $get_os, $get_browser, $get_device)
	{
		mysqli_query($this->dbCon, "INSERT INTO banco_activities(accid,ip,os,browser,device) VALUES('$accid','$get_ip','$get_os','$get_browser','$get_device')");
		return $this->getLastId();
	}



	public function LogonActivities($accid)
	{
		$LogonActivities = mysqli_query($this->dbCon, "SELECT * FROM banco_activities WHERE accid='$accid'");
		return $LogonActivities;
	}


	public function AllTransactions()
	{
		$AllTransactions = mysqli_query($this->dbCon, "SELECT * FROM banco_transactions ORDER BY created DESC");
		return $AllTransactions;
	}



	public function RecentTransactions($limit = 10)
	{
		$RecentTransactions = mysqli_query($this->dbCon, "SELECT * FROM banco_transactions ORDER BY created DESC");
		return $RecentTransactions;
	}

	public function CountTransactions()
	{
		$CountTransactions = mysqli_query($this->dbCon, "SELECT count(tid) AS transcount FROM banco_transactions");
		$CountTransactions = mysqli_fetch_object($CountTransactions);
		return $CountTransactions->transcount;
	}


	public function RecentUserTransactions($accid)
	{
		$RecentTransactions = mysqli_query($this->dbCon, "SELECT * FROM banco_transactions WHERE accid='$accid' ORDER BY created DESC");
		return $RecentTransactions;
	}


	public function TransactionInfo($id)
	{
		$TransactionInfo = mysqli_query($this->dbCon, "select * from banco_transactions where tid='$id' OR transid='$id'");
		$TransactionInfo = mysqli_fetch_object($TransactionInfo);
		return $TransactionInfo;
	}


	public function CountUserTransactions($accid)
	{
		$CountTransactions = mysqli_query($this->dbCon, "SELECT count(tid) AS transcount FROM banco_transactions WHERE accid='$accid'");
		$CountTransactions = mysqli_fetch_object($CountTransactions);
		return $CountTransactions->transcount;
	}


	public function SetCurrRateInfo($code, $key, $val)
	{
		mysqli_query($this->dbCon, "UPDATE banco_currencies SET $key='$val' where code='$code'");
		return mysqli_affected_rows($this->dbCon);
	}


	public function adminUsers()
	{
		$adminUsers = mysqli_query($this->dbCon, "select * from banco_accounts ORDER BY accid ASC");
		return $adminUsers;
	}


	public function UserLogin($username, $password)
	{
		$UserLogin = mysqli_query($this->dbCon, "select * from banco_accounts where (email='$username' OR mobile='$username') AND password='$password'");
		$UserLogin = mysqli_fetch_object($UserLogin);
		$this->SetUserInfo($UserLogin->accid, "lastseen", date("Y-m-d g:i:s"));

		return $UserLogin;
	}
	public function SetUserInfo($username, $key, $val)
	{
		mysqli_query($this->dbCon, "UPDATE banco_accounts SET $key='$val' where email='$username' OR accid='$username' OR mobile='$username'");
		return mysqli_affected_rows($this->dbCon);
	}

	public function UserExists($username)
	{
		$UserExists = mysqli_query($this->dbCon, "select * from banco_accounts where email='$username' OR accid='$username' OR mobile='$username'");
		$UserExists = mysqli_fetch_object($UserExists);
		if (isset($UserExists->accid)) {
			return true;
		}
		return false;
	}
}
