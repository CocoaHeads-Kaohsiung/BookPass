<?php
if(isset($_POST['name'])){
	
	//setlocale(LC_MONETARY, 'en_US');
	require('../PassBook.php');
		
	// Variables
	$id = rand(100000,999999) . '-' . rand(100,999) . '-' . rand(100,999); // Every card should have a unique serialNumber
	$balance = 'NT$'.rand(0,3000);
	$name = $_POST['name'];
	
	
	// Create pass
	$pass = new PassBook(); 

	$pass->setCertificate('[your pass type cert file .p12 file]'); // Set the path to your Pass Certificate (.p12 file)
	$pass->setCertificatePassword('[your cert password]'); // Set password for certificate
	$pass->setAppleWWDRcertPath('[Apple WWDR Certificate .pem file]');
	$pass->setJSON('{ 
	"passTypeIdentifier": "pass.org.mopcon.events.2012",
	"formatVersion": 1,
	"organizationName": "Starbucks",
	"teamIdentifier": "JA387Z4D7Q",
	"serialNumber": "'.$id.'",
    "backgroundColor": "rgb(240,240,240)",
	"logoText": "Starbucks",
	"description": "Demo pass",
	"storeCard": {
        "secondaryFields": [
            {
                "key": "balance",
                "label": "賬戶餘額",
                "value": "'.$balance.'"
            },
            {
                "key": "name",
                "label": "用戶名稱",
                "value": "'.$name.'"
            }

        ],
        "backFields": [
            {
                "key": "id",
                "label": "Card Number",
                "value": "'.$id.'"
            }
        ]
    },
    "barcode": {
        "format": "PKBarcodeFormatPDF417",
        "message": "'.$id.'",
        "messageEncoding": "iso-8859-1",
        "altText": "'.$id.'"
    }
    }');

    $pass->addFile('icon.png');
    $pass->addFile('icon@2x.png');
    $pass->addFile('logo.png');
    $pass->addFile('background.png');

    $pass->create(true); // Create and output the PassBook
    exit;
	
}else{
	?>
<!DOCTYPE html>
<html lang="zh-TW">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">    
    <title>PassBook Generate</title>
    <link href="../bootstrap/css/bootstrap.min.css" media="screen" rel="stylesheet">
<style>
				.header { background-color: #CCC; padding-top: 30px; padding-bottom: 30px; margin-bottom: 32px; text-align: center; }
				.logo { width: 84px; height: 84px; margin-bottom: 20px; }
				.title { color: black; font-size: 22px; text-shadow: 1px 1px 1px rgba(0,0,0,0.1); font-weight: bold; display: block; text-align: center; }
				.userinfo { margin: 0px auto; padding-bottom: 32px; width: 280px;}
				form.form-stacked { padding: 0px;}
				legend { text-align: center; padding-bottom: 20px; clear: both;}
				input.xlarge { width: 280px; height: 26px; line-height: 26px;}
			</style>  </head>
  <body>
<?php
  require_once('../navigator.html');
?>
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="../bootstrap/js/bootstrap.min.js"></script>
			<div class="header">
				<img class="logo" src="../images/logo_web.png" />
				<span class="title">Starbucks</span>
			</div>
			<div class="userinfo">
				<form action="starbucks.php" method="post" class="form-stacked">
            <fieldset>
                <legend style="padding-left: 0px;">請輸入您的暱稱</legend>
                                                
                <div class="clearfix">
                	<label style="text-align:left">暱稱：</label>
                	<div class="input">
                		<input class="xlarge" name="name" type="text" value="我的暱稱" />
                	</div>
                </div>
                                
                <br /><br />
                <center><input type="submit" class="btn primary" value=" 建立 PassBook &gt; " /></center>
            </fieldset>
        </form>

			</div>
    
  </body>
</html>
	<?
} 
?>