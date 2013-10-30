<?php

require_once('includes/functions.include.php');

// Initialize connexion to database
$pdo = newDBConnection();
foreach ($_GET as &$thing) {
	$thing = secure($pdo, $thing);
}
// Get users to track
$query = rawSelect($pdo, "SELECT * FROM usersToTrack");

// On récupère les utilisateurs à actualiser

// Si la requête retourne des résultats
if (count($query) > 0) {
	// Pour chaque joueur
	while ($row = $query->fetch()) {
		// Préparation de la requête cURL
		$region = mb_strtoupper($row['region']);
		$sId = $row['summonerId'];
		$aId = $row['accountId'];
		$c = curl_init();
		$json = getRecentGames($c, $region, $aId);
		curl_close($c);
		// On transforme le json en un Array
		$array = json_decode($json, true);
		$matches = $array['gameStatistics']['array'];

		foreach ($matches as $match) {
		
			// Just messing with the date formatting...
			$date = preg_match('/(\w+) (\d+), (\d+) (\d+):(\d+):(\d+) (\w+)/', $match['createDate'], $save);
			$time = $save[3]."-".$months[$save[1]]."-".$save[2]." ".date("H:i", strtotime($save[4].":".$save[5].":".$save[6]." ".$save[7]));
		
			/*
			First we need to match every stat in the json file to a line in the database.
			We'll put things in 3 different tables :
			- games   
			- data    
			- players 
			*/
			
			// Table "games"
			$games = array (
				"gameId" => $match['gameId'],
				"region" => $region,
				"mapId" => $match['gameMapId'],
				"time" => $time,
				"type" => $match['queueType'],
				"subType" => $match['subType'],
				"difficulty" => $match['difficulty'],
				"duration" => 0, // TODO : Estimate game duration in function of IP won
				"sender" => 0
			);
			
			// Table "data"
			$data = array (
				"gameId" => $match['gameId'],
				"summonerId" => $sId,
				"region" => $region,
				"spell1" => $match['spell1'],
				"spell2" => $match['spell2'],
				"ipData" => $_SERVER['REMOTE_ADDR'],
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
			
			// Table "players"
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
			$req[0] = "INSERT INTO games ".buildInsert($games)."
				ON DUPLICATE KEY
				UPDATE time='".$time."';";
				
			// Request on the "data" table
			$req[1] = "INSERT INTO data ".buildInsert($data)."
				ON DUPLICATE KEY
				UPDATE estimatedDuration = '0';";
			
			// Request on the "players" table
			$req[2] = "INSERT INTO players ".buildMultInsert($players)."
				ON DUPLICATE KEY
				UPDATE dataVersion = '2';";
			
			// Execute all three requests in a secured way
			echo securedInsert($pdo, $req);
		
			
		} // END foreach match
		
	} // END foreach player

	
	$c = curl_init();
	
	$region = "euw";
	$name = "Lachainone";
	
	echo trackNewPlayer($pdo, $c, $region, $name);
	
	curl_close($c);
}
?>