<?php
require_once('../includes/prepend.inc.php');

if ($_GET['menu_id']) {
	if ($_POST && is_numeric($_POST['user_account_id'])) {
		if (QApplication::$TracmorSettings->PortablePinRequired && $_POST['portable_user_pin']) {
			
			QApplication::Redirect('./asset_move.php');
			exit;
			// This is just to make the example work, and obviously needs to be removed and changed.
			
			$intUserAccountId = $_POST['user_account_id'];
			$strPortableUserPin = $_POST['portable_user_pin'];
			
			// $objUserAccount = UserAccount::LoadByUserAccountIdPortableUserPin($intUserAccountId, $strPortableUserPin);
			// This method needs to be added to UserAccount.class.php
			if (!$objUserAccount) {
				// error
			}
			else {
				// Authenticate user and redirect to proper transaction page based on menu_id
				/*
				switch ($_POST['menu_id']) {
					case 1:
						QApplication::Redirect('./assets_move.php');
						break;
					case 2:
						QApplication::Redirect('./assets_checkout.php');
						break;
					case 3:
						QApplication::Redirect('./assets_checkin.php');
						break;
					case 4:
						QApplication::Redirect('./assets_receive.php');
						break;
					case 5:
						QApplication::Redirect('./assets_inventory.php');
						break;
					case 6:
						QApplication::Redirect('./inventory_move.php');
						break;
					case 7:
						QApplication::Redirect('./inventory_takeout.php');
						break;
					case 8:
						QApplication::Redirect('./inventory_restock.php');
						break;
					case 9:
						QApplication::Redirect('./inventory_inventory.php');
						break;
				}
				*/
			}
		}
	}
	
}
else {
	QApplication::Redirect('./index.php');
}

?>

<html>
<head>
<title>Tracmor Portable Interface - Authenticate</title>
<link rel="stylesheet" type="text/css" href="/css/portable.css">
</head>
<body onload="document.main_form.user_account_id.focus();">

<h1>TRACMOR PORTABLE INTERFACE</h1>
<h3>Authenticate</h3>

<form method="post" name="main_form">
User ID: <input type="text" name="user_account_id" size="4"><br />
User PIN: <input type="text" name="portable_user_pin" size="10"><br />
<input type="submit" value="Authenticate">
</form>

</body>
</html>