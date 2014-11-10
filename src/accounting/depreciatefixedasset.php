<?php

require_once("common.php");

function main()
{
	$fixedAssetID = get("id");
	doAccountingFixedAsset($fixedAssetID);
	
	$check = function($condition, $error) use($fixedAssetID) {
		if(!$condition) die(page(makeHeader(_("Depreciate fixed asset"), fixedAssetBreadcrumbs($fixedAssetID), crumbs(_("Depreciate fixed asset"), "depreciatefixedasset.php?id=$fixedAssetID")) . depreciateFixedAssetForm($fixedAssetID, $error, $_POST)));
	};
	
	$check(($until = parseDate(post("until"))) !== null, _("Invalid until date."));
	$check(post("confirm") !== null, null);
	
	accountingDepreciateFixedAsset($fixedAssetID, $until);
	
	redirect("accounting/fixedasset.php?id=$fixedAssetID");
}

main();

?>