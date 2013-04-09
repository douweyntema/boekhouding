<?php

require_once("common.php");

function main()
{
	doAccounting();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add account", accountingBreadcrumbs(), crumbs("Recompute balances", "recomputebalances.php")) . recomputeBalancesForm($error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	accountingRecomputeBalances();
	
	redirect("accounting/index.php");
}

main();

?>