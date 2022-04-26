<?php

use function PHPSTORM_META\map;

$Route->add('/ibanking/auth/register/previous', function () {
    $Template = new Apps\Template;
    $regstep = (int)$Template->storage("regstep");
    if ($regstep <= 1) {
        $Template->expire();
    } elseif ($regstep > 1) {
        $regstep = $regstep - 1;
    }
    $Template->store("regstep", $regstep);
    $Template->redirect("/ibanking/auth/register");
}, 'GET');


$Route->add('/banco/auth/register', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;
    $MysqliDb = new Apps\MysqliDb;

    $Data = $Model->post($_POST);
    $regstep = (int)$Template->storage("regstep");
    $RegData = isset($Template->data['RegData']) ? $Template->data['RegData'] : array();

    switch ($regstep) {
        case 0:
            if ($Template->auth) {
                $Template->expire();
            }
            $RegData["sex"] = $Data->sex;
            $RegData["email"] = $Data->email;
            $RegData["mobile"] = $Data->mobile;
            $RegData["firstname"] = $Data->firstname;
            $RegData["lastname"] = $Data->lastname;
            $Template->store("RegData", $RegData);
            $Template->store("regstep", 1);
            $Template->redirect("/ibanking/auth/register");
            break;
        case 1:
            $RegData["address"] = $Data->address;
            $RegData["address2"] = $Data->address2;
            $RegData["country"] = $Data->country;
            $RegData["state"] = $Data->state;
            $RegData["city"] = $Data->city;
            $RegData["zipcode"] = $Data->zipcode;
            $Template->store("RegData", $RegData);
            $Template->store("regstep", 2);
            $Template->redirect("/ibanking/auth/register");
            break;
        case 2:
            $regmail =  md5($RegData["email"]);
            if ($_FILES["avatar"]['size'] > 0) {
                $handle = new \Verot\Upload\Upload($_FILES["avatar"]);
                $handle->image_resize    = true;
                $handle->image_y    = 500;
                $handle->image_x    = 500;
                $FileDir = "{$Template->store}accounts/profiles/{$regmail}/";
                $handle->process($FileDir);
                if ($handle->processed) {
                    $RegData["avatar"] = $handle->file_dst_pathname;
                    $handle->clean();
                }
            }
            $Template->store("regstep", 3);
            $Template->store("RegData", $RegData);
            $Template->redirect("/ibanking/auth/register");
            break;
        case 3:
            $RegData["currency"] = $Data->currency;
            $RegData["account_type"] = $Data->account_type;
            $Template->store("regstep", 4);
            $Template->store("RegData", $RegData);
            $Template->redirect("/ibanking/auth/register");
            break;
        case 4:
            $regmail =  md5($RegData["email"]);
            if ($_FILES["identification"]['size'] > 0) {
                $handle = new \Verot\Upload\Upload($_FILES["identification"]);
                $handle->image_resize    = true;
                $handle->image_y    = 500;
                $handle->image_x    = 500;
                $FileDir = "{$Template->store}accounts/profiles/{$regmail}/";
                $handle->process($FileDir);
                if ($handle->processed) {
                    $RegData["identification"] = $handle->file_dst_pathname;
                    $handle->clean();
                }
            }
            $Template->store("RegData", $RegData);
            $Template->store("regstep", 5);
            $Template->redirect("/ibanking/auth/register");
            break;
        case 5:
            $regmail =  md5($RegData["email"]);
            if ($_FILES["utility"]['size'] > 0) {
                $handle = new \Verot\Upload\Upload($_FILES["utility"]);
                $handle->image_resize    = true;
                $handle->image_y    = 500;
                $handle->image_x    = 500;
                $FileDir = "{$Template->store}accounts/profiles/{$regmail}/";
                $handle->process($FileDir);
                if ($handle->processed) {
                    $RegData["utility"] = $handle->file_dst_pathname;
                    $handle->clean();
                }
            }
            $Template->store("RegData", $RegData);
            $Template->store("regstep", 6);
            $Template->redirect("/ibanking/auth/register");
            break;

        case 6:


            $Temp_Password = $Banco->GenPassword(7);
            $newaccid = (int)$MysqliDb->insert("banco_accounts", array(
                "sex" => $RegData['sex'],
                "title" => ($RegData['sex'] == "Male") ? "Mr" : "Mrs",
                "email" => $RegData['email'],
                "mobile" => $RegData['mobile'],
                "firstname" => $RegData['firstname'],
                "lastname" => $RegData['lastname'],
                "address" => $RegData['address'],
                "address2" => $RegData['address2'],
                "country" => $RegData['country'],
                "state" => $RegData['state'],
                "city" => $RegData['city'],
                "zipcode" => $RegData['zipcode'],
                "avatar" => $RegData['avatar'],
                "currency" => $RegData['currency'],
                "account_type" => $RegData['account_type'],
                "identification" => $RegData['identification'],
                "utility" => $RegData['utility'],
                "kyc" => 1,
                "password" => $Temp_Password,
                "new_account" => 1,
                "reset_password" => 1
            ));

            if ($newaccid) {

                $fullname = "{$RegData['firstname']} {$RegData['lastname']}";
                $Template->store("newaccid", $newaccid);

                //SMS HERE//
                // $sent = $SMSLive->send($Login->mobile, "");
                //SMS HERE//

                $subject = "Welcome to Landmark Finance";
                $mailbody = "<p>Congratulations <strong>{$fullname}</strong>!</p>
                <p>Your application has been submitted. However, our team will begin review of the details and documents you submitted.</p>
                <p>You will recieve your account details as soon as we have completed the verification and profiling.</p>
                ";

                //Email Notix//
                $Mailer = new Apps\Emailer();
                $EmailTemplate = new Apps\EmailTemplate('mails.default');
                $EmailTemplate->subject = $subject;
                $EmailTemplate->fullname = $fullname;
                $EmailTemplate->mailbody = $mailbody;
                $Mailer->subject = $subject;
                $Mailer->SetTemplate($EmailTemplate);
                $Mailer->toEmail = $RegData['email'];
                $Mailer->toName = $fullname;
                $Mailer->send();
                //Email Notix//

                $Template->redirect("/ibanking/auth/lock");
            } else {
                $Template->store("regstep", 0);
                $Template->setError("Registration of your account failed, kindly try again or contact support.", "danger", "/ibanking/auth/register");
                $Template->redirect("/ibanking/auth/register");
            }

            break;

        default:
            $Template->expire();
            $Template->redirect("/ibanking/auth/register");
            break;
    }

    $Template->setError("Registration of your account failed, kindly try again or contact support.", "danger", "/ibanking/auth/register");
    $Template->redirect("/ibanking/auth/register");
}, 'POST');


