<?php

require_once("common.php");

function main()
{
	$fixedAssetID = get("id");
	doAccountingFixedAsset($fixedAssetID);
	$fixedAsset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("name", "performedDepreciations", "totalDepreciations", "automaticDepreciation"));
	$content = makeHeader("Fixed asset {$fixedAsset["name"]}", fixedAssetBreadcrumbs($fixedAssetID));
	
	$content .= fixedAssetSummary($fixedAssetID);
	if(($fixedAsset["performedDepreciations"] < $fixedAsset["totalDepreciations"]) && !$fixedAsset["automaticDepreciation"]) {
		$content .= depreciateFixedAssetForm($fixedAssetID);
	}
	$content .= editFixedAssetForm($fixedAssetID);
	if(fixedAssetEmpty($fixedAssetID)) {
		$content .= deleteFixedAssetForm($fixedAssetID);
	}
	echo page($content);
}

main();

?>