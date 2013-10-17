<?php

require_once('includes/functions.include.php');


/*Initialisation de la ressource curl*/


/*
TEMP
*/

$connect = mysql_connect("localhost", "updateScript", "8pGJNjMN9QP4Rwbq");
mysql_select_db("lolarchive", $connect) or die("erreur select db : " . mysql_error());

/*
END TEMP
*/

// On r�cup�re les utilisateurs � actualiser
$req = "SELECT * FROM usersToTrack";
$query = mysql_query($req, $connect) or die("Requ�te SELECT 1 �chou�e : ".mysql_error());

// Si la requ�te retourne des r�sultats
if (mysql_num_rows($query) > 0) {
	while ($row = mysql_fetch_array($query)) { // Pour chaque joueur
	
		// Pr�paration de la requ�te cURL
		$region = mb_strtoupper($row['region']);
		$sId = $row['summonerId'];
		$aId = $row['accountId'];
		$c = curl_init();
		$json = getRecentGames($c, $region, $aId);
		curl_close($c);
		// On transforme le json en un Array
		$array = json_decode($json, true);
		$matches = $array['gameStatistics']['array'];
		
		$months = array (
			"Jan" => '01',
			"Feb" => '02',
			"Mar" => '03',
			"Apr" => '04',
			"May" => '05',
			"Jun" => '06',
			"Jul" => '07',
			"Aug" => '08',
			"Sep" => '09',
			"Oct" => '10',
			"Nov" => '11',
			"Dec" => '12'
		);

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
				"estimatedDuration" => '0', // TODO : Estimer la dur�e d'une game en fonction des IP gagn�s
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
			$keys = implode(', ', array_keys($games));
			$values = implode('\', \'', array_values($games));
			$req[0] = "INSERT INTO games (".$keys.") VALUES ('".$values."')
				ON DUPLICATE KEY
				UPDATE time='".$time."';";
				
			// Request on the "data" table
			$keys = implode(', ', array_keys($data));
			$values = implode('\', \'', array_values($data));
			$req[1] = "INSERT INTO data (".$keys.") VALUES ('".$values."')
				ON DUPLICATE KEY
				UPDATE estimatedDuration = '0';";
			
			// Request on the "players" table
			$keys = implode(', ', array_keys($players[0]));
			$rows = array();
			$requestString = "INSERT INTO players (".$keys.") VALUES ";
			foreach ($players as $pl) {
				$values = implode('\', \'', array_values($pl));
				$rows[] = "('".$values."')";
			}
			$requestString = $requestString.implode(", ", $rows);
			$requestString = $requestString."
				ON DUPLICATE KEY
				UPDATE dataVersion = '2';";
			$req[2] = $requestString;
				
			
			foreach ($req as $request) {
				// echo "Request:<br>"$request.;
				// $query = mysql_query($request, $connect) or die("<br>Requ�te INSERT �chou�e : ".mysql_error());
				// echo "<br>".mysql_affected_rows()." affected rows";
			}
			
		} // END foreach match
		
	} // END foreach player

	$c = curl_init();
	echo getSummonerByName($c, "euw", "Lachainone");
	curl_close($c);
	
	if (class_exists('PDO')) {
		echo ABSPATH;
	}
}
?>