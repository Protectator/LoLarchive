<?php

define('DATAVERSION', '3');

require_once('config.php');

require_once(LOCAL.'/includes/functions.include.php');

// Initialize connexion to database
$pdo = newDBConnection();
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}
// Get users to track
$query = rawSelect($pdo, "SELECT summonerId, region FROM usersToTrack ORDER BY region, summonerId");

// If REMOTE_ADDR ain't set, it's that we're doing this request locally
$ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1";

date_default_timezone_set('Europe/Berlin');
$header = "> ".date('d/m/Y H:i:s', time())." - Request by ".$ip.PHP_EOL/*IDE*/;
logAccess($header);/*;3*/
echo $header;

$countTotalMatches = 0;
// Si la requête retourne des résultats
if (count($query) > 0) {
	$countPlayers = 0;
	$mode = "PRIMARY";
	if (IMMEDIATE_QUERY_SUMMONER_NAMES) {
		$allIds = array();
	}
	$secondaryIds = array();
	$rowSecondary = array();
	// For each player
	while (($row = $query->fetch(PDO::FETCH_ASSOC)) || ($rowSecondary = array_pop($secondaryIds))) {

		// If IMMEDIATE_QUERY_PARTICIPANTS_DATA is true, this changes to mode SECONDARY when the query has been entirely seen.
		if (empty($row) && !empty($rowSecondary) && IMMEDIATE_QUERY_PARTICIPANTS_DATA && $mode == "PRIMARY") {
			$mode = "SECONDARY";
			$secondaryIds = array_map('unserialize', array_unique(array_map('serialize', $secondaryIds)));
			$row = $rowSecondary;
		} elseif (empty($row) && !empty($rowSecondary) && IMMEDIATE_QUERY_PARTICIPANTS_DATA) {
			$row = $rowSecondary;
		} elseif (empty($row) && !empty($rowSecondary)) {
			echo "break ?";
			break;
		}

		// Only used to log informations
		$countPlayers += 1;
		$countNewMatches = 0;

		// Preparing cURL
		$region = strtolower($row['region']);
		$sId = $row['summonerId'];
		$c = curl_init();
		$array = apiGame($c, $region, $sId);
		curl_close($c);
		// Transform json in an Array

		if (isset($array['status']) && $array['status']!= "") {
			$errorText = "Error in API call ; ";
			$errorText = $errorText."API sent : Error ".$array['status']['status_code']." : ".$array['status']['message'];
			logError($errorText);
			echo "<br>Error in API call '".API_URL.$region."/v".SUMMONER_API_VERSION."/game/by-summoner/".$sId."/recent?api_key=".API_KEY."': <br><pre>";
			print_r($array);
			echo "</pre>";

		} else {
			$matches = $array['games'];

			if (isset($_GET['debug'])) {
				echo "json : <br><pre>";
				print_r($matches);
				echo "</pre>";
			}

			foreach ($matches as $match) {
			
				// Converting the epoch to Datetime
				$epochCreateDate = $match['createDate']/1000;
				$finalDate = date('Y-m-d H:i:s', $epochCreateDate);

				$estimatedWinningTeam = ($match['stats']['win']) ? $match['stats']['team'] : 300-$match['stats']['team'];
				$estimatedDuration = $match['stats']['timePlayed'];
			
				/*
				First we need to match every stat in the json file to a column in the database.
				We'll put things in 3 different tables :
				- games   
				- data    
				- players 
				*/

				/* Every field that is null means it's simply the same
				name as the one in the API and needs no other treatment.
				*/
								
				// Table 'games'
				$games = array (
					"gameId" => null,
					"region" => $region,
					"createDate" => $finalDate,
					"gameMode" => null,
					"gameType" => null,
					"subType" => null,
					"mapId" => null,
					"invalid" => null,
					"estimatedDuration" => $estimatedDuration,
					"estimatedWinningTeam" => $estimatedWinningTeam,
					"gamesVersion" => DATAVERSION, 
					"gamesIp" => $ip
				);
				foreach ($games as $key => $value) {
					if (!isset($value)) {
						$games[$key] = $match[$key];
					}
				}
				
				// Table 'dates'
				$data = array (
					"gameId" => $match['gameId'], 
					"summonerId" => $sId, 
					"region" => $region, 
					"goldEarned" => null, 
					"championsKilled" => null, 
					"numDeaths" => null, 
					"assists" => null, 
					"minionsKilled" => null, 
					"spell1" => $match['spell1'], 
					"spell2" => $match['spell2'], 
					"item0" => null, 
					"item1" => null, 
					"item2" => null, 
					"item3" => null, 
					"item4" => null, 
					"item5" => null, 
					"item6" => null, 
					"largestMultiKill" => null, 
					"largestKillingSpree" => null, 
					"turretsKilled" => null, 
					"totalHeal" => null, 
					"totalDamageDealtToChampions" => null, 
					"physicalDamageDealtToChampions" => null, 
					"magicDamageDealtToChampions" => null, 
					"trueDamageDealtToChampions" => null, 
					"totalDamageDealt" => null, 
					"physicalDamageDealtPlayer" => null, 
					"magicDamageDealtPlayer" => null, 
					"trueDamageDealtPlayer" => null, 
					"totalDamageTaken" => null, 
					"physicalDamageTaken" => null, 
					"magicDamageTaken" => null, 
					"trueDamageTaken" => null, 
					"sightWardsBought" => null, 
					"visionWardsBought" => null, 
					"neutralMinionsKilled" => null, 
					"neutralMinionsKilledYourJungle" => null, 
					"neutralMinionsKilledEnemyJungle" => null, 
					"level" => null, 
					"wardPlaced" => null, 
					"wardKilled" => null, 
					"summonerLevel" => null, 
					"totalTimeCrowdControlDealt" => null, 
					"largestCriticalStrike" => null, 
					"win" => null, 
					"barracksKilled" => null, 
					"totalScoreRank" => null, 
					"objectivePlayerScore" => null, 
					"victoryPointTotal" => null, 
					"nodeCaptureAssist" => null, 
					"totalPlayerScore" => null, 
					"nodeCapture" => null, 
					"nodeNeutralize" => null, 
					"nodeNeutralizeAssist" => null, 
					"teamObjective" => null, 
					"combatPlayerScore" => null, 
					"consumablesPurchased" => null, 
					"firstBlood" => null, 
					"spell1Cast" => null, 
					"spell2Cast" => null, 
					"spell3Cast" => null, 
					"spell4Cast" => null, 
					"summonSpell1Cast" => null, 
					"summonSpell2Cast" => null, 
					"superMonsterKilled" => null, 
					"timePlayed" => null, 
					"unrealKills" => null, 
					"doubleKills" => null, 
					"tripleKills" => null, 
					"quadraKills" => null, 
					"pentaKills" => null, 
					"nexusKilled" => null, 
					"gold" => null, 
					"itemsPurchased" => null, 
					"numItemsBought" => null, 
					"dataVersion" => DATAVERSION, 
					"dataIp" => $ip
				);
				foreach ($data as $key => $value) {
					if (!isset($value)) {
						if (isset($match['stats'][$key])) {
							$data[$key] = $match['stats'][$key];
						} else {
							$data[$key] = '0';
						}
					}
				}


				// Matching columns in "players" with API
				$players = array();
				foreach ($match['fellowPlayers'] as $player) {
					$players[] = array (	
						"gameId" => $match['gameId'],
						"summonerId" => $player['summonerId'],
						"teamId" => $player['teamId'],
						"championId" => $player['championId'], 
						"playersVersion" => DATAVERSION, 
						"playersIp" => $ip
					);
					if (IMMEDIATE_QUERY_PARTICIPANTS_DATA && $mode == "PRIMARY") {
						$secondaryIds[] = array("summonerId" => $player['summonerId'], "region" => $region);
						echo "PLAYER ".$player['summonerId']." ADDED<br>";
					}
				} // Now we need to add the player that we're checking (he isn't in the json array)
				$players[] = array (
					"gameId" => $match['gameId'],
					"summonerId" => $sId,
					"teamId" => $match['teamId'],
					"championId" => $match['championId'],
					"playersVersion" => DATAVERSION, 
					"playersIp" => $ip
				);

				// Adds ids to query for names
				if (IMMEDIATE_QUERY_SUMMONER_NAMES) {
					$sIds = array_map(function($a){return $a['summonerId'];}, $players); // Array containing only summonerIds
					$allIds = array_merge($allIds, $sIds);
				}

				
				$req = array(); // Will contain requests to do			

				// Request on the "games" table
				$req[0] = "INSERT IGNORE INTO games ".buildInsert($games);
					
				// Request on the "data" table
				$req[1] = "INSERT IGNORE INTO data ".buildInsert($data);
				
				// Request on the "players" table
				$req[2] = "INSERT IGNORE INTO players ".buildMultInsert($players);
				
				// Execute all three requests in a secured way
				$result = securedInsert($pdo, $req);
				if ($result[0]) {
					if ($result[1] >= 1) {
						$countNewMatches++;
						$countTotalMatches++;
					}
				}
				
			} // END foreach match
			$text = ($countNewMatches > 0) ? "[".$region."] Summoner ".$sId." \"".(array_key_exists('name', $row)?$row['name']:"?")."\" : ".$countNewMatches." added games".PHP_EOL : "";
			echo $text;
			if (!isset($_GET['debug'])) {
				logAccess($text);
			}
		}
		if (!PROD_KEY) {
			sleep(1);
		}
	} // END foreach player

	if (IMMEDIATE_QUERY_SUMMONER_NAMES) {
		$totalNewNames = 0;
		$allIds = array_unique($allIds);
		while ($someIds = array_splice($allIds, 0, 40)) {
			$c = curl_init();
			$names = apiSummonerNames($c, $region, $someIds);
			curl_close($c);
			$usersQuery = "INSERT IGNORE into users ";
			$toAdd = array();
			foreach($names as $key => $val) {
				$toAdd[] = array(
					"id" => $key,
					"user" => $val,
					"region" => $region
				);
			}
			$usersQuery .= buildMultInsert($toAdd);
			$addedSummoners = securedInsert($pdo, $usersQuery);
			if ($addedSummoners[0] != 1) {
				logError("An error occured while adding names to database.".PHP_EOL);
			} else {
				$totalNewNames += $addedSummoners[1];
			}
			if (!PROD_KEY) {
				sleep(1);
			}
		}
		if ($totalNewNames != 0) {
			logAccess($totalNewNames." names added.".PHP_EOL);
		}
		
	}

}

echo "\nTotal : ".$countTotalMatches." added games / ".$countPlayers." tracked summoners\n";
?>