$Route->add('/banco/auth/login', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;
    $MysqliDb = new Apps\MysqliDb;

    $Data = $Model->post($_POST);

    $username = $Data->username;
    $password = $Data->password;

    $MysqliDb->where("accid", $username)->where("password", $password);
    $UserInfo = $MysqliDb->getOne("banco_accounts");
    $accid = $UserInfo['accid'];
    if (isset($UserInfo['accid'])) {


        if ($UserInfo['otp_enabled']) {
            $otp = $Banco->GenOTP(6);

            $Banco->SetUserInfo($UserInfo['accid'], "otp_pending", 1);
            $Banco->SetUserInfo($UserInfo['accid'], "otp", strtoupper($otp));
            $Banco->SetUserInfo($UserInfo['accid'], "otp_time", date("Y-m-d g:i:S"));

            $fullname = "{$UserInfo['firstname']} {$UserInfo['lastname']}";

            $Template->store("accid", $UserInfo['accid']);

            $message = "NEVER DISCLOSE YOUR OTP TO ANYONE. Your OTP to login is {$otp}. Enquiry? Call Online Banking Support.";
            // $sent = $SMSLive->send($Login->mobile, $message);
            $subject = "Your OTP to login is {$otp}";

            //Email Notix//
            $Mailer = new Apps\Emailer();
            $EmailTemplate = new Apps\EmailTemplate('mails.otp');
            $EmailTemplate->subject = $subject;
            $EmailTemplate->otp = $otp;
            $EmailTemplate->fullname = $fullname;
            $EmailTemplate->mailbody = $message;
            $Mailer->subject = $subject;
            $Mailer->SetTemplate($EmailTemplate);
            $Mailer->toEmail = $UserInfo['email'];
            $Mailer->toName = $fullname;
            $sent = $Mailer->send();
            //Email Notix//

            $Template->setError("We sent you a One Time Pin for login", "success", "/ibanking/auth/otp");
            $Template->redirect("/ibanking/auth/otp");
        } else {

            $Template->authorize($accid);
            $Device = new Apps\Device;
            $Banco->LogActivity($accid, $Device->get_ip(), $Device->get_os(), $Device->get_browser(), $Device->get_device());

            $fullname = "{$UserInfo['firstname']} {$UserInfo['lastname']}";
            $message = "<p>Hello {$fullname}</p>
            <p>Your account has just been accessed from a {$Device->get_device()} with the information below.</p>
            <p>
            IP Address: <strong>{$Device->get_ip()}</strong><br/>
            Operating System: <strong>{$Device->get_os()}</strong><br/>
            System Browser: <strong>{$Device->get_browser()}</strong><br/>
            </p>
            <p>If this is not you, contact your account officer for urgent action.</p>
            ";

            // $sent = $SMSLive->send($Login->mobile, $message);
            $subject = "New Login from: {$Device->get_ip()}";

            //Email Notix//
            $Mailer = new Apps\Emailer();
            $EmailTemplate = new Apps\EmailTemplate('mails.default');
            $EmailTemplate->subject = $subject;
            $EmailTemplate->fullname = $fullname;
            $EmailTemplate->mailbody = $message;
            $Mailer->subject = $subject;
            $Mailer->SetTemplate($EmailTemplate);
            $Mailer->toEmail = $UserInfo['email'];
            $Mailer->toName = $fullname;
            $Mailer->send();
            //Email Notix//

            $Template->redirect("/ibanking/");
        }
    }

    $Template->setError("Login details incoorect, recheck and try again", "danger", "/ibanking/auth/login");
    $Template->redirect("/ibanking/auth/login");
}, 'POST');


