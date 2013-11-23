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
			$id = $_GET['id'];
			$requestString[0] .= conditions($conditions); 
			$findSummoner = $pdo->prepare($requestString[0]);
			$findSummoner->bindParam(":id", $_GET['id']);
		// Else, look for the username
		} else {
			$conditions["user"] = $_GET['name'];
			$name = $_GET['name'];
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
			
			$filtersStr = array(); // String of all filters conditions
			
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
				$modeFilterStr = " AND games.type = :typeStr";
				$filtersStr[] = $modeFilterStr;
			}
			
			$requestString[1] = "
			SELECT *
			FROM games 
			LEFT JOIN players ON games.gameId = players.gameId
			LEFT JOIN data ON games.gameId = data.gameId AND players.gameId = data.gameId
			WHERE players.summonerId = :sId AND data.summonerId = :sId".implode($filtersStr);

			$requestString[1] .= " ORDER BY games.time DESC;";
			if (isset($_GET['debug'])) {
				echo $requestString[1];
			}
			// New request : All games of this user
			$summonerGames = $pdo->prepare($requestString[1]);
			$summonerGames->bindParam(":sId", $id);
			
			// Check if each filter is activated
			
			// filter games by Champion
			if ($filters['fChampion']) {
				$summonerGames->bindParam(":championId", intval($filters['fChampion']));
			}
			// filter games by Mode
			if ($filters['fMode']) {
				$summonerGames->bindParam(":typeStr", $filters['fMode']);
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
			<h2><?php echo htmlentities(utf8_decode($name));?> <a href="http://www.lolking.net/summoner/<? echo $region."/".$id; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a></h2>
			<?php echo htmlentities($regionTxt);?>
			<br><?php echo htmlentities($id);?>
		</div>
	</div>
</div>

<div class="row">
	<div class="span12">
		<form class="form-horizontal well" action="http://protectator.ch/lolarchive/index.php" method="get">
			<fieldset>
				<legend>Filter games</legend>
				<input type="hidden" name="page" value="player"/>
				<input type="hidden" name="region" value="<?php echo $_GET['region']?>"/>
				<input type="hidden" name="name" value="<?php echo $_GET['name']?>"/> 
				<div class="control-group">
					<label class="control-label">
						<label class="checkbox inline"><input type="checkbox" id="champFilterBox" <?php echo (isset($filters['fChampion']) && $filters['fChampion'])?'checked="yes"':''?>> Champion</label>
					</label>
					<div class="controls">
						<select id="champFilterChoice" name="fChampion" class="input-medium">
							<?php
							foreach ($champsId as $value) {
								?>
								<option value="<?php echo $value;?>" style="background: url('<?php echo PATH;?>img/champions/<?php echo ucfirst($champsDisplay[$value]);?>.png') no-repeat;" <?php echo (isset($filters['fChampion']) && $filters['fChampion'] == $value)?"selected":"";?>>
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
                                    
					<div class="well<?php echo $class;?> match" id="<?php echo $row['gameId'];?>">
						<div class="matchcell championcell"><img class="img-rounded imgchampion" alt="<?php echo $champsDisplay[$row['championId']];?>" src="<?php echo $champsFolder.ucfirst($champsName[$row['championId']]);?>.png">
						</div>
						
						<div class="matchcell headcell">
							<?php echo $modes[$row['type']];?>
							<br><span class="<?php echo $classtext;?>"><?php echo $text;?></span>
							<br> <?php echo $duration;?>
							<br> <?php echo $time;?>
						</div>
						
						<?php
						if (isset($row['spell1']))
						{
						?>
							<div class="matchcell kdacell">
								<div class="kdaNumber"><?php echo $row['CHAMPIONS_KILLED'];?><img class="icon" src="<?php echo PATH;?>img/kill.png" alt="kills"></div>
								<div class="kdaNumber"><?php echo $row['NUM_DEATHS'];?><img class="icon" src="<?php echo PATH;?>img/death.png" alt="deaths"></div>
								<div class="kdaNumber"><?php echo $row['ASSISTS'];?><img class="icon" src="<?php echo PATH;?>img/assist.png" alt="assists"></div>

								<br><div class="minion"><?php echo $row['MINIONS_KILLED'];?><img class="icon" src="<?php echo PATH;?>img/minion.png" alt="minions"></div>
								<br><div class="gold"><?php echo $row['GOLD_EARNED'];?><img class="icon" src="<?php echo PATH;?>img/gold.png" alt="gold"></div>
							</div>
							
							<div class="matchcell sscell">
								<div class="ss"><img class="icon img-rounded" src="<?php echo PATH;?>img/ss/<?php echo $row['spell1'];?>.png" alt="Summoner Spell n <?php echo $row['spell1'];?>"></div>
								<div class="ss"><img class="icon img-rounded" src="<?php echo PATH;?>img/ss/<?php echo $row['spell2'];?>.png" alt="Summoner Spell n <?php echo $row['spell2'];?>"></div>
							</div>
							
							<div class="matchcell itemscell">
								<table>
									<?php echo items($row); ?>
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
							<table class="players">
								<?php 
								
								echo players($teamL, $teamR, $row['region'], $id, $champsName); ?>
							</table>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>