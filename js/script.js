/*	LoLarchive - Website to keep track of your games in League of Legends
    Copyright (C) 2013-2014  Kewin Dousse (Protectator)

    This file is part of LoLarchive.

    LoLarchive is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or any later version.

    LoLarchive is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Contact : kewin.d@websud.ch
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