function showTerritoriesForYear(year) {
	ajaxPost(
		{ getTerritoriesForYear: true, year: year },
		function(error, data) {
			alert(data.count+' territories are located for this period');
		}
	);
}