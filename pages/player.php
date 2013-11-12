<?php
	
	$rName = array();
	$rName['euw'] = "Europe West";
	$rName['na'] = "North America";
	$rName['eune'] = 'Europe Nordic &amp; East';
	$rName['br'] = "Brazil";
	$rName['tr'] = "Turkey";
	$rName['ru'] = "Russia";
	$rName['lan'] = "Latin America North";
	$rName['las'] = "Latin America South";
	$rName['oce'] = "Oceania";
	
	$champsFolder = PATH."img/champions/";
	
	/* If we have recieved valid arguments */
	if ((isset($_GET['name']) OR isset($_GET['id'])) AND isset($_GET['region'])) {
		$region = strtolower($_GET['region']); // region is always lowercase.
		$requestString = array(); // array of all String of requests we'll make.
		$result = array(); // array of all results of requests we'll malke.
		
		// First request is to select the right user
		$requestString[0] = "SELECT id, user FROM users WHERE ";
		$conditions = array ("region" => $region);
		
		// If the id is provided, go for it
		if (isset($_GET/*27*/['id'])) {
			$conditions["id"] = $_GET['id'];
			$requestString[0] .= conditions($conditions); 
			$findSummoner = $pdo->prepare($requestString[0]);
			$findSummoner->bindParam(":id", $_GET['id']);
		// Else, look for the username
		} else {
			$conditions["user"] = $_GET['name'];
			$requestString[0] .= conditions($conditions);
			$findSummoner = $pdo->prepare($requestString[0]);
			$findSummoner->bindParam(":user", $_GET['name']);
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
			
			$filtersStr = array();
			
			if (isset($_GET['champFilterChoice']) AND $_GET['champFilterChoice'] != '') {
				$championFilterStr = " AND players.championId = :championId";
				$filtersStr[] = $championFilterStr;
			}
			
			if (isset($_GET['modeFilterChoice']) AND $_GET['modeFilterChoice'] != '') {
				$modeFilterStr = " AND games.type = :typeStr";
				$filtersStr[] = $modeFilterStr;
			}
			
			$requestString[1] = "
			SELECT *
			FROM games 
			LEFT JOIN players ON games.gameId = players.gameId
			LEFT JOIN data ON games.gameId = data.gameId AND players.gameId = data.gameId
			WHERE players.summonerId = :sId AND data.summonerId = :sId".implode($filtersStr);
			
			/* PARTIE SUR LES FILTRES
			TODO : REFACTOR THIS SHIT :)
			// Filtre champion
			$players = Array('a1', 'a2', 'a3', 'a4', 'a5', 'b1', 'b2', 'b3', 'b4', 'b5');
			
			$req2 = "";
			$champFilter = 0;
			$modeFilter = 0;
			
			if (isset($_GET['champFilterBox']) AND isset($_GET['champFilterChoice']) AND $_GET['champFilterBox'] == 'on') {
				$champFilter = 1;
				$filterString = " AND (";
				foreach ($players as $value) {
					$filterString = $filterString."(games.".$value."id = '".$id."' AND matches.".$value."champ = '".$_GET['champFilterChoice']."')";
					if ($value != 'b5') {
						$filterString .= " OR ";
					}
				}
				$filterString .= ")";
			}
			
			// Filtre mode
			if (isset($_GET['modeFilterBox']) AND isset($_GET['modeFilterChoice']) AND $_GET['modeFilterBox'] == 'on') {
				$modeFilter = 1;
				$filterModeString = " AND matches.type = '".$_GET['modeFilterChoice']."'";
			}
			
		
			// SQL : Récupération des matches du joueur
			$req2 = "SELECT * FROM matches LEFT JOIN data ON data.host = matches.host AND data.time = matches.time AND data.region = matches.region AND data.user = '".$id."' WHERE '".$id."' IN(matches.a1id, matches.a2id, matches.a3id, matches.a4id, matches.a5id, matches.b1id, matches.b2id, matches.b3id, matches.b4id, matches.b5id)";
			
			if ($champFilter) {
				$req2 = $req2.$filterString;
			}
			if ($modeFilter) {
				$req2 = $req2.$filterModeString;
			}
			*/
			$requestString[1] .= " ORDER BY games.time DESC;";
			if (isset($_GET['debug'])) {
				echo $requestString[1];
			}
			// New request : All games of this user
			$summonerGames = $pdo->prepare($requestString[1]);
			$summonerGames->bindParam(":sId", $id);
			if (isset($_GET['champFilterChoice']) AND $_GET['champFilterChoice'] != '') {
				$summonerGames->bindParam(":championId", intval($_GET['champFilterChoice']));
			}
			$summonerGames->execute();        // Execute the request
			//$summonerGames->fetch(); // Get the array
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
?>
<div class="row">
	<div class="span12">
		<div class="well">
			<?php 
			if (isset($rName[$region])) {
				$regionTxt = $rName[$region];} else {$regionTxt = $region; } ?>
			<h2><? echo htmlentities(utf8_decode($name));?> <a href="http://www.lolking.net/summoner/<? echo $region."/".$id; ?>"><img src="<?echo PATH;?>img/lolking.png" alt="lolking"></a></h2>
			<? echo htmlentities($regionTxt);?>
			<br><? echo htmlentities($id);?>
		</div>
	</div>
</div>

<?php
/*
	FILTRES !!!
*/

if (isset($_GET['champFilterChoice']) AND $_GET['champFilterChoice'] != "") {
	$lastChampion = $_GET['champFilterChoice'];
}

if (isset($_GET['modeFilterChoice']) AND $_GET['modeFilterChoice'] != "") {
	$lastMode = $_GET['modeFilterChoice'];
}
?>

<div class="row">
	<div class="span12">
		<form class="form-horizontal well" action="http://protectator.ch/lolarchive/index.php" method="get">
			<fieldset>
				<legend>Filter games</legend>
				<input type="hidden" name="page" value="player"/>
				<input type="hidden" name="region" value="<?echo $_GET['region']?>"/>
				<input type="hidden" name="name" value="<?echo $_GET['name']?>"/> 
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="champFilterBox" name="champFilterBox" <?echo (isset($lastChampion) && $lastChampion)?'checked="yes"':''?>> Champion</label>
					</label>
					<div class="controls">
						<select id="champFilterChoice" name="champFilterChoice" class="input-medium">
							<?
							foreach ($champsId as $value) {
								?>
								<option value="<?echo $value;?>" style="background: url('<?echo PATH;?>img/champions/<?echo ucfirst($champsDisplay[$value]);?>.png') no-repeat;" <?echo (isset($lastChampion) && $lastChampion == $value)?"selected":"";?>>
								<? echo $champsDisplay[$value];?>
								</option>
								<?
							}
							?>
						</select>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="modeFilterBox" name="modeFilterBox" <?echo ($lastMode)?'checked="yes"':''?>> Game mode</label>
					</label>
					<div class="controls">
						<select id="modeFilterChoice" name="modeFilterChoice" class="input-medium">
							<?
							foreach ($modes as $key => $value) {
								?>
								<option value="<?echo $key;?>" <?echo ($lastMode == $key)?"selected":"";?>>
								<? echo $value;?>
								</option>
								<?
							}
							?>
						</select>
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
			$win = ord($row['WIN']);
			$lose = ord($row['LOSE']);
			$fwotd = ord($row['fwotd']);
			$leaver = ord($row['leaver']);
			$afk = ord($row['afk']);
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
			
			if($row['duration'] == "0") {
				$duration = "??? mins";
			} else {
				$duration = $row['duration']." mins";
			}
			
			$year = substr($row['time'], 0, 4);
			$month = substr($row['time'], 5, 2);
			$day = substr($row['time'], 8, 2);
			$hour = substr($row['time'], 11, 2);
			$min = substr($row['time'], 14, 2);
			$time = $day.".".$month.".".$year." ".$hour.":".$min;

		?>
			<div class="row">
				<div class="span12">
					<div class="well<?echo $class;?> match" id="<?echo $row['gameId'];?>">
						<div class="matchcell championcell"><img class="img-rounded imgchampion" alt="<?echo $champsDisplay[$row['championId']];?>" src="<?echo $champsFolder.ucfirst($champsName[$row['championId']]);?>.png">
						</div>
						
						<div class="matchcell headcell">
							<?echo $modes[$row['type']];?>
							<br><span class="<?echo $classtext;?>"><?echo $text;?></span>
							<br> <?echo $duration;?>
							<br> <?echo $time;?>
						</div>
						
						<?
						if (isset($row['spell1']))
						{
						?>
							<div class="matchcell kdacell">
								<div class="kdaNumber"><?echo $row['CHAMPIONS_KILLED'];?><img class="icon" src="<?echo PATH;?>img/kill.png" alt="kills"></div>
								<div class="kdaNumber"><?echo $row['NUM_DEATHS'];?><img class="icon" src="<?echo PATH;?>img/death.png" alt="deaths"></div>
								<div class="kdaNumber"><?echo $row['ASSISTS'];?><img class="icon" src="<?echo PATH;?>img/assist.png" alt="assists"></div>

								<br><div class="minion"><?echo $row['MINIONS_KILLED'];?><img class="icon" src="<?echo PATH;?>img/minion.png" alt="minions"></div>
								<br><div class="gold"><?echo $row['GOLD_EARNED'];?><img class="icon" src="<?echo PATH;?>img/gold.png" alt="gold"></div>
							</div>
							
							<div class="matchcell sscell">
								<div class="ss"><img class="icon img-rounded" src="<?echo PATH;?>img/ss/<?echo $row['spell1'];?>.png" alt="Summoner Spell n <?echo $row['spell1'];?>"></div>
								<div class="ss"><img class="icon img-rounded" src="<?echo PATH;?>img/ss/<?echo $row['spell2'];?>.png" alt="Summoner Spell n <?echo $row['spell2'];?>"></div>
							</div>
							
							<div class="matchcell itemscell">
								<table>
									<? echo items($row); ?>
								</table>
							</div>
						<?
						} else {
						?>
							<div class="matchcell nodatacell">
								No data.
							</div>
						<?
						}
						?>
						<div class="matchcell playerscell">
							<table class="players">
								<? 
								
								echo players($teamL, $teamR, $row['region'], $id, $champsName); ?>
							</table>
						</div>
					</div>
				</div>
			</div>
		<?}?>