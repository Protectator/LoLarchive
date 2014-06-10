/*	LoLarchive - Website to keeps track of your games in League of Legends
    Copyright (C) 2013-2014  Kewin Dousse (Protectator)

    This file is part of LoLarchive.

    LoLarchive is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    To contact me : kewin.d@websud.ch
    Project's repository : https://github.com/Protectator/LoLarchive
*/

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