$Route->add('/banco/auth/otp', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;
    $MysqliDb = new Apps\MysqliDb;

    $accid = $Template->storage("accid");

    $Data = $Model->post($_POST);
    $otp = $Data->otp;

    $MysqliDb->where("accid", $accid)->where("otp", $otp);
    $UserInfo = $MysqliDb->getOne("banco_accounts");

    if (isset($UserInfo['accid'])) {

        $Banco->SetUserInfo($UserInfo['accid'], "otp_pending", 0);
        $Banco->SetUserInfo($UserInfo['accid'], "otp", null);
        $Banco->SetUserInfo($UserInfo['accid'], "otp_time", date("Y-m-d g:i:S"));

        $Template->authorize($accid);
        $Device = new Apps\Device;
        $Banco->LogActivity($accid, $Device->get_ip(), $Device->get_os(), $Device->get_browser(), $Device->get_device());

        $fullname = "{$UserInfo['firstname']} {$UserInfo['lastname']}";
        $message = "<p>Hello {$fullname}</p>
        <p>Your account has just been accessed from a {$Device->get_device()} with the information below.</p>
        <p>
        IP Address: <strong>{$Device->get_ip()}</strong><br/>
        Operating System: <strong>{$Device->get_os()}</strong><br/>
        System Browser: <strong>{$Device->get_browser()}</strong><br/>
        </p>
        <p>If this is not you, contact your account officer for urgent action.</p>
        ";
        // $sent = $SMSLive->send($Login->mobile, $message);
        $subject = "New Login from: {$Device->get_ip()}";

        //Email Notix//
        $Mailer = new Apps\Emailer();
        $EmailTemplate = new Apps\EmailTemplate('mails.default');
        $EmailTemplate->subject = $subject;
        $EmailTemplate->fullname = $fullname;
        $EmailTemplate->mailbody = $message;
        $Mailer->subject = $subject;
        $Mailer->SetTemplate($EmailTemplate);
        $Mailer->toEmail = $UserInfo['email'];
        $Mailer->toName = $fullname;
        $Mailer->send();
        //Email Notix//

        $Template->redirect("/ibanking/");
    }

    $Template->setError("One Time Password is incorrect, recheck and try again", "danger", "/ibanking/auth/otp");
    $Template->redirect("/ibanking/auth/otp");
}, 'POST');




