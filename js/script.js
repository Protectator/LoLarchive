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