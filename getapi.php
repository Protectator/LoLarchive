<?php

define('DATAVERSION', '3');

require_once('private/config.php');

require_once('includes/functions.include.php');

// Initialize connexion to database
$pdo = newDBConnection();
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}
// Get users to track
$query = rawSelect($pdo, "SELECT * FROM usersToTrack ORDER BY region, summonerId");

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
	// For each player
	while ($row = $query->fetch()) {
		// Only used to log informations
		$countPlayers += 1;
		$countNewMatches = 0;

		// Preparing cURL
		$region = strtolower($row['region']);
		$sId = $row['summonerId'];
		$c = curl_init();
		$json = apiGame($c, $region, $sId);
		curl_close($c);
		// Transform json in an Array
		$array = json_decode($json, true);

		if (isset($array['status']) && $array['status']!= "") {
			logError($array['error']);
		} else {
			$matches = $array['games'];

			foreach ($matches as $match) {
			
				// Converting the epoch to Datetime
				$epochCreateDate = $match['createDate']/1000;
				$finalDate = date('Y-m-d H:i:s', $epochCreateDate);
			
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
					"duration" => '0',
					"mapId" => null,
					"invalid" => null,
					"dataVersion" => DATAVERSION, 
					"dataIp" => $ip
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
					"invalid" => null, 
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
						"dataVersion" => DATAVERSION, 
						"dataIp" => $ip
					);
				} // Now we nees to add the player that we're checking (he isn't in the json array)
				$players[] = array (
					"gameId" => $match['gameId'],
					"summonerId" => $sId,
					"teamId" => $match['teamId'],
					"championId" => $match['championId'],
					"dataVersion" => DATAVERSION, 
					"dataIp" => $ip
				);
				
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
			$text = ($countNewMatches > 0) ? "[".$region."] Summoner ".$sId." \"".$row['name']."\" : ".$countNewMatches." added games".PHP_EOL : "";
			echo $text;
			logAccess($text);
		}
		if (!PROD_KEY) {
			sleep(0.9);
		}
	} // END foreach player

}

echo "\nTotal : ".$countTotalMatches." added games / ".$countPlayers." tracked summoners\n";
?>