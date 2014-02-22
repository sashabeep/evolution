<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_document')) {	
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id=$_REQUEST['id'];

/************ webber ********/
$content=$modx->db->getRow($modx->db->select('parent, pagetitle', $modx->getFullTableName('site_content'), "id='{$id}'"));
$pid=($content['parent']==0?$id:$content['parent']);

/************** webber *************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;

/***********************************/


// check permissions on the document
include_once MODX_MANAGER_PATH . "processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
	$modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

// get the timestamp on which the document was deleted.
$sql = "SELECT deletedon FROM $dbase.`".$table_prefix."site_content` WHERE $dbase.`".$table_prefix."site_content`.id=".$id." AND deleted=1;";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit!=1) {
	$modx->webAlertAndQuit("Couldn't find document to determine it's date of deletion!");
} else {
	$row=$modx->db->getRow($rs);
	$deltime = $row['deletedon'];
}

$children = array();

function getChildren($parent) {
	
	global $modx,$dbase;
	global $table_prefix;
	global $children;
	global $deltime;
	
	$db->debug = true;
	
	$sql = "SELECT id FROM $dbase.`".$table_prefix."site_content` WHERE $dbase.`".$table_prefix."site_content`.parent=".$parent." AND deleted=1 AND deletedon=$deltime;";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	if($limit>0) {
		// the document has children documents, we'll need to delete those too
		while ($row=$modx->db->getRow($rs)) {
			$children[] = $row['id'];
			getChildren($row['id']);
			//echo "Found childNode of parentNode $parent: ".$row['id']."<br />";
		}
	}
}

getChildren($id);

if(count($children)>0) {
	$docs_to_undelete = implode(" ,", $children);
	$sql = "UPDATE $dbase.`".$table_prefix."site_content` SET deleted=0, deletedby=0, deletedon=0 WHERE id IN($docs_to_undelete);";
	$modx->db->query($sql);
}
//'undelete' the document.
$sql = "UPDATE $dbase.`".$table_prefix."site_content` SET deleted=0, deletedby=0, deletedon=0 WHERE id=$id;";
$modx->db->query($sql);
	// Set the item name for logger
	$_SESSION['itemname'] = $content['pagetitle'];

	// empty cache
	$modx->clearCache('full');
	// finished emptying cache - redirect
	//$header="Location: index.php?r=1&a=7&id=$id&dv=1";

// webber
	$header="Location: index.php?r=1&a=7&id=$pid&dv=1".$add_path;
	header($header);
?>