<?php
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
require_once('../includes/functions.include.php');

// Open the database connection
$pdo = newDBConnection();

echoHeader("Admin - LoLarchive");
?>
<div class="row-fluid">
	<div class="span12 well">
		<h1>Admin page</h1>
		Authenticated as <?php echo $_SERVER['PHP_AUTH_USER']." with IP adress ".$_SERVER['REMOTE_ADDR'];?>
	</div>
</div>
<div class="row-fluid">
	<div class="span6 well">
		<h3>Track a new summoner</h3>
		<form class="form-inline" method="post">
			<select id="region" name="region" class="input-small">
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
			<input type="text" id="name" name="name" class="input-medium" placeholder="Name" maxlength="16">
			<button type="submit" class="btn btn-primary">Add</button>
		</form>
		<h3>Tracked summoners</h3>
		<div class="scrollableTableContainer">
			<?php
			$trackedPlayers = getTrackedPlayers($pdo);
			echo "<table id='trackedSummoners' class='table scrollable-table table-hover'><thead class='fixedHeader'><tr class='info'>".arrayToCells(array("region", "name", "summonerId", "Action"), True)."</tr></thead><tbody class='scrollContent'>";
			foreach ($trackedPlayers as $player) {
				$player['name'] = "<a href='/index.php?page=player&region=".$player['region']."&id=".$player['summonerId']."'>".$player['name']."</a>";
				echo "<tr>".arrayToCells($player)."<td><button class='button btn-danger btn-mini'><i class='icon-remove'></button></td></tr>";
			}
			echo "</table>";
			//echo arrayToTable(getTrackedPlayers($pdo), "table-striped", array("region", "name", "summonerId", "Action"));
			?>
		</div>

	</div>
	<div class="span6 well">
		<h3>Change admin password</h3>
		<form class="form-inline" method="post">
			New password <input type="password" id="newPass" name="newPass" class="input-medium" maxlength="32">
			<button type="submit" class="btn btn-primary">Change</button>
		</form>
	</div>
</div>
<div class="row-fluid">
	<div class="span12 well">
		<h3>Logs</h3>
		<ul id="logsTab" class="nav nav-tabs" data-tabs="logsTab">
			<li class="active"><a href="#access" data-toggle="tab">Access</a></li>
			<li><a href="#errors" data-toggle="tab">Errors</a></li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="access">
				<pre class="pre-scrollable">
					<?php
						echo read_backward_line(LOCAL."private/logs/access.log", 100, True);
					?>
				</pre>
			</div>
			<div class="tab-pane" id="errors">
				<pre class="pre-scrollable">
					<?php
						echo read_backward_line(LOCAL."private/logs/error.log", 100, True);
					?>
				</pre>
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