<?php

require_once("common.php");

function main()
{
	$fixedAssetID = get("id");
	doAccountingFixedAsset($fixedAssetID);
	
	$check = function($condition, $error) use($fixedAssetID) {
		if(!$condition) die(page(makeHeader(_("Edit fixed asset"), fixedAssetBreadcrumbs($fixedAssetID), crumbs(_("Edit fixed asset"), "editfixedasset.php?id=$fixedAssetID")) . editFixedAssetForm($fixedAssetID, $error, $_POST)));
	};
	
	$check(($name = post("name")) !== null, "");
	$check(($description = post("description")) != null, "");
	
	$check($name != "", _("Missing account name."));
	$check(post("confirm") !== null, null);
	
	$fixedAsset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("accountID", "depreciationAccountID", "expenseAccountID"));
	
	startTransaction();
	accountingEditAccount($fixedAsset["accountID"], $name, $description);
	accountingEditAccount($fixedAsset["depreciationAccountID"], $name, depreciationAccountDescription($name));
	accountingEditAccount($fixedAsset["expenseAccountID"], $name, expenseAccountDescription($name));
	stdSet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("name"=>$name, "description"=>$description, "automaticDepreciation"=>post("automaticDepreciation") !== null ? 1 : 0));
	commitTransaction();
	
	redirect("accounting/fixedasset.php?id=$fixedAssetID");
}

main();

?>