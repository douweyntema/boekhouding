<?php

require_once("common.php");

function main()
{
	$fixedAssetID = get("id");
	doAccountingFixedAsset($fixedAssetID);
	
	$check = function($condition, $error) use($fixedAssetID) {
		if(!$condition) die(page(makeHeader(_("Delete fixed asset"), fixedAssetBreadcrumbs($fixedAssetID), crumbs(_("Delete fixed asset"), "editfixedasset.php?id=$fixedAssetID")) . deleteFixedAssetForm($fixedAssetID, $error, $_POST)));
	};
	
	$check(fixedAssetEmpty($fixedAssetID), _("Fixed asset is still in use."));
	$check(post("confirm") !== null, null);
	
	$fixedAsset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("accountID", "depreciationAccountID", "expenseAccountID"));
	startTransaction();
	stdDel("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID));
	accountingDeleteAccount($fixedAsset["accountID"]);
	accountingDeleteAccount($fixedAsset["depreciationAccountID"]);
	accountingDeleteAccount($fixedAsset["expenseAccountID"]);
	commitTransaction();
	
	redirect("accounting/index.php");
}

main();

?>