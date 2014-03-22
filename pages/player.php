<?php
	
	$champsFolder = PATH."img/champions/";

	if (isset($_GET["name"])) { $Iname = $_GET["name"];}
	if (isset($_GET["id"])) { $Iid = $_GET/*27*/["id"];}
	if (isset($_GET["region"])) { $Iregion = $_GET["region"];}
	
	/* If we have recieved valid arguments */
	if ( (isset($Iname) OR isset($Iid)) AND isset($Iregion)) {
		$region = strtolower($Iregion); // region is always lowercase.
		$requestString = array(); // array of all String of requests we'll make.
		$result = array(); // array of all results of requests we'll malke.
		
		// First request is to select the right user
		$requestString[0] = "SELECT id, user, region FROM users WHERE ";
		$conditions = array ("region" => $region);
		
		// If the id is provided, go for it
		if (isset($Iid)) {
			$conditions["id"] = $Iid;
			$id = $Iid;
			$requestString[0] .= conditions($conditions); 
			$findSummoner = $pdo->prepare($requestString[0]);
			$findSummoner->bindParam(":id", $Iid);
		// Else, look for the username
		} else {
			$name = $Iname;
			$requestString[0] .= "LOWER(user) = LOWER(:user) AND region = :region";
			$findSummoner = $pdo->prepare($requestString[0]);
			$findSummoner->bindParam(":user", $Iname);
		}
		$findSummoner->bindParam(":region", $region);
		
		//   START Debug
		if (isset($_GET['debug'])) {
			echo "REQUEST: ".$requestString[0];
		} // END Debug
	
		$findSummoner->execute();        // Execute the request
		$foundSummoner = $findSummoner->fetch(); // Get the array
		
		// If there is an user
		if (count($foundSummoner) > 0) {
			//   START Debug
			if (isset($_GET['debug'])) {
				echo "<br>ARRAY:<pre>";
				print_r($foundSummoner);
				echo "</pre>";
			} // END Debug
			$id = $foundSummoner['id'];
			$name = $foundSummoner['user'];
			$region = $foundSummoner['region'];
			
			$filtersStr = array(); // sexy String of all filters conditions
			
			// START FILTERS
			
			$filters = array( // Array of activated filters
						'fChampion' => false,
						'fMode' => false
			);
			
			// filter games by Champion
			if (isset($_GET['fChampion']) AND $_GET['fChampion'] != '') {
				$filters['fChampion'] = $_GET['fChampion'];
				$championFilterStr = " AND players.championId = :championId";
				$filtersStr[] = $championFilterStr;
			}
			
			// filter games by Game Mode
			if (isset($_GET['fMode']) AND $_GET['fMode'] != '') {
				$filters['fMode'] = $_GET['fMode'];
				$modeFilterStr = " AND games.subType = :typeStr";
				$filtersStr[] = $modeFilterStr;
			}
			
			$conditions = "
			FROM games 
			LEFT JOIN players ON games.gameId = players.gameId
			LEFT JOIN data ON games.gameId = data.gameId AND players.gameId = data.gameId
			WHERE players.summonerId = :sId AND data.summonerId = :sId".implode($filtersStr);
			
			$statsString = "SELECT count(*) AS nbGames, avg(data.championsKilled) AS k, avg(data.numDeaths) AS d, avg(data.assists) AS a,
			avg(data.minionsKilled) AS minions, avg(data.goldEarned) AS gold, avg(data.timePlayed) AS duration".$conditions;
			
			$wonGamesString = "SELECT count(*) AS nb".$conditions." AND data.win = (1);";
			
			$requestString[1] = "SELECT * ".$conditions." ORDER BY `games`.`createDate` DESC;";

			if (isset($_GET['debug'])) {
				echo $requestString[1];
			}
			// New request : All games of this user
			$summonerGames = $pdo->prepare($requestString[1]);
			$summonerGames->bindParam(":sId", $id);
			// Stats requests
			$stats = $pdo->prepare($statsString);
			$stats->bindParam(":sId", $id);
			$wonGames = $pdo->prepare($wonGamesString);
			$wonGames->bindParam(":sId", $id);
			
			// Check if each filter is activated
			
			// filter games by Champion
			if ($filters['fChampion']) {
				$summonerGames->bindParam(":championId", intval($filters['fChampion']));
				$stats->bindParam(":championId", intval($filters['fChampion']));
				$wonGames->bindParam(":championId", intval($filters['fChampion']));
			}
			// filter games by Mode
			if ($filters['fMode']) {
				$summonerGames->bindParam(":typeStr", $filters['fMode']);
				$stats->bindParam(":typeStr", $filters['fMode']);
				$wonGames->bindParam(":typeStr", $filters['fMode']);
			}
			
			$summonerGames->execute();        // Execute the request
			$stats->execute();        // Execute the request
			$wonGames->execute();        // Execute the request

			echoHeader($name." [".strtoupper($region)."] - LoLarchive");
		} else {
			echoHeader("Summoner not found - LoLarchive");
		}
	}
	
	// We want infos about all champions. This request will never change
	$requestString[2] = "SELECT * FROM champions ORDER BY name ASC;";
	$championsRequest = $pdo->prepare($requestString[2]);
	$championsRequest->execute(); // Execute the request
	
	$champsId = array();
	$champsDisplay = array();
	while ($champ = $championsRequest->fetch()) {
		$champsId[$champ['id']] = $champ['id'];
		$champsDisplay[$champ['id']] = $champ['display'];
		$champsName[$champ['id']] = $champ['name'];
	}

