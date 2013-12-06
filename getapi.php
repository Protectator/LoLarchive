<?php

require_once('includes/functions.include.php');

// Initialize connexion to database
$pdo = newDBConnection();
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}
// Get users to track
$query = rawSelect($pdo, "SELECT * FROM usersToTrack ORDER BY region, summonerId");

// If REMOTE_ADDR ain't set, it's that we're doing this request locally
$ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "local";

date_default_timezone_set('Europe/Berlin');
echo "> ".date('d/m/Y H:i:s', time())." - Request by ".$ip;

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
		$region = mb_strtoupper($row['region']);
		$sId = $row['summonerId'];
		$aId = $row['accountId'];
		$c = curl_init();
		$json = getRecentGames($c, $region, $aId);
		curl_close($c);
		// Transform json in an Array
		$array = json_decode($json, true);
		/*echo "<pre>";
		print_r($array);
		echo "</pre>";*/
		$matches = $array['gameStatistics']['array'];
		
		foreach ($matches as $match) {
		
			// Just messing with the date formatting...
			$date = preg_match('/(\w+) (\d+), (\d+) (\d+):(\d+):(\d+) (\w+)/', $match['createDate'], $save);
			$time = $save[3]."-".$months[$save[1]]."-".$save[2]." ".date("H:i", strtotime($save[4].":".$save[5].":".$save[6]." ".$save[7]));
		
			/*
			First we need to match every stat in the json file to a column in the database.
			We'll put things in 3 different tables :
			- games   
			- data    
			- players 
			*/
			
			// Matching columns in "games" with API
			$games = array (
				"gameId" => $match['gameId'],
				"region" => $region,
				"mapId" => $match['gameMapId'],
				"time" => $time,
				"type" => $match['queueType'],
				"subType" => $match['subType'],
				"duration" => timeOf($match['gameMapId'], $match['type'], $match['ipEarned'], $match['win'], $match['difficulty'], $match['level']),
				"sender" => 0
			);
			
			// If there is a difficulty, then it's a bot game. Else, it is 0.
			$games["difficulty"] = (isset($match['difficulty'])) ? $match['difficulty'] : "0";
			
			// Matching columns in "data" with API
			$data = array (
				"gameId" => $match['gameId'],
				"summonerId" => $sId,
				"region" => $region,
				"spell1" => $match['spell1'],
				"spell2" => $match['spell2'],
				"ipData" => $ip,
				"leaver" => $match['afk'],
				"invalid" => $match['invalid'],
				"dataVersion" => $match['dataVersion'],
				"playerLevel" => $match['level'],
				"premade" => $match['premadeSize'],
				"ipEarned" => $match['ipEarned'],
				"fwotd" => $match['eligibleFirstWinOfDay'],
				"estimatedDuration" => '0', // TODO : Estimer la durée d'une game en fonction des IP gagnés
				"boostIpEarned" => $match['boostIpEarned'],
				"skinIndex" => $match['skinIndex']
			);
			// For each other stat (the ones in caps) we put them directly with their name
			// in the table
			foreach ($match['statistics']['array'] as $stat) {
				$data[$stat['statType']] = $stat['value'];
			}
			
			// Matching columns in "players" with API
			$players = array();
			foreach ($match['fellowPlayers']['array'] as $player) {
				$players[] = array (	
					"gameId" => $match['gameId'],
					"summonerId" => $player['summonerId'],
					"teamId" => $player['teamId'],
					"championId" => $player['championId'],
					"dataVersion" => "2"
				);
			} // Now we nees to add the player that we're checking (he isn't in the json array)
			$players[] = array (
				"gameId" => $match['gameId'],
				"summonerId" => $sId,
				"teamId" => $match['teamId'],
				"championId" => $match['championId'],
				"dataVersion" => "2"
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
		
		echo ($countNewMatches > 0) ? "\n[".$region."] Summoner ".$sId." \"".$row['name']."\" : ".$countNewMatches." added games" : "";
		
	} // END foreach player

}

echo "\nTotal : ".$countTotalMatches." added games / ".$countPlayers." tracked summoners\n";
?>