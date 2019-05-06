$(document).ready(function() {
	$('table.tableDnD').tableDnD({
	
		onDragStart: function(table, row) {
		
			originalOrder = $.tableDnD.serialize();
			reOrder = ':even';

			if (table.tBodies[0].rows[1] && $('#' + table.tBodies[0].rows[1].id).hasClass('alt_row'))
				reOrder = ':odd';

			$('#'+table.id+ '#' + row.id).parent('tr').addClass('myDragClass');
		},
		dragHandle: 'dragHandle',
		onDragClass: 'myDragClass',
		onDrop: function(table, row) {
			var tableDrag = $('#' + table.id);
			tableDrag.find('tr').not('.nodrag').removeClass('alt_row');
			tableDrag.find('tr:not(".nodrag"):odd').addClass('alt_row');
		}
	});
})