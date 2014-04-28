<?php
	
	$champsFolder = PATH."img/champions/";

	if (isset($_GET["name"])) {
		if ($_GET["name"] != "") {$Iname = $_GET["name"];}
	}
	if (isset($_GET["id"])) {
		if ($_GET["id"] != "") {$Iid = $_GET/*27*/["id"];}
	}
	if (isset($_GET["region"])) { 
		if ($_GET["region"] != "") {$Iregion = strtolower($_GET["region"]);}
	}

	if (isset($Iregion)) {
		if (array_key_exists($Iregion, $regionName)) {
			$summonerRegion = $regionName[$Iregion];
			// Look for summoner by name in users
			if (isset($Iname) && !isset($Iid)) { // If only the name is provided
				if (strlen($Iname) <= SUMMONER_NAME_MAX_LENGTH) {

					$userRequestString = "SELECT id, user, region FROM users WHERE LOWER( user ) = LOWER( :user ) AND region = :region";
					// debug
					if (isset($_GET['debug'])) {
						echo $userRequestString;
						echo "<br/>name: ".$Iname;
						echo "<br/>region: ".$Iregion;
					}
					//
					$findSummoner = $pdo->prepare($userRequestString);
					$findSummoner->bindParam(":region", $Iregion);
					$findSummoner->bindParam(":user", $Iname);
					$findSummoner->execute(); 
					$foundSummoner = $findSummoner->fetch();
					if (!empty($foundSummoner)) { // If a summoner is found in the database
						$summonerId = $foundSummoner['id'];
						$summonerName = $foundSummoner['user'];
					} else { // Else, we'll have to get infos outside our database
						// If API requests for summoner names are enabled
						if (QUERY_FOREIGN_SUMMONER_NAME_WHEN_PLAYER_ACCESSED) {
							$cUrl = curl_init();
							$byNameSummoner = current(apiSummonerByName($cUrl, $summonerRegion, $Iname));
							curl_close($cUrl);
							if (isset($byNameSummoner['id'])) { // If we found someone in the API
								$summonerId = $byNameSummoner['id'];
								$summonerName = $byNameSummoner['name'];
								$usersFields[] = array(
									"id" => $summonerId,
									"user" => $summonerName,
									"region" => $summonerRegion
									);
								$addUserRequestString = "INSERT IGNORE INTO users ".buildInsert($usersFields);
								$result = securedInsert($pdo, $addUserRequestString);
							} else { // If we didn't find anything in the API either
								echoHeader("Summoner not found");
								echo "<div class='alert alert-error alert-block'><h4>Summoner doesn't exist</h4>";
								echo "No summoner named <strong>".purify($Iname)."</strong> on ".$summonerRegion." seems to exist.</div>";
							}
						} else { // If other API requests aren't authorized
							echoHeader("Summoner not found");
							echo "<div class='alert alert-info alert-block'><h4>Summoner not found</h4>";
							echo "No summoner named <strong>".purify($Iname)."</strong> on ".$summonerRegion." was found in the database.</div>";
						}
					}

				} else {
					echoHeader("Summoner not found");
					echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
					echo "Summoner name must not exceed ".SUMMONER_NAME_MAX_LENGTH." characters</div>";
				}

			} elseif (isset($Iid)) {
				if (is_numeric($Iid)) {
					if (strlen($Iid) <= SUMMONER_ID_MAX_LENGTH) {

						// Look for a summoner by id in users
						$userRequestString = "SELECT id, user, region FROM users WHERE region = :region AND id = :id";
						$findSummoner = $pdo->prepare($userRequestString);
						$findSummoner->bindParam(":region", $Iregion);
						$findSummoner->bindParam(":id", $Iid);
						$findSummoner->execute(); 
						$foundSummoner = $findSummoner->fetch();
						if (!empty($foundSummoner)) {
							$summonerId = $foundSummoner['id'];
							$summonerName = $foundSummoner['user'];
						} else {
							$summonerId = $Iid;
							// Check infos in the API if authorized

							// If API requests for summoner names are enabled
							if (QUERY_FOREIGN_SUMMONER_NAME_WHEN_PLAYER_ACCESSED) {
								$cUrl = curl_init();
								$byIdSummoner = current(apiSummonerNames($cUrl, $summonerRegion, $Iid));
								curl_close($cUrl);
								if (isset($byIdSummoner)) { // If we found someone in the API
									$summonerName = $byIdSummoner;
									$usersFields[] = array(
										"id" => $summonerId,
										"user" => $summonerName,
										"region" => $summonerRegion
										);
									$addUserRequestString = "INSERT IGNORE INTO users ".buildInsert($usersFields);
									$result = securedInsert($pdo, $addUserRequestString);
								} else { // If we didn't find anything in the API either
									echoHeader("Summoner not found");
									echo "<div class='alert alert-error'><h4>Id doesn't exist</h4>";
									echo "This id doesn't match any existing summoner.</div>";
								}
							} else { // If other API requests aren't authorized
								echoHeader(purify($summonerId)." [".strtoupper($summonerRegion)."] - LoLarchive");
								$potentiallyInexistantSummoner = true; // TODO : Display only if summoner has no games.
								echo "<div class='alert alert-warning alert-block'><button type='button' class='close' data-dismiss='alert'>&times;</button>";
								echo "<h4>Warning</h4>No name has been found for this id in the database, and no request have been sent to Riot Games' API.<br>";
								echo "This page displays information about the summoner with id ".purify($summonerId).".<br>";
								echo "If there is no game here, the summoner may not exist at all.</div>";
							}

						}

					} else {
						echoHeader("Summoner not found");
						echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
						echo "Summoner id must not exceed ".SUMMONER_ID_MAX_LENGTH." digits</div>";
					}


				} else {
					echoHeader("Summoner not found");
					echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
					echo "Please provide an id containing only digits.</div>";
				}

			} else {
				echoHeader("Summoner not found");
				echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
				echo "Please provide either a summoner name or id.</div>";
			}


			// Done with treating user input, now we'll display all of this
			if (isset($summonerId)) {

				///
				/// START NEW
				/// 
				

				// START FILTERS
				$filtersStr = array(); // sexy String of all filters conditions	
				$filters = array( // Array of activated filters
							'fChampion' => false,
							'fMode' => false,
							'fStart' => false,
							'fEnd' => false
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

				$validDateFormat = "/^\d+-\d+-\d+$/";

				// filter games by Date
				if ((isset($_GET['fStart']) AND $_GET['fStart'] != '') AND (isset($_GET['fEnd']) AND $_GET['fEnd'] != '')) {
					if (preg_match($validDateFormat, $_GET['fStart']) AND preg_match($validDateFormat, $_GET['fEnd'])) {
						$nextStart = $_GET['fStart'];
						$nextEnd = $_GET['fEnd'];
						$filters['fStart'] = implode("-", array_reverse( explode("-", $_GET['fStart'])))." 00:00:00";
						$filters['fEnd'] = implode("-", array_reverse( explode("-", $_GET['fEnd'])))." 23:59:59";
						$dateFilterStr = " AND (games.createDate BETWEEN :from AND :to)";
						$filtersStr[] = $dateFilterStr;
					}

				} elseif (isset($_GET['fStart']) AND $_GET['fStart'] != '') {
					if (preg_match($validDateFormat, $_GET['fStart'])) {
						$nextStart = $_GET['fStart'];
						$filters['fStart'] = implode("-", array_reverse( explode("-", $_GET['fStart'])))." 00:00:00";
						$dateFilterStr = " AND games.createDate >= :from ";
						$filtersStr[] = $dateFilterStr;
					}

				} elseif (isset($_GET['fEnd']) AND $_GET['fEnd'] != '') {
					if (preg_match($validDateFormat, $_GET['fEnd'])) {
						$nextEnd = $_GET['fEnd'];
						$filters['fEnd'] = implode("-", array_reverse( explode("-", $_GET['fEnd'])))." 23:59:59";
						$dateFilterStr = " AND games.createDate <= :to";
						$filtersStr[] = $dateFilterStr;
					}
				}
				
				$conditions = "
				FROM (games 
				INNER JOIN players ON games.gameId = players.gameId)
				LEFT JOIN data ON games.gameId = data.gameId AND players.summonerId = data.summonerId AND data.gameId = games.gameId
				WHERE players.summonerId = :sId".implode($filtersStr);
				
				$statsString = "SELECT count(*) AS nbGames, avg(data.championsKilled) AS k, avg(data.numDeaths) AS d, avg(data.assists) AS a,
				avg(data.minionsKilled) AS minions, avg(data.goldEarned) AS gold, avg(data.timePlayed) AS duration".$conditions;
				
				$wonGamesString = "SELECT count(*) AS nb".$conditions." AND data.win = (1);";
				
				$requestString = array();
				$requestString[1] = "SELECT * ".$conditions." ORDER BY `games`.`createDate` DESC;";

				if (isset($_GET['debug'])) {
					echo "<br>".$requestString[1];
				}
				// New request : All games of this user
				$summonerGames = $pdo->prepare($requestString[1]);
				$summonerGames->bindParam(":sId", $summonerId);
				// Stats requests
				$stats = $pdo->prepare($statsString);
				$stats->bindParam(":sId", $summonerId);
				$wonGames = $pdo->prepare($wonGamesString);
				$wonGames->bindParam(":sId", $summonerId);
				
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
				// filter games by Date
				if ($filters['fStart']) {
					$summonerGames->bindParam(":from", $filters['fStart']);
					$stats->bindParam(":from", $filters['fStart']);
					$wonGames->bindParam(":from", $filters['fStart']);
				}
				if ($filters['fEnd']) {
					$summonerGames->bindParam(":to", $filters['fEnd']);
					$stats->bindParam(":to", $filters['fEnd']);
					$wonGames->bindParam(":to", $filters['fEnd']);
				}
				
				$summonerGames->execute(); // Execute the request
				$stats->execute();         // Execute the request
				$wonGames->execute();      // Execute the request

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
				///
				/// END NEW
				/// 

				if (isset($summonerName)) {
					echoHeader($summonerName." [".strtoupper($summonerRegion)."] - LoLarchive");
					// Display infos about that summoner
					// 
					?>
					<div class="row">
						<div class="span12">
							<div class="well">
								<h2><?php echo htmlentities(utf8_decode($summonerName));?> <a href="http://www.lolking.net/summoner/<? echo $summonerRegion."/".$summonerId; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a></h2>
								<?php echo htmlentities($summonerRegion);?>
								<br><?php echo htmlentities($summonerId);?>
							</div>
						</div>
					</div>
					<?


				} else {
					echoHeader($summonerId." [".strtoupper($summonerRegion)."] - LoLarchive");


					?>
					<div class="row">
						<div class="span12">
							<div class="well">
								<h2><?php echo htmlentities(utf8_decode($summonerId));?> <a href="http://www.lolking.net/summoner/<? echo $summonerRegion."/".$summonerId; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a></h2>
								<?php echo htmlentities($summonerRegion);?>
							</div>
						</div>
					</div>
					<?
				}
				// Display infos about summoner games
				// 

				?>
				<div class="row">
					<div class="span12">
						<div class="well">
							<legend>Statistics</legend>
							<?php
							$finalStats = $stats->fetch();
							$nbWon = $wonGames->fetch();
							if ($finalStats['nbGames'] != 0) {
								$kdaRatio = ($finalStats['d'] != 0) ? round(($finalStats['k']+$finalStats['a'])/$finalStats['d'], 2) : $finalStats['k'] + $finalStats['a'];
								echo $nbWon['nb']." wins / ".$finalStats['nbGames']." games (".round($nbWon['nb']/$finalStats['nbGames']*100, 2)."% win)";
								echo "<br>Average KDA; ".round($finalStats['k'], 1)." / ".round($finalStats['d'], 1)." / ".round($finalStats['a'], 1);
								echo "<br>Ratio; ".$kdaRatio;
							} else {
								echo "No game found.";
							}
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
								<input type="hidden" name="region" value="<?php echo $Iregion?>"/>
								<?
								if (isset($summonerName)) { ?>
								<input type="hidden" name="name" value="<?php echo $summonerName?>"/>
								<? } ?>
								<input type="hidden" name="id" value="<?php echo $summonerId?>"/> 
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
										<label class="checkbox inline"><input type="checkbox" id="dateFilterBox" <?php echo ($filters['fStart'] OR $filters['fEnd'])?'checked="yes"':''?>> Date range</label>
									</label>
									<div class="controls">
										<div class="input-daterange" id="datepicker">
											<input type="text" class="input-small" name="fStart" id="date1" <?php echo (isset($nextStart))?'value="'.$nextStart.'"':'' ?> />
											<span class="add-on">to</span>
											<input type="text" class="input-small" name="fEnd" id="date2" value="<?php echo (isset($nextEnd))?$nextEnd:date('j-n-Y') ?>" />
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
				<?
				/*
					FOR EACH GAME
				*/
				while ($row = $summonerGames->fetch(PDO::FETCH_NAMED)) {

					//   START Debug
					if (isset($_GET['debug'])) {
						echo "<br>ARRAY:<pre>";
						print_r($row);
						echo "</pre>";
					} // END Debug

					$hasData = isset($row['spell1']);
					
					// Handles all bit(1) data
					$win = ($hasData) ? ord($row['win']) : ($row['teamId'] == $row['estimatedWinningTeam']);
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
					$playersRequest->bindParam(":gId", $row['gameId'][0]);
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

					$duration = ($hasData) ? $row['timePlayed'] : $row['estimatedDuration'];
					
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
						
							<div class="well<?php echo $class;?> match" id="<?php echo $row['gameId'][0];?>">
						
								<div class="matchcell championcell"><?php echo HTMLchampionImg($row['championId'], "big", $champsName); ?></div>
								
								<div class="matchcell headcell">							
									<?php echo HTMLgeneralStats($modes[$row['subType']], $text, $duration, $time);?>
								</div>
								
								<?php
								if ($hasData)
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
									<?php echo HTMLparticipants($row['region'][0], $teamL, $teamR, $champsName); ?>
								</div>
							</div>
						</div>
					</div>
					<?php
				}

			}

		} else {
			echoHeader("Summoner not found");
			echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
			echo "Please provide a valid region.</div>";
		}
	} else {
		echoHeader("Summoner not found");
		echo "<div class='alert alert-error alert-block'><h4>Bad search</h4>";
		echo "Please provide a region.</div>";
	}
	echoFooter();
?>