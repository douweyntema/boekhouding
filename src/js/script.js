$(document).ready(function() {
	// TODO: sorteren op de .sorted colom
	$(".sortable table").tablesorter({widgets: ['zebra']});
	$(".tree table").treeTable({zebra: true, initialState: "expanded"});
	$(".list:not(.tree, .sortable) table").each(zebra);
});

function zebra()
{
	counter = 1;
	$(this).find("tbody tr").each(function() {
		if(counter % 2 == 0) {
			$(this).addClass("even").removeClass("odd");
		} else {
			$(this).addClass("odd").removeClass("even");
		}
		counter++;
	});
}