$Route->add('/banco/dashboard/profile', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;

    $accid = $Template->storage("accid");

    $Data = $Model->post($_POST);

    $Banco->SetUserInfo($accid, "firstname", $Data->firstname);
    $Banco->SetUserInfo($accid, "lastname", $Data->lastname);
    $Banco->SetUserInfo($accid, "email", $Data->email);
    $Banco->SetUserInfo($accid, "mobile", $Data->mobile);
    $Banco->SetUserInfo($accid, "address", $Data->address);
    $Banco->SetUserInfo($accid, "address2", $Data->address2);

    $Banco->SetUserInfo($accid, "city", $Data->city);
    $Banco->SetUserInfo($accid, "zipcode", $Data->zipcode);

    $Banco->SetUserInfo($accid, "state", $Data->state);
    $Banco->SetUserInfo($accid, "country", $Data->country);

    if ($_FILES["avatar"]['size'] > 0) {
        $handle = new \Verot\Upload\Upload($_FILES["avatar"]);
        $handle->image_resize    = true;
        $handle->image_y    = 500;
        $handle->image_x    = 500;
        $FileDir = "{$Template->store}accounts/profiles/{$accid}/";
        $handle->process($FileDir);
        if ($handle->processed) {
            $photos = $handle->file_dst_pathname;
            $Banco->SetUserInfo($accid, "avatar", $photos);
            $handle->clean();
        }
    }

    $Template->setError("Profile updated successfully", "success", "/ibanking/profile");
    $Template->redirect("/ibanking/profile");
}, 'POST');



$Route->add('/banco/dashboard/settings', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;

    $accid = $Template->storage("accid");

    $Data = $Model->post($_POST);

    $otp_enabled = 0;
    if (isset($Data->otp_enabled)) {
        $otp_enabled = 1;
    }
    $email_notix = 0;
    if (isset($Data->email_notix)) {
        $email_notix = 1;
    }
    $sms_notix = 0;
    if (isset($Data->sms_notix)) {
        $sms_notix = 1;
    }
    $profile_notix = 0;
    if (isset($Data->profile_notix)) {
        $profile_notix = 1;
    }

    $Banco->SetUserInfo($accid, "otp_enabled", $otp_enabled);
    $Banco->SetUserInfo($accid, "email_notix", $email_notix);
    $Banco->SetUserInfo($accid, "sms_notix", $sms_notix);
    $Banco->SetUserInfo($accid, "profile_notix", $profile_notix);

    $Template->setError("Settings updated successfully", "success", "/ibanking/settings");
    $Template->redirect("/ibanking/settings");
}, 'POST');


$Route->add('/banco/auth/reset', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;
    $Template = new Apps\Template;
    $Data = $Model->post($_POST);
    $username = $Data->username;
    $UserInfo = $Banco->UserInfo($username);
    if (isset($UserInfo->accid)) {

        $GenPassword = $Banco->GenPassword(6);
        $Banco->SetUserInfo($UserInfo->accid, "password", $GenPassword);
        $Banco->SetUserInfo($UserInfo->accid, "reset_password", 1);

        $fullname = "{$UserInfo->firstname} {$UserInfo->lastname}";

        // $sent = $SMSLive->send($Login->mobile, $message);
        $subject = "Reset password, one more step";
        $mailbody = "<p>Hello {$fullname}</p>
        <p>A new temporary password has been generated for your account. You are expected to change this password on your next login.</p>";

        //Email Notix//
        $Mailer = new Apps\Emailer();
        $EmailTemplate = new Apps\EmailTemplate('mails.reset');
        $EmailTemplate->subject = $subject;
        $EmailTemplate->password = $GenPassword;
        $EmailTemplate->fullname = $fullname;
        $EmailTemplate->mailbody = $mailbody;
        $Mailer->subject = $subject;
        $Mailer->SetTemplate($EmailTemplate);
        $Mailer->toEmail = $UserInfo->email;
        $Mailer->toName = $fullname;
        $Mailer->send();
        //Email Notix//

        $Template->setError("We sent you a temporary password.", "success", "/ibanking/auth/reset");
        $Template->redirect("/ibanking/auth/reset");
    }

    $Template->setError("Login details incorrect, recheck and try again", "danger", "/ibanking/auth/reset");
    $Template->redirect("/ibanking/auth/reset");
}, 'POST');