echoHeader($name." [".strtoupper($region)."] - LoLarchive");
?>


<div class="row">
	<div class="span12">
		<div class="well">
			<?php 
			if (isset($rName[$region])) {
				$regionTxt = $rName[$region];} else {$regionTxt = $region; } ?>
			<h2><?php echo htmlentities(utf8_decode($name));?> <a href="http://www.lolking.net/summoner/<? echo $region."/".$id; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a></h2>
			<?php echo htmlentities($regionTxt);?>
			<br><?php echo htmlentities($id);?>
		</div>
	</div>
</div>

<div class="row">
	<div class="span12">
		<div class="well">
			<legend>Statistics</legend>
			<?php
			$finalStats = $stats->fetch();
			$nbWon = $wonGames->fetch();
			echo $nbWon['nb']." wins / ".$finalStats['nbGames']." games (".round($nbWon['nb']/$finalStats['nbGames']*100, 2)."% win)";
			echo "<br>Average KDA; ".round($finalStats['k'], 1)." / ".round($finalStats['d'], 1)." / ".round($finalStats['a'], 1);
			echo "<br>Rate; ".round(($finalStats['k']+$finalStats['a'])/$finalStats['d'], 2)." : 1";
			?>
		</div>
	</div>
</div>

<div class="row">
	<div class="span12">
		<form class="form-horizontal well" action="index.php" method="get">
			<fieldset>
				<legend>Filter games</legend>
				<input type="hidden" name="page" value="player"/>
				<input type="hidden" name="region" value="<?php echo $region?>"/>
				<input type="hidden" name="name" value="<?php echo $name?>"/>
				<input type="hidden" name="id" value="<?php echo $id?>"/> 
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="champFilterBox" <?php echo (isset($filters['fChampion']) && $filters['fChampion'])?'checked="yes"':''?>> Champion</label>
					</label>
					<div class="controls">
						<select id="champFilterChoice" name="fChampion" class="input-medium">
							<?php
							foreach ($champsId as $value) {
								?>
								<option value="<?php echo $value;?>" style="background: url('<?php echo PATH;?>img/champions/<?php echo $champsName[$value];?>.png') no-repeat;" <?php echo (isset($filters['fChampion']) && $filters['fChampion'] == $value)?"selected":"";?>>
								<?php echo $champsDisplay[$value];?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="modeFilterBox" <?php echo ($filters['fMode'])?'checked="yes"':''?>> Game mode</label>
					</label>
					<div class="controls">
						<select id="modeFilterChoice" name="fMode" class="input-medium">
							<?
							foreach ($modes as $key => $value) {
								?>
								<option value="<?php echo $key;?>" <?php echo ($filters['fMode'] == $key)?"selected":"";?>>
								<? echo $value;?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="dateFilterBox" <?php echo ($filters['fDate'])?'checked="yes"':''?>> Date range</label>
					</label>
					<div class="controls">
						<div class="input-daterange" id="datepicker">
							<input type="text" class="input-small" name="start" id="date1"/>
							<span class="add-on">to</span>
							<input type="text" class="input-small" name="end" id="date2" value="<?php echo date('j-n-Y'); ?>"/>
						</div>
					</div>
				</div>
				<div class="form-actions">
					<div class="controls">
						<button type="submit" class="btn btn-primary">Filter</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>
		
<?php
/*
	FOR EACH GAME
*/
while ($row = $summonerGames->fetch()) {

	//   START Debug
	if (isset($_GET['debug'])) {
		echo "<br>ARRAY:<pre>";
		print_r($row);
		echo "</pre>";
	} // END Debug
	
	// Handles all bit(1) data
	$win = ord($row['win']);
	$invalid = ord($row['invalid']);
	
	if ($win == 1) {
		$class = " winmatch";
		$classtext = " wintext";
		$text = "Win";
	} else {
		$class = " lossmatch";
		$classtext = " losstext";
		$text = "Loss";
	}
	
	/* Request to find all summoners in a game */
	$requestString[3] = "
	SELECT * FROM players
	LEFT JOIN users ON users.id = players.summonerId
	WHERE players.gameId = :gId";
	$playersRequest = $pdo->prepare($requestString[3]);
	$playersRequest->bindParam(":gId", $row['gameId']);
	$playersRequest->execute(); // Execute the request
	
	// Put each player on the right team
	$summonersTeam = $row['teamId'];
	$teamL = array();
	$teamR = array();
	while ($player = $playersRequest->fetch()) {
		if ($player['teamId'] == $summonersTeam) {
			$teamL[] = $player;
		} else {
			$teamR[] = $player;
		}
	}

	$duration = $row['timePlayed'];
	
	$year = substr($row['createDate'], 0, 4);
	$month = substr($row['createDate'], 5, 2);
	$day = substr($row['createDate'], 8, 2);
	$hour = substr($row['createDate'], 11, 2);
	$min = substr($row['createDate'], 14, 2);
	$time = $day.".".$month.".".$year." ".$hour.":".$min;
	
	$inventory = array($row['item0'], $row['item1'], $row['item2'], 
		$row['item3'], $row['item4'], $row['item5'], $row['item6']);

?>
	<div class="row">
		<div class="span12">
		
			<div class="well<?php echo $class;?> match" id="<?php echo $row['gameId'];?>">
		
				<div class="matchcell championcell"><?php echo HTMLchampionImg($row['championId'], "big", $champsName); ?></div>
				
				<div class="matchcell headcell">							
					<?php echo HTMLgeneralStats($modes[$row['subType']], $text, $duration, $time);?>
				</div>
				
				<?php
				if (isset($row['spell1']))
				{
				?>
					<div class="matchcell kdacell">
						<?php
						echo HTMLkda($row['championsKilled'], $row['numDeaths'],
							$row['assists'], $row['minionsKilled'], $row['goldEarned']) ?>
					</div>
					
					<div class="matchcell sscell">
						<?php echo HTMLsummonerSpells($row['spell1'], $row['spell2']) ?>
					</div>
					
					<div class="matchcell itemscell">
						<table>
							<?php 
							echo HTMLinventory($inventory); ?>
						</table>
					</div>
				<?php
				} else {
				?>
					<div class="matchcell nodatacell">
						No data.
					</div>
				<?php
				}
				?>
				<div class="matchcell playerscell">
					<?php echo HTMLparticipants($row['region'], $teamL, $teamR, $champsName); ?>
				</div>
			</div>
		</div>
	</div>
<?php
} 
echoFooter();?>