<?php

require_once('includes/functions.include.php');

/*Initialisation de la ressource curl*/

	$pdo = newDBConnection();
	$req = "SELECT * FROM usersToTrack";
	$query = rawSelect($pdo, $req);

// On récupère les utilisateurs à actualiser
//$query = mysql_query($req, $connect) or die("Requête SELECT 1 échouée : ".mysql_error());

// Si la requête retourne des résultats
if (count($query) > 0) {
	while ($row = $query->fetch()) { // Pour chaque joueur
	
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
		
			// Parsing de la date
			$date = preg_match('/(\w+) (\d+), (\d+) (\d+):(\d+):(\d+) (\w+)/', $match['createDate'], $save);
			$time = $save[3]."-".$months[$save[1]]."-".$save[2]." ".date("H:i", strtotime($save[4].":".$save[5].":".$save[6]." ".$save[7]));
			
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
			
			// TOUJOURS dans la table "Data"
			// Parcourir l'array "STATISTICS"
			foreach ($match['statistics']['array'] as $stat) {
				$data[$stat['statType']] = $stat['value'];
			}
			
			$players = array();
			// Table "players"
			foreach ($match['fellowPlayers']['array'] as $player) {
				$players[] = array (	
					"gameId" => $match['gameId'],
					"summonerId" => $player['summonerId'],
					"teamId" => $player['teamId'],
					"championId" => $player['championId'],
					"dataVersion" => "2"
				);
			}
			// Adding the player that we're checking (he isn't in the table)
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
			
			foreach ($req as $request) {
				//echo "Request:<br>".$request;
				$queries = securedInsert($pdo, $req);
				//echo $queries."<br><br>";
			}
			
		} // END foreach match
		
	} // END foreach player

	$c = curl_init();
	echo getSummonerByName($c, "euw", "Protectator")."<br><br>";
	
	$region = "euw";
	$name = "Protectator";
	
	$json = getSummonerByName($c, $region, $name);
	$jsonArray = json_decode($json, true);
	$aId = $jsonArray['acctId'];
	$sId = $jsonArray['summonerId'];
	$infos = array (
		"region" => $region,
		"summonerId" => $sId,
		"accountId" => $aId,
		"name" => $name
	);
	$request = "INSERT INTO usersToTrack ".buildInsert($infos)." ON DUPLICATE KEY UPDATE name = '".$name."';";
	echo $request;
	echo securedInsert($pdo, $request); // Returns the number of affected rows
	
	curl_close($c);
}
?>