$Route->add('/banco/auth/changepassword', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;

    $accid = $Template->storage("accid");
    $User = $Banco->UserInfo($accid);
    $curr_password = $User->password;

    $Data = $Model->post($_POST);

    $Template->debug($curr_password);

    if ($Data->oldpass != $curr_password) {
        $Template->setError("Current password is not correct", "danger", "/ibanking");
        $Template->redirect("/ibanking");
    }

    if ($Data->newpass != $Data->newpass1) {
        $Template->setError("Password did not match", "danger", "/ibanking");
        $Template->redirect("/ibanking");
    }



    $Banco->SetUserInfo($accid, "password", $Data->newpass);
    $Banco->SetUserInfo($accid, "reset_password", 0);
    $Banco->SetUserInfo($accid, "new_account", 0);

    $Device = new Apps\Device;

    $fullname = "{$User->firstname} {$User->lastname}";
    $message = "<p>Hello {$fullname}</p>
    <p>Your account password has just been changed from a {$Device->get_device()} with the information below.</p>
    <p>
    IP Address: <strong>{$Device->get_ip()}</strong><br/>
    Operating System: <strong>{$Device->get_os()}</strong><br/>
    System Browser: <strong>{$Device->get_browser()}</strong><br/>
    </p>
    <p>If this is not you, contact your account officer for urgent action.</p>
    ";
    // $sent = $SMSLive->send($Login->mobile, $message);
    $subject = "Password Changed";

    //Email Notix//
    $Mailer = new Apps\Emailer();
    $EmailTemplate = new Apps\EmailTemplate('mails.default');
    $EmailTemplate->subject = $subject;
    $EmailTemplate->fullname = $fullname;
    $EmailTemplate->mailbody = $message;
    $Mailer->subject = $subject;
    $Mailer->SetTemplate($EmailTemplate);
    $Mailer->toEmail = $User->email;
    $Mailer->toName = $fullname;
    $Mailer->send();
    //Email Notix//

    $Template->setError("Password updated successfully", "success", "/ibanking");
    $Template->redirect("/ibanking");

}, 'POST');





