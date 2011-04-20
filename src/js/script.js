$(document).ready(function() {
	// TODO: sorteren op de .sorted colom
	$(".sortable table").tablesorter({widgets: ['zebra']});
	$(".tree table").treeTable({zebra: true});
});
