(function() {
	
	var sttds = $("#simpletable td");

	sttds.each(function() {
		var tdtext = $(this).text();
		var tdtext = numberWithCommas(tdtext);
		var tdtext = "$ " + tdtext;
		$(this).text(tdtext);
	});


	function numberWithCommas(number) {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");	// https://stackoverflow.com/a/2901298/2523144
	}

})();