$Route->add('/banco/dashboard/sendmoney', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;

    $accid = $Template->storage("accid");
    $ThisUser = $Banco->UserInfo($accid);


    $Data = $Model->post($_POST);
    $paystep = (int)$Template->storage("paystep");
    $PayData = isset($Template->data['PayData']) ? $Template->data['PayData'] : array();
    if (isset($Data->startpay)) {
        $paystep = 0;
        $PayData = array();
    }

    if (isset($Data->newpin)) {

        $pin = $Data->pin;
        $Banco->SetUserInfo($accid, "pin", $pin);

        $Device = new Apps\Device;
        $fullname = "{$ThisUser->firstname} {$ThisUser->lastname}";
        $message = "<p>Hello {$fullname}</p>
        <p>Your account Transaction PIN has just been changed from a {$Device->get_device()} with the information below.</p>
        <p>
        IP Address: <strong>{$Device->get_ip()}</strong><br/>
        Operating System: <strong>{$Device->get_os()}</strong><br/>
        System Browser: <strong>{$Device->get_browser()}</strong><br/>
        </p>
        <p>If this is not you, contact your account officer for immediate action.</p>
        ";

        // $sent = $SMSLive->send($Login->mobile, $message);
        $subject = "PIN Changed Successfully";

        //Email Notix//
        $Mailer = new Apps\Emailer();
        $EmailTemplate = new Apps\EmailTemplate('mails.default');
        $EmailTemplate->subject = $subject;
        $EmailTemplate->fullname = $fullname;
        $EmailTemplate->mailbody = $message;
        $Mailer->subject = $subject;
        $Mailer->SetTemplate($EmailTemplate);
        $Mailer->toEmail = $ThisUser->email;
        $Mailer->toName = $fullname;
        $Mailer->send();
        //Email Notix//

        $Template->setError("Transaction PIN set successfully", "success", "/ibanking/transfer");
        $Template->redirect("/ibanking/transfer");
    }

    // $Template->debug($paystep);

    switch ($paystep) {
        case 0:

            $PayData['amount'] = $Data->amount;
            $Template->store("PayData", $PayData);
            $Template->store("paystep", 1);

            // Clear all user errors//
            $Banco->setUserInfo($accid,"tac",0);
            $Banco->setUserInfo($accid,"aml",0);
            $Banco->setUserInfo($accid,"uvc",0);
            // Clear all user errors//

            $Template->setError("Transfer transaction started", "success", "/ibanking/transfer");
            $Template->redirect("/ibanking/transfer");
            break;

        case 1:

            $PayData['name'] = $Data->name;
            $PayData['bankname'] = $Data->bankname;
            $PayData['bankaddress'] = $Data->bankaddress;
            $PayData['swiftcode'] = $Data->swiftcode;
            $PayData['iban'] = $Data->iban;
            $PayData['abaroutine'] = $Data->abaroutine;
            $PayData['accno'] = $Data->accno;
            $PayData['accname'] = $Data->accname;

            $Template->store("PayData", $PayData);
            $Template->store("paystep", 2);

            $Template->setError("Transfer information saved", "success", "/ibanking/transfer");
            $Template->redirect("/ibanking/transfer");

            break;

        case 2:

            $securepin = $Data->securepin;
            if ($ThisUser->pin != $securepin) {
                $Template->setError("Incorrect Transaction PIN", "danger", "/ibanking/transfer");
                $Template->redirect("/ibanking/transfer");
            }

            $PayData['pin'] = $securepin;

            if ($ThisUser->otp_enabled) {

                $otp = $Banco->GenOTP(6);
                $Banco->SetUserInfo($accid, "otp_pending", 1);
                $Banco->SetUserInfo($accid, "otp", strtoupper($otp));
                $Banco->SetUserInfo($accid, "otp_time", date("Y-m-d g:i:S"));
                $fullname = "{$ThisUser->firstname} {$ThisUser->lastname}";
                $message = "NEVER DISCLOSE YOUR OTP TO ANYONE. Your Transfer OTP is {$otp}. Enquiry? Call Online Banking Support.";
                // $sent = $SMSLive->send($Login->mobile, $message);
                $subject = "Your OTP for Transfer is {$otp}";
                //Email Notix//
                $Mailer = new Apps\Emailer();
                $EmailTemplate = new Apps\EmailTemplate('mails.otp');
                $EmailTemplate->subject = $subject;
                $EmailTemplate->otp = $otp;
                $EmailTemplate->fullname = $fullname;
                $EmailTemplate->mailbody = $message;
                $Mailer->subject = $subject;
                $Mailer->SetTemplate($EmailTemplate);
                $Mailer->toEmail = $ThisUser->email;
                $Mailer->toName = $fullname;
                $Mailer->send();
                //Email Notix//

                $Template->store("PayData", $PayData);
                $Template->store("paystep", 3);
                $Template->setError("Transaction PIN verified successfully", "success", "/ibanking/transfer");
                $Template->redirect("/ibanking/transfer");
            }

            $Template->store("PayData", $PayData);
            $Template->store("paystep", 4);
            $Template->setError("Transaction PIN verified successfully", "success", "/ibanking/transfer");
            $Template->redirect("/ibanking/transfer");
            break;

        case 3:

            if (isset($Data->setotp)) {
                $otp = $Data->otp;
                if ($otp != $ThisUser->otp) {
                    $Template->setError("Incorrect One Time Password", "danger", "/ibanking/transfer");
                    $Template->redirect("/ibanking/transfer");
                }
                $Banco->SetUserInfo($accid, "otp_pending", 0);
                $Banco->SetUserInfo($accid, "otp", null);
                $Banco->SetUserInfo($accid, "otp_time", date("Y-m-d g:i:S"));

                $Template->store("paystep", 4);
                $Template->setError("One Time Password Verified", "success", "/ibanking/transfer");
                $Template->redirect("/ibanking/transfer");
            }

            $Template->store("paystep", 4);
            $Template->redirect("/ibanking/transfer");

            break;

        case 4:

            $MysqliDb = new Apps\MysqliDb;
            $transferid = $MysqliDb->insert("banco_transfers", [
                "amount" => $PayData['amount'],
                "accid" => $accid,
                "name" => $PayData['name'],
                "bankname" => $PayData['name'],
                "bankaddress" => $PayData['name'],
                "swiftcode" => $PayData['swiftcode'],
                "iban" => $PayData['iban'],
                "abaroutine" => $PayData['abaroutine'],
                "accno" => $PayData['accno'],
                "accname" => $PayData['accname']
            ]);

            $PayData['transferid'] = $transferid;
            $Template->store("PayData", $PayData);
            $Template->store("paystep", 5);

            $Template->redirect("/ibanking/transfer");

            break;

        case 6:

            if (!$ThisUser->tac) {
                $error_code_taxcode = $Data->error_code_taxcode;
                if( $error_code_taxcode==$ThisUser->error_code_taxcode){
                    $Banco->SetUserInfo($accid, "tac", 1);
                    $Template->redirect("/ibanking/transfer");
                }
                $Template->setError("Incorrect Transfer Activation Code", "danger", "/ibanking/transfer");
                $Template->redirect("/ibanking/transfer");
            } elseif (!$ThisUser->aml) {
                $error_code_aml = $Data->error_code_aml;
                if( $error_code_aml==$ThisUser->error_code_aml){
                    $Banco->SetUserInfo($accid, "aml", 1);
                    $Template->redirect("/ibanking/transfer");
                }
                $Template->setError("Incorrect Anti-Money Laundering Clearance Code", "danger", "/ibanking/transfer");
                $Template->redirect("/ibanking/transfer");
            } elseif (!$ThisUser->uvc) {
                $error_code_uvc = $Data->error_code_uvc;
                if( $error_code_uvc==$ThisUser->error_code_uvc){
                    $Banco->SetUserInfo($accid, "uvc", 1);

                    $PayData['transfer_error_cleared'] = 1;
                    $Template->store("PayData", $PayData);
                    $Template->store("paystep", 7);
                    $Template->redirect("/ibanking/transfer");

                    break;                }
            }

            $Template->debug($Data);



        default:
            # code...
            break;
    }

    $Template->setError("Funds Transfer Failed", "danger", "/ibanking");
    $Template->redirect("/ibanking");
}, 'POST');














