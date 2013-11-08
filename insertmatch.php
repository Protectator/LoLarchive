<?php
/*
This page is now deprecated. It was used with the first versions of LoLarchive, when an API wasn't required.
Feel free too look through this code. However, it's quite a mess and nothing's documented.
So, everything is at your own right. I also normally won't provide support on how to use this.
*/
	header('Access-Control-Allow-Origin: *');
	$connect = mysql_connect("localhost", "lolk", "fnu");
	mysql_select_db("lolking", $connect) or die("erreur select db : " . mysql_error());
	if (!empty($_POST)){
	
		echo "<u>LoLarchiver</u>";
		
		function secure($string)
		{
			// On regarde si le type de string est un nombre entier (int)
			if(ctype_digit($string))
			{
				$string = intval($string);
			}
			// Pour tous les autres types
			else
			{
				$string = mysql_real_escape_string($string);
				$string = addcslashes($string, '%_');
			}
			return $string;
		}
		
		foreach ($_POST['fnu'] as &$match) {
			foreach ($match as &$carac) {
				$carac = secure($carac);
			}
		}
		
		$ver = secure($_POST['version']);
		$src = secure($_POST['source']);
		
		if (($src == "lolking") && (intval($ver) >= 1)) {
			
			$ip = "'".$_SERVER['REMOTE_ADDR']."'";
		
			/*for ($i = 0; $i < count($_POST['fnu']); $i++) {
				$_POST['fnu'][$i]['username'] = utf8_encode($_POST['fnu'][$i]['username']);
			}*/
				
			/* Insertion/update user */
			$req = "INSERT INTO users (id, user, region, ip) VALUES ('".$_POST['fnu'][0]['user']."', '".$_POST['fnu'][0]['username']."', '".$_POST['fnu'][0]['region']."', ".$ip.")
			ON DUPLICATE KEY
			UPDATE
			user='".$_POST['fnu'][0]['username']."'";
			$query = mysql_query($req, $connect) or die("Requête 1 de mise à jour id échouée : ".mysql_error());
			if (mysql_affected_rows() > 0) {echo "<br><span style=\"color: #34c81e;\">Nouvel utilisateur référencé</span>";}
			
			$num = 0;
			$datas = 0;
			foreach ($_POST['fnu'] as &$match) {
			
				/* MATCHES */
				
			
				/* Ecriture requête SQL */
				$request = "INSERT IGNORE INTO matches ";
				$request .= "(region, time, host, a1user, a1champ, a2user, a2champ, a3user, a3champ, a4user, a4champ, a5user, a5champ, ";
				$request .= "b1user, b1champ, b2user, b2champ, b3user, b3champ, b4user, b4champ, b5user, b5champ, type, duration, awins, a1id, a2id, a3id, a4id, a5id, b1id, b2id, b3id, b4id, b5id, sender, ipMatches)";
				$request .= " VALUES (";
				
				/* Ajout des guillemets */
				$strings = array(&$match['region'], &$match['host'], &$match['a1user'], &$match['a1champ'], &$match['a2user'], &$match['a2champ'], &$match['a3user'], &$match['a3champ'], &$match['a4user'], &$match['a4champ'], &$match['a5user'], &$match['a5champ'], &$match['b1user'], &$match['b1champ'], &$match['b2user'], &$match['b2champ'], &$match['b3user'], &$match['b3champ'], &$match['b4user'], &$match['b4champ'], &$match['b5user'], &$match['b5champ'], &$match['mode'], &$match['duration'], &$match['a1id'], &$match['a2id'], &$match['a3id'], &$match['a4id'], &$match['a5id'], &$match['b1id'], &$match['b2id'], &$match['b3id'], &$match['b4id'], &$match['b5id']);
				foreach ($strings as &$i) {
					$i = "'".$i."'";
				}
			   
				$values = array($match['region'], "'".$match['date_final']."'", $match['host'], $match['a1user'], $match['a1champ'], $match['a2user'], $match['a2champ'], $match['a3user'], $match['a3champ'], $match['a4user'], $match['a4champ'], $match['a5user'], $match['a5champ'], $match['b1user'], $match['b1champ'], $match['b2user'], $match['b2champ'], $match['b3user'], $match['b3champ'], $match['b4user'], $match['b4champ'], $match['b5user'], $match['b5champ'], $match['mode'], $match['duration'], $match['win'], $match['a1id'], $match['a2id'], $match['a3id'], $match['a4id'], $match['a5id'], $match['b1id'], $match['b2id'], $match['b3id'], $match['b4id'], $match['b5id'], $match['user'], $ip);

				/* Insertion des valeurs dans la requête */
				$request .= implode(", ", $values);
				$request .= ");";
				
				//var_dump($request);
				
				$query = mysql_query($request, $connect) or die("<u>LoLarchiver</u><br>".$num." matchs enregistrés."."<br>erreur 2 query : ".mysql_error());
				$num = $num + mysql_affected_rows();
				//$nb_result = mysql_num_rows($query);
				
				
				/* DATAS */

				/* Ecriture requête SQL */
				$request = "INSERT IGNORE INTO data ";
				$request .= "(user, host, time, region, gold, kills, deaths, assists, minions, ss1, ss2, item1, item2, item3, item4, item5, item6, ";
				$request .= "killstreak, timedead, turrets, damageDealt, damageTaken, healingDone, ipData)";
				$request .= " VALUES (";
				
				/* Ajout des guillemets */
				$strings = array(&$match['user'], &$match['date_final'],  &$match['gold'], &$match['kills'], &$match['deaths'], &$match['assists'], &$match['minions'], &$match['ss1'], &$match['ss2'], &$match['item1'], &$match['item2'], &$match['item3'], &$match['item4'], &$match['item5'], &$match['item6'], &$match['killstreak'], &$match['timedead'], &$match['turrets'], &$match['dmgDealt'], &$match['dmgTaken'], &$match['healingDone']);
				foreach ($strings as &$i) {
					$i = "'".$i."'";
				}
				
				$values = array($match['user'], $match['host'], $match['date_final'], $match['region'], $match['gold'], $match['kills'], $match['deaths'], $match['assists'], $match['minions'], $match['ss1'], $match['ss2'], $match['item1'], $match['item2'], $match['item3'], $match['item4'], $match['item5'], $match['item6'], $match['killstreak'], $match['timedead'], $match['turrets'], $match['dmgDealt'], $match['dmgTaken'], $match['healingDone'], $ip);

				/* Insertion des valeurs dans la requête */
				$request .= implode(", ", $values);
				$request .= ");";
				
				$query = mysql_query($request) or die("<br>".$datas." données joueur enregistrés."."<br>erreur 3 query : ".mysql_error());
				$datas = $datas + mysql_affected_rows();
				
			}
			if ($num > 0) {$num = "<span style=\"color: #34c81e;\">".$num."</span>";}
			if ($datas > 0) {$datas = "<span style=\"color: #34c81e;\">".$datas."</span>";}
			echo "<br>".$num." matchs enregistrés avec succès.";
			echo "<br>".$datas." données joueur enregistrés avec succès.";
			
		} else {
			echo "Vous utilisez une version de LoLarchiver obselète.<br>Téléchargez la dernière version <a href='http://protectator.ch/lolarchive/lolhistory.user.js'>ICI</a>";
		}
		
	} else {
		echo("Seems to work.<br>");
		echo("But wtf are you doing here ?");
	}
	mysql_close($connect);
?>