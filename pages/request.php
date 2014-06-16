<?php

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
	
echoHeader();

if (isset($_POST["nameToTrack"]) AND $_POST["nameToTrack"] != "" AND isset($_POST["regionToTrack"]) AND $_POST["regionToTrack"] != "") {
	$nameToTrack = $_POST["nameToTrack"];
	$regionToTrack = strtolower($_POST["regionToTrack"]);
	$c = curl_init();
	$result = trackNewPlayer($pdo, $c, $regionToTrack, $nameToTrack, False, False);
	curl_close($c);
	if ($result == 1) {
		$message = "Summoner ".purify($nameToTrack)." [".purify($regionToTrack)."] has been requested to be tracked";
		echo HTMLsuccess("Added", $message);
	} elseif ($result == 2) {
		$message = "Summoner ".purify($nameToTrack)." [".purify($regionToTrack)."] is already tracked (or requested).";
		echo HTMLinfo("Already tracked", $message);
	} elseif ($result == 0) {
		$message = "Summoner with name ".purify($nameToTrack)." [".purify($regionToTrack)."] has not been found.";
		echo HTMLerror("Not found", $message);
	}
}

?>

<div class="row">
	<div class="span12">
		<form class="form-horizontal well toProcess" action="<?php echo PATH;?>index.php" method="post" onsubmit="processGet()">
			<fieldset>
				<legend>Request to track a summoner</legend>
				<input type="hidden" name="page" value="request" class="get"/> 
				<div class="control-group">
					<label class="control-label">Region</label>
					<div class="controls">
						<select id="region" name="regionToTrack" class="input-small">
							<option value="EUW">EUW</option>
							<option value="EUNE">EUNE</option>
							<option value="NA">NA</option>
							<option value="BR">BR</option>
							<option value="TR">TR</option>
							<option value="RU">RU</option>
							<option value="LAN">LAN</option>
							<option value="LAS">LAS</option>
							<option value="OCE">OCE</option>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">Summoner name</label>
					<div class="controls">
						<input type="text" id="name" name="nameToTrack" class="input-medium" maxlength="16">
					</div>
				</div>
				<div class="form-actions">
					<div class="controls">
						<button type="submit" class="btn btn-primary"><i class="icon-search icon-white"></i> Request</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>


<?
echoFooter();
?>