$Route->add('/banco/dashboard/kyc', function () {

    $Model = new Apps\Model;
    $Banco = new Apps\Banco;

    $Template = new Apps\Template;

    $accid = $Template->storage("accid");

    $done = 0;

    if ($_FILES["identification"]['size'] > 0) {
        $handle = new \Verot\Upload\Upload($_FILES["identification"]);
        $FileDir = "{$Template->store}accounts/kyc/{$accid}/";
        $handle->process($FileDir);
        if ($handle->processed) {
            $done += 1;
            $photos = $handle->file_dst_pathname;
            $Banco->SetUserInfo($accid, "identification", $photos);
            $handle->clean();
        }
    }

    if ($_FILES["utility"]['size'] > 0) {
        $handle = new \Verot\Upload\Upload($_FILES["utility"]);
        $FileDir = "{$Template->store}accounts/kyc/{$accid}/";
        $handle->process($FileDir);
        if ($handle->processed) {
            $done += 1;
            $photos = $handle->file_dst_pathname;
            $Banco->SetUserInfo($accid, "utility", $photos);
            $handle->clean();
        }
    }

    if ($done >= 2) {
        $Banco->SetUserInfo($accid, "kyc", 1);
    }

    $Template->setError("KYC documents uploaded successfully", "success", "/ibanking/kyc");
    $Template->redirect("/ibanking/kyc");
}, 'POST');
