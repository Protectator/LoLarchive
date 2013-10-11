<?php

require_once('includes/functions.include.php');

/*Initialisation de la ressource curl*/
$c = curl_init();

$key = "18d2e10ecf21b6e12fb81182fa4cf9f1718c873c";

/*
TEMP
*/

$connect = mysql_connect("localhost", "updateScript", "8pGJNjMN9QP4Rwbq");
mysql_select_db("lolarchive", $connect) or die("erreur select db : " . mysql_error());

/*
END TEMP
*/

// On récupère les utilisateurs à actualiser
$req = "SELECT * FROM usersToTrack";
$query = mysql_query($req, $connect) or die("Requête SELECT 1 échouée : ".mysql_error());

// Si la requête retourne des résultats
if (mysql_num_rows($query) > 0) {
	while ($row = mysql_fetch_array($query)) { // Pour chaque joueur
	
		// Préparation de la requête cURL
		$region = mb_strtoupper($row['region']);
		$sId = $row['summonerId'];
		$aId = $row['accountId'];
		$url = "http://legendaryapi.com/api/v1.0/".$region."/summoner/getRecentGames/".$aId;
		curl_setopt($c, CURLOPT_URL,$url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		// Exécution de la requête cURL
		$json = trim(curl_exec($c));
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
				"time" => $time,
				"type" => $match['queueType'],
				"subType" => $match['subType'],
				"duration" => 0, // TODO : Estimate game duration in function of PI won
				"sender" => 0
			);
			
			// Table "data"
			$data = array (
				"gameId" => $match['gameId'],
				"summonerId" => $sId,
				"mapId" => $match['gameMapId'],
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
				"estimatedDuration" => '0',
				"boostIpEarned" => $match['boostIpEarned'],
				"skinIndex" => $match['skinIndex']
			);
			
			// Parcourir l'array "STATISTICS"
			foreach ($stat as $match['statistics']['array']) {
				$data[$stat['statType']] => $stat['value'];
			}
		}

		echo $json;
		
		/*$req = "INSERT INTO games (id, user, region, ip) VALUES ('".$_POST['fnu'][0]['user']."', '".$_POST['fnu'][0]['username']."', '".$_POST['fnu'][0]['region']."', ".$ip.")
			ON DUPLICATE KEY
			UPDATE
			user='".$_POST['fnu'][0]['username']."'";*/
	}
}
?>