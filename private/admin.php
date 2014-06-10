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

// Counting the time required to create the page
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$startTime = $mtime;
// This page loads every other page asked
// It's the one an end-user always asks for.

// Start by loading the config file
require_once('config.php');

// Then all the useful functions
require_once(LOCAL.'includes/functions.include.php');

// Open the database connection
$pdo = newDBConnection();

echoHeader("Admin - LoLarchive");
$nbActions = 0;
$user = $_SERVER['PHP_AUTH_USER'];
?>
<div class="row-fluid">
	<div class="span12 well">
		<h1>Admin panel</h1>
		Authenticated as <?php echo $_SERVER['PHP_AUTH_USER']." with IP adress ".$_SERVER['REMOTE_ADDR'];?>
	</div>
</div>
<?
// Treating any request
// Add summoner to track
if (isset($_POST["nameToTrack"]) AND $_POST["nameToTrack"] != "" AND isset($_POST["regionToTrack"]) AND $_POST["regionToTrack"] != "") {
	$nameToTrack = $_POST["nameToTrack"];
	$regionToTrack = strtolower($_POST["regionToTrack"]);
	$c = curl_init();
	$result = trackNewPlayer($pdo, $c, $regionToTrack, $nameToTrack);
	curl_close($c);
	if ($result == 1) {
		$message = "Summoner ".purify($nameToTrack)." has been added to tracked summoners.";
		logAdmin($user." : ".$message);
		$nbActions += 1;
		echo HTMLsuccess("Added", $message);
	} elseif ($result == 2) {
		$message = "Summoner ".purify($nameToTrack)." is already tracked.";
		echo HTMLinfo("Already tracked", $message);
	} elseif ($result == 0) {
		$message = "Summoner with name ".purify($nameToTrack)." has not been found.";
		echo HTMLerror("Not found", $message);
	}
}

// Remove tracked summoner
if (isset($_POST["idToUntrack"]) AND $_POST["idToUntrack"] != "" AND isset($_POST["regionToUntrack"]) AND $_POST["regionToUntrack"] != "") {
	$idToUntrack = $_POST["idToUntrack"];
	$untrackName = (isset($_POST['untrackName']) AND $_POST['untrackName'] != "") ? $_POST['untrackName'] : "?id?".$idToUntrack ;
	$regionToUntrack = strtolower($_POST["regionToUntrack"]);
	$c = curl_init();
	$result = untrackPlayer($pdo, $c, $regionToUntrack, $idToUntrack, True);
	curl_close($c);
	if ($result == 1) {
		$message = "Summoner ".purify($untrackName)." has been removed from tracked summoners.";
		$nbActions += 1;
		logAdmin($user." : ".$message);
		echo HTMLsuccess("Removed", $message);
	} elseif ($result == 2) {
		$message = "Summoner ".purify($untrackName)." is already not tracked.";
		echo HTMLinfo("Already not tracked", $message);
	} elseif ($result == 0) {
		$message = "Summoner with name ".purify($untrackName)." has not been found.";
		echo HTMLerror("Not found", $message);
	}
}

?>

