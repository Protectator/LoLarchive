if ($('#champFilterBox').is (':checked'))
	{
		$("#champFilterChoice").prop('disabled', false);
	} else {
		$("#champFilterChoice").prop('disabled', true);
	}


if ($('#modeFilterBox').is (':checked'))
	{
		$("#modeFilterChoice").prop('disabled', false);
	} else {
		$("#modeFilterChoice").prop('disabled', true);
	}

if ($('#dateFilterBox').is (':checked'))
	{
		$("#date1").prop('disabled', false);
		$("#date2").prop('disabled', false);
		$("#datepicker").removeClass("disabled");
	} else {
		$("#date1").prop('disabled', true);
		$("#date2").prop('disabled', true);
		$("#datepicker").addClass("disabled");
	}


$('#champFilterBox').click (function ()
{
	var thisCheck = $(this);
	if (thisCheck.is (':checked'))
	{
		$("#champFilterChoice").prop('disabled', false);
	} else {
		$("#champFilterChoice").prop('disabled', true);
	}
});

$('#modeFilterBox').click (function ()
{
	var thisCheck = $(this);
	if (thisCheck.is (':checked'))
	{
		$("#modeFilterChoice").prop('disabled', false);
	} else {
		$("#modeFilterChoice").prop('disabled', true);
	}
});

$('#dateFilterBox').click (function ()
{
	var thisCheck = $(this);
	if (thisCheck.is (':checked'))
	{
		$("#date1").prop('disabled', false);
		$("#date2").prop('disabled', false);
		$("#datepicker").removeClass("disabled");
	} else {
		$("#date1").prop('disabled', true);
		$("#date2").prop('disabled', true);
		$("#datepicker").addClass("disabled");
	}
});

$('.input-daterange').datepicker({
    format: "d/m/yyyy",
    weekStart: 1,
    endDate: today,
    todayBtn: "linked",
    todayHighlight: true
});