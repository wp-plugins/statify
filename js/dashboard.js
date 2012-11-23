google.load(
	'visualization',
	1,
	{
		packages: ['corechart']
	}
);


google.setOnLoadCallback(
	function() {
		/* Init */
		var id,
			rows = [],
			chart = {},
			output = jQuery('#statify_chart'),
			data = new google.visualization.DataTable();
		
		/* Leer? */
		if ( !statify ) {
			return;
		}

		/* Loopen */
		for (id in statify) {
			rows[id] = [statify[id].date, parseInt(statify[id].count)];
		}
		
		/* Spalten */
		data.addColumn('string', 'Datum');
		data.addColumn('number', 'Aufrufe');
		data.addRows(rows);
		
		/* Chart */
		chart = new google.visualization.AreaChart(output.get(0));
	  	
	  	/* Zeichnen */
	  	chart.draw(
	  		data,
	  		{
				'width': output.parent().width(),
				'height': 120,
				'legend': 'none',
				'colors': ['#3399CC'],
				'pointSize': 6,
				'lineWidth': 3,
				'gridlineColor': '#ececec',
				'backgroundColor': 'transparent',
				'reverseCategories': true,
				'vAxis': {
					'textPosition': 'in',
					'baselineColor': 'transparent',
					'textStyle': {
						'color': '#8F8F8F',
						'fontSize': 10
					}
				},
				'chartArea': {
					'width': "100%",
					'height': "100%"
				}
			}
		);
	}
);