<div class="row-fluid">
	<div class="span6 well">
		<h3>Track a new summoner</h3>
		<form class="form-inline" method="post">
			<select id="region" name="regionToTrack" class="input-small">
				<option>EUW</option>
				<option>EUNE</option>
				<option>NA</option>
				<option>BR</option>
				<option>TR</option>
				<option>RU</option>
				<option>LAN</option>
				<option>LAS</option>
				<option>OCE</option>
			</select>
			<input type="text" id="name" name="nameToTrack" class="input-medium" placeholder="Name" maxlength="16">
			<button type="submit" class="btn btn-success btn-small"><i class="icon-plus icon-white"></i> Add</button>
		</form>
		<h3>Tracked summoners</h3>
		<div class="scrollableTableContainer">
			<?php
			$trackedPlayers = getTrackedPlayers($pdo);
			echo "<table id='trackedSummoners' class='table scrollable-table table-hover'><thead class='fixedHeader'><tr class='info'>".arrayToCells(array("region", "name", "summonerId", "Action"), True)."</tr></thead><tbody class='scrollContent'>";
			foreach ($trackedPlayers as $player) {
				$realName = $player['name'];
				$player['name'] = "<a href='/index.php?page=player&region=".$player['region']."&id=".$player['summonerId']."'>".$player['name']."</a>";
				echo "<tr>".arrayToCells($player)."<td><form method='post'><input type='hidden' name='idToUntrack' value='".$player['summonerId']."'>
				<input type='hidden' name='regionToUntrack' value='".$player['region']."'>
				<input type='hidden' name='untrackName' value='".$realName."'>
				<button type='submit' class='button btn-danger btn-mini'>
				<i class='icon-remove'></i> Remove</button></form></td></tr>";
			}
			echo "</table>";
			?>
		</div>
	</div>

	<div class="span6 well">
		<h3>Configuration</h3>
		<?php
			$configInfos = array (
				"Database host" => HOST,
				"Database name" => DBNAME,
				"API key used" => preg_replace('/(\w{4})\w{4}-\w{4}-\w{4}-\w{4}-\w{8}(\w{4})/', '${1}****-****-****-****-********${2}', API_KEY),
				"Used as prod key" => (PROD_KEY) ? "<i class='icon-ok icon-white'></i>" : "<i class='icon-remove icon-white'></i>"
				);
			echo arrayToVerticalList($configInfos, "table-condensed");
		?>
		<h3>Database</h3>
		<?php
			$gamesStatsRequestString = "SELECT COUNT(*) as nbGames FROM games;";
			$trackedStatsRequestString = "SELECT COUNT(*) as nbTracked FROM usersToTrack;";
			$gamesStats = $pdo->prepare($gamesStatsRequestString);
			$trackedStats = $pdo->prepare($trackedStatsRequestString);
			$gamesStats->execute();
			$trackedStats->execute();
			$stats[0] = current($gamesStats->fetchAll(PDO::FETCH_NAMED));
			$stats[1] = current($trackedStats->fetchAll(PDO::FETCH_NAMED));
			echo $stats[1]['nbTracked']." tracked summoners<br>";
			echo number_format($stats[0]['nbGames'], 0, "", "'")." games<br>";
		?>
	</div>
</div>
<?php
if ($nbActions == 0) {
	logAdmin($user." : Accessed the Admin panel.");
}
?>
<div class="row-fluid">
	<div class="span12 well">
		<h3>Logs</h3>
		<ul id="logsTab" class="nav nav-tabs" data-tabs="logsTab">
			<li class="active"><a href="#access" data-toggle="tab">Access</a></li>
			<li><a href="#errors" data-toggle="tab">Errors</a></li>
			<li><a href="#admin" data-toggle="tab">Admin</a></li>
		</ul>
		<p>Last <?php echo ADMIN_PAGE_LOGS_NB_LINES_DISPLAYED; ?> lines of the logs :</p>
		<div class="tab-content">
			<div class="tab-pane active" id="access">
				<pre class="pre-scrollable"><?php
						echo read_backward_line(LOCAL."private/logs/access.log", ADMIN_PAGE_LOGS_NB_LINES_DISPLAYED);
					?></pre>
			</div>
			<div class="tab-pane" id="errors">
				<pre class="pre-scrollable"><?php
						echo read_backward_line(LOCAL."private/logs/error.log", ADMIN_PAGE_LOGS_NB_LINES_DISPLAYED);
					?></pre>
			</div>
			<div class="tab-pane" id="admin">
				<pre class="pre-scrollable"><?php
						echo read_backward_line(LOCAL."private/logs/admin.log", ADMIN_PAGE_LOGS_NB_LINES_DISPLAYED);
					?></pre>
			</div>
		</div>
	</div>
</div>

<?
echoFooter();

// Stop counting the time required and display it in comments
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$endTime = $mtime;
$totalTime = ($endTime - $startTime);
echo "<!-- This page was created in ".$totalTime." seconds. -->";

// Close the database connection
$pdo = null;
?>