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
    format: "d-m-yyyy",
    weekStart: 1,
    endDate: today,
    todayBtn: "linked",
    todayHighlight: true
});

$("#access :first-child").scrollTop($("#access :first-child")[0].scrollHeight);
// Yeah, because I can't touch the scroll of hidden divs, I have to first activate them,
// then change the scroll and finally desactivate them...
$("#errors").addClass("active");
$("#errors :first-child").scrollTop($("#errors :first-child")[0].scrollHeight);
$("#errors").removeClass("active");
$("#admin").addClass("active");
$("#admin :first-child").scrollTop($("#admin :first-child")[0].scrollHeight);
$("#admin").removeClass("active");

$("#logsTab").tab();