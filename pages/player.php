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
	
	$champsFolder = PATH."img/champions/";

	// Check which of the important parameters are set

	if (isset($_GET["name"])) {
		if ($_GET["name"] != "") {$Iname = $_GET["name"];}
	}
	if (isset($_GET["id"])) {
		if ($_GET["id"] != "") {$Iid = $_GET/*27*/["id"];}
	}
	if (isset($_GET["region"])) { 
		if ($_GET["region"] != "") {$Iregion = strtolower($_GET["region"]);}
	}

	//////////////////////////////////////////////////////////////////////////
	// Decide which query to execute based on which parameters are provided //
	//////////////////////////////////////////////////////////////////////////
	//
	// This part will try to get a summoner id by any means. Either this id
	// is directly provided by the client, or it needs to be looked for in the
	// database or also asked for using Riot Games' API by giving the name
	// provided. If neither the name or id is provided, a search isn't possible.

	if (isset($Iregion)) {
		if (array_key_exists($Iregion, $regionName)) {
			$summonerRegion = $regionName[$Iregion];
			// Look for summoner by name in users
			if (isset($Iname) && !isset($Iid)) { // If only the name is provided
				if (strlen($Iname) <= SUMMONER_NAME_MAX_LENGTH) {

					$userRequestString = "SELECT id, user, region FROM users WHERE LOWER( user ) = LOWER( :user ) AND region = :region";
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
							$summoner = apiSummonerByName($cUrl, $Iregion, $Iname);
							curl_close($cUrl);
							if (!is_null($summoner)) { // If we found someone in the API
								$byNameSummoner = current($summoner);
								$summonerId = $byNameSummoner['id'];
								$summonerName = $byNameSummoner['name'];
								$usersFields = array(
									"id" => $summonerId,
									"user" => $summonerName,
									"region" => $Iregion
									);
								$addUserRequestString = "INSERT IGNORE INTO users ".buildInsert($usersFields);
								$result = securedInsert($pdo, $addUserRequestString);
							} else { // If we didn't find anything in the API either
								echoHeader("Summoner not found");
								echo HTMLerror("Summoner doesn't exist", "No summoner named <strong>".purify($Iname)."</strong> on ".$summonerRegion." seems to exist.");
							}
						} else { // If other API requests aren't authorized
							echoHeader("Summoner not found");
							echo HTMLerror("Summoner not found", "No summoner named <strong>".purify($Iname)."</strong> on ".$summonerRegion." was found in the database.");
						}
					}

				} else {
					echoHeader("Summoner not found");
					echo HTMLerror("Bad search", "Summoner name must not exceed ".SUMMONER_NAME_MAX_LENGTH." characters.");
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
							// Check infos in the API if authorized

							// If API requests for summoner names are enabled
							if (QUERY_FOREIGN_SUMMONER_NAME_WHEN_PLAYER_ACCESSED) {
								$cUrl = curl_init();
								$summonerName = saveSummonerInfosById($pdo, $cUrl, $Iregion, $Iid);
								curl_close($cUrl);
								if (!is_null($summonerName)) {
									$summonerId = $Iid;
								} else { // If we didn't find anything in the API either
									echoHeader("Summoner not found");
									echo HTMLerror("Id doesn't exist", "This id doesn't match any existing summoner.");

								}
							} else { // If other API requests aren't authorized
								$summonerId = $Iid;
								echoHeader(purify($summonerId)." [".strtoupper($Iregion)."] - LoLarchive");
								$potentiallyInexistantSummoner = true;
							}

						}

					} else {
						echoHeader("Summoner not found");
						echo HTMLerror("Bad search", "Summoner id must not exceed ".SUMMONER_ID_MAX_LENGTH." digits.");
					}

				} else {
					echoHeader("Summoner not found");
					echo HTMLerror("Bad search", "Please provide an id containing only digits.");
				}

			} else {
				echoHeader("Summoner not found");
				echo HTMLerror("Bad search", "Please provide either a summoner name or id.");
			}

			if (isset($summonerId)) {

				////////////////////////////////////////////////////
				// Query user's games and statistics with filters //
				////////////////////////////////////////////////////
				// 
				// Now that we have an id to look for, we'll ask the database for all
				// the games that this user took part in, filtered by the provided
				// conditions.
				
				//// Option : Ask for summoner data ////
				if (QUERY_FOREIGN_SUMMONER_DATA_WHEN_PLAYER_ACCESSED) {
					$trackedUsersString = "SELECT summonerId FROM usersToTrack";
					$trackedUsersRequest = $pdo->prepare($trackedUsersString);
					$trackedUsersRequest->execute();
					$trackedUsers = array_values($trackedUsersRequest->fetchAll());
					if (!in_array($summonerId, $trackedUsers)) {
						// TODO : Take the 10 recent games of that summoner...
						// Not really possible now, as it is a complete file execution,
						// not "just" a funciton. Might implement this later.
						// 
						// $cUrl = curl_init();
						// $summoner = apiSummonerByName($cUrl, $Iregion, $Iname);
						// curl_close($cUrl);
					}
				}

				//// Filters ////
				// There are 4 filters
				// - fChampion	Games in which the user played that champion
				// - fMode		Games played in that mode
				// - fStart		Games more recent than this date
				// - fEnd		Games more ancient than this date

				$filtersStr = array(); // sexy String of all filters conditions	
				$filters = array( // Array of activated filters
							'fChampion' => false,
							'fMode' => false,
							'fStart' => false,
							'fEnd' => false
				);
				
				// Add filter fChampion if it is given
				if (isset($_GET['fChampion']) AND $_GET['fChampion'] != '') {
					$filters['fChampion'] = $_GET['fChampion'];
					$championFilterStr = " AND players.championId = :championId";
					$filtersStr[] = $championFilterStr;
				}
				
				// Add filter fMode if it is given
				if (isset($_GET['fMode']) AND $_GET['fMode'] != '') {
					$filters['fMode'] = $_GET['fMode'];
					$modeFilterStr = " AND games.subType = :typeStr";
					$filtersStr[] = $modeFilterStr;
				}

				// Filters fStart and fEnd are a little more tricky :
				// If only one of them is given, we add it as a comparison between the
				// provided filter's date and the games' date.
				// If they are both given, the filter is added as a date between two
				// boundaries (date BETWEEN fStart and fEnd)
				if ((isset($_GET['fStart']) AND $_GET['fStart'] != '') AND (isset($_GET['fEnd']) AND $_GET['fEnd'] != '')) {
					if (preg_match($validDateFormat, $_GET['fStart']) AND preg_match($validDateFormat, $_GET['fEnd'])) {
						$nextStart = $_GET['fStart'];
						$nextEnd = $_GET['fEnd'];
						$filters['fStart'] = dateToSQL($_GET['fStart'], "00:00:00");
						$filters['fEnd'] = dateToSQL($_GET['fEnd'], "23:59:59");
						$dateFilterStr = " AND (games.createDate BETWEEN :from AND :to)";
						$filtersStr[] = $dateFilterStr;
					}

				} elseif (isset($_GET['fStart']) AND $_GET['fStart'] != '') {
					if (preg_match($validDateFormat, $_GET['fStart'])) {
						$nextStart = $_GET['fStart'];
						$filters['fStart'] = dateToSQL($_GET['fStart'], "00:00:00");
						$dateFilterStr = " AND games.createDate >= :from ";
						$filtersStr[] = $dateFilterStr;
					}

				} elseif (isset($_GET['fEnd']) AND $_GET['fEnd'] != '') {
					if (preg_match($validDateFormat, $_GET['fEnd'])) {
						$nextEnd = $_GET['fEnd'];
						$filters['fEnd'] = dateToSQL($_GET['fEnd'], "23:59:59");
						$dateFilterStr = " AND games.createDate <= :to";
						$filtersStr[] = $dateFilterStr;
					}
				}
				
				//// Main SQL ////
				// This is the main SQL call. It joins all the needed tables to display games with its data about
				// the corresponding participating summoners and stats about the current summoner
				$conditions = "
				FROM (games 
				INNER JOIN players ON games.gameId = players.gameId)
				LEFT JOIN data ON games.gameId = data.gameId AND players.summonerId = data.summonerId AND data.gameId = games.gameId
				WHERE players.summonerId = :sId".implode($filtersStr);
				
				// This SQL calculated the stats that are going to be displayed just below summoner's informations. They are statistics
				// about all the asked games
				$statsString = "SELECT count(*) AS nbGames, avg(data.championsKilled) AS k, avg(data.numDeaths) AS d, avg(data.assists) AS a,
				avg(data.minionsKilled+data.neutralMinionsKilled) AS minions, avg(data.goldEarned) AS gold, avg(data.timePlayed) AS duration".$conditions;
				
				// This SQL counts the number of won games in the search.
				$wonGamesString = "SELECT count(*) AS nb".$conditions." AND (data.win = (1) OR (games.estimatedWinningTeam = players.teamId));";
				
				$requestString = array();
				$requestString[1] = "SELECT * ".$conditions." ORDER BY `games`.`createDate` DESC;";

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
				
				// Execute all SQL requests.
				$summonerGames->execute();
				$stats->execute();
				$wonGames->execute();

				$finalStats = $stats->fetch();

				if ($finalStats['nbGames'] != 0) {
					$potentiallyInexistantSummoner = false;
				}

				// Bizarre case that happens when :
				// - The client has provided only the id
				// - The id has no corresponding summoner in the database
				// - The server has disabled calls to Riot Games' API about summoner names
				// - No game have been found for this id
				// Then there's no way to know if that summoner actually exists or not.
				// In that case, we display a warning message.
				if (isset($potentiallyInexistantSummoner)) {
					if ($potentiallyInexistantSummoner) {
						echo HTMLwarning("Warning",
							"No name has been found for this id in the database, and no request have been sent to Riot Games' API.<br>
							This page displays information about the summoner with id ".purify($summonerId).".<br>
							If there is no game here, the summoner may not exist at all.");	
					}
				}

				$nbWon = $wonGames->fetch();

				//// Write summoner HTML ////
				// This part writes the HTML that displays informations about the summoner,
				// such as name, statistics and filters form.

				if (isset($summonerName)) {
					// Display infos about that summoner, if we have its name
					echoHeader($summonerName." [".strtoupper($Iregion)."] - LoLarchive");
					?>
					<div class="row">
						<div class="span12">
							<div class="well">
								<h2><?php echo htmlentities(utf8_decode($summonerName));?>
								<?php if (LINKTO_LOLKING) { ?><a href="http://www.lolking.net/summoner/<? echo $Iregion."/".$summonerId; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a><? } ?></h2>
								<?php echo htmlentities($summonerRegion);?>
								<br><?php echo htmlentities($summonerId);?>
							</div>
						</div>
					</div>
					<?


				} else {
					// Display infos about that summoner, if we don't have its name
					echoHeader($summonerId." [".strtoupper($Iregion)."] - LoLarchive");
					?>
					<div class="row">
						<div class="span12">
							<div class="well">
								<h2>? 
								<?php if (LINKTO_LOLKING) { ?><a href="http://www.lolking.net/summoner/<? echo $Iregion."/".$summonerId; ?>"><img src="<?php echo PATH;?>img/lolking.png" alt="lolking"></a><? } ?></h2>
								<?php echo htmlentities($summonerRegion);?>
								<br><?php echo htmlentities($summonerId);?>
							</div>
						</div>
					</div>
					<?
				}
				// Display statistics about the asked games
				?>
				<div class="row">
					<div class="span12">
						<div class="well">
							<legend>Statistics</legend>
							<?php
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
											foreach (array_sort($champions, 'display') as $key => $value) {
												?>
												<option value="<?php echo $key;?>" <?php echo (isset($filters['fChampion']) && $filters['fChampion'] == $key)?"selected":"";?>>
												<?php echo $value['display'];?>
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
				
				while ($row = $summonerGames->fetch(PDO::FETCH_NAMED)) {

					//// Each game ////
					// This will treat each game and display it.
					
					// Request to find all summoners in the game
					$requestString[3] = "
					SELECT * FROM players
					LEFT JOIN users ON users.id = players.summonerId
					WHERE players.gameId = :gId";
					$playersRequest = $pdo->prepare($requestString[3]);
					$playersRequest->bindParam(":gId", $row['gameId'][0]);
					$playersRequest->execute(); // Execute the request
					
					// Put each player on the right team
					$leftTeam = array();
					$rightTeam = array();
					while ($player = $playersRequest->fetch()) {
						if ($player['teamId'] == $row['teamId']) {
							$leftTeam[] = $player;
						} else {
							$rightTeam[] = $player;
						}
					}

					$hasData = isset($row['spell1']);

					// If a game has no estimated winning team, estimate it.
					if ($row['estimatedWinningTeam'] == 0) {
						$win = (estimateWinningTeam($pdo, $row['gameId'][0]) == $row['teamId']);
					} else {
						$win = ($hasData) ? ord($row['win']) : ($row['teamId'] == $row['estimatedWinningTeam']);
					}
					// Do the same for an estimation of the game's duration.
					if ($row['timePlayed'] == 0 AND $row['estimatedDuration'] == 0) {
						$duration = estimateDuration($pdo, $row['gameId'][0]);
					} else {
						$duration = ($hasData) ? $row['timePlayed'] : $row['estimatedDuration'];
					}

					echo HTMLplayerGame($row, $win, $duration, $leftTeam, $rightTeam);

				}
			}
		} else {
			echoHeader("Summoner not found");
			echo HTMLerror("Bad search", "Please provide a valid region.");
		}
	} else {
		echoHeader("Summoner not found");
		echo HTMLerror("Bad search", "Please provide a region.");
	}
	echoFooter();
?>