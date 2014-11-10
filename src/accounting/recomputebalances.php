<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader(_("Recompute balances"), accountingBreadcrumbs(), crumbs(_("Recompute balances"), "recomputebalances.php")) . recomputeBalancesForm($error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	accountingRecomputeBalances();
	
	redirect("accounting/index.php");
}

main();

?>