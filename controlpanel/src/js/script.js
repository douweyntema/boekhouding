$(document).ready(function() {
	$.tablesorter.addParser({
		id: "index",
		is: function(s) {
			return /^\#[0-9]+/.test(s);
		}, format: function(s) {
			return $.tablesorter.formatInt(s.substr(1));
		}, type: "numeric"
	});
	// TODO: sorteren op de .sorted colom
	$(".sortable table").tablesorter({widgets: ['zebra']});
	$(".tree table").treeTable({zebra: true, initialState: "expanded"});
	$(".list:not(.tree, .sortable) table").each(zebra);
	setupAutoCollapse();
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

function setupAutoCollapse()
{
	$("input[type=radio][id]").change(function() {
		$("input[type=radio][name=" + $(this).attr("name") + "]").each(function() {
			checked = $(this).attr("checked") != undefined;
			$(".if-selected-" + $(this).attr("id")).toggle(checked);
		});
	}).change();
}
