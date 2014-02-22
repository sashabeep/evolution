<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id=$_REQUEST['id'];

// check the user is allowed to delete this message
$sql = "SELECT * FROM $dbase.`".$table_prefix."user_messages` WHERE $dbase.`".$table_prefix."user_messages`.id=$id";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit!=1) {
	$modx->webAlertAndQuit("Wrong number of messages returned!");
} else {
	$message=$modx->db->getRow($rs);
	if($message['recipient']!=$modx->getLoginUserID()) {
		$modx->webAlertAndQuit("You are not allowed to delete this message!");
	} else {
		// delete message
		$modx->db->delete($modx->getFullTableName('user_messages'), "id='{$id}'");
	}
}

$header = "Location: index.php?a=10";
header($header);

?>