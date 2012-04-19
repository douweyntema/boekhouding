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
	setupRepeatField();
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

function setupRepeatField()
{
	$(".repeatFieldMaster").each(function() {
		id = this.id;
		emptyLine = "<tr";
		for(var key in this.attributes) {
			if(!isNaN(key) && (this.attributes[key].name != "id")) {
				emptyLine += " " + this.attributes[key].name + "=\"" + this.attributes[key].value + "\"";
			}
		}
		emptyLine += ">";
		emptyLine += $(this).html();
		emptyLine += "</tr>";
		extraRowID = 1;
		addnewline = function() {
			$(".repeatFieldChild-" + id).last().addClass("CURRENTLASTLINE");
			$(".repeatFieldChild-" + id).last().after(emptyLine);
			$(".repeatFieldChild-" + id).last().addClass("NEWLINE");
			$(".NEWLINE [name]").each(function() {
				name = $(this).attr("name")
				pos = name.lastIndexOf("-");
				number = name.substr(pos + 1) * 1;
				newname = name.substr(0, pos) + "-" + (number + extraRowID);
				extraRowID++;
				$(this).attr("name", newname);
			});
			$(".repeatFieldChild-" + id).last().change(addnewline);
			
			$(".CURRENTLASTLINE").removeClass("CURRENTLASTLINE");
			$(".NEWLINE").removeClass("NEWLINE");
			
			$(this).unbind("change");
		};
		$(".repeatFieldChild-" + id).change(addnewline);
	});
	$(".repeatFieldRemove").remove();
}
