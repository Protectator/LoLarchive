-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le : Mar 04 Mars 2014 à 14:50
-- Version du serveur: 5.5.31
-- Version de PHP: 5.3.10-1ubuntu3.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `lolarchivedev`
--

-- --------------------------------------------------------

--
-- Structure de la table `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `gameId` bigint(20) unsigned NOT NULL,
  `summonerId` bigint(20) unsigned NOT NULL,
  `region` varchar(8) NOT NULL,
  `goldEarned` mediumint(8) unsigned NOT NULL,
  `championsKilled` smallint(5) unsigned NOT NULL,
  `numDeaths` smallint(5) unsigned NOT NULL,
  `assists` smallint(5) unsigned NOT NULL,
  `minionsKilled` smallint(5) unsigned NOT NULL,
  `spell1` tinyint(3) unsigned NOT NULL,
  `spell2` tinyint(3) unsigned NOT NULL,
  `item0` smallint(5) unsigned NOT NULL,
  `item1` smallint(5) unsigned NOT NULL,
  `item2` smallint(5) unsigned NOT NULL,
  `item3` smallint(5) unsigned NOT NULL,
  `item4` smallint(5) unsigned NOT NULL,
  `item5` smallint(5) unsigned NOT NULL,
  `item6` smallint(5) unsigned NOT NULL,
  `largestMultiKill` smallint(5) unsigned NOT NULL,
  `largestKillingSpree` smallint(5) unsigned NOT NULL,
  `turretsKilled` tinyint(3) unsigned NOT NULL,
  `totalHeal` mediumint(8) unsigned NOT NULL,
  `invalid` bit(1) NOT NULL,
  `totalDamageDealtToChampions` mediumint(8) unsigned NOT NULL,
  `physicalDamageDealtToChampions` mediumint(8) unsigned NOT NULL,
  `magicDamageDealtToChampions` mediumint(8) unsigned NOT NULL,
  `trueDamageDealtToChampions` mediumint(8) unsigned NOT NULL,
  `totalDamageDealt` mediumint(8) unsigned NOT NULL,
  `physicalDamageDealtPlayer` mediumint(8) unsigned NOT NULL,
  `magicDamageDealtPlayer` mediumint(8) unsigned NOT NULL,
  `trueDamageDealtPlayer` mediumint(8) unsigned NOT NULL,
  `totalDamageTaken` mediumint(8) unsigned NOT NULL,
  `physicalDamageTaken` mediumint(8) unsigned NOT NULL,
  `magicDamageTaken` mediumint(8) unsigned NOT NULL,
  `trueDamageTaken` mediumint(8) unsigned NOT NULL,
  `sightWardsBought` smallint(5) unsigned NOT NULL,
  `visionWardsBought` smallint(5) unsigned NOT NULL,
  `neutralMinionsKilled` smallint(5) unsigned NOT NULL,
  `neutralMinionsKilledYourJungle` smallint(5) unsigned NOT NULL,
  `neutralMinionsKilledEnemyJungle` smallint(5) unsigned NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `wardPlaced` smallint(5) unsigned NOT NULL,
  `wardKilled` smallint(5) unsigned NOT NULL,
  `summonerLevel` tinyint(3) unsigned NOT NULL,
  `totalTimeCrowdControlDealt` mediumint(8) unsigned NOT NULL,
  `largestCriticalStrike` smallint(5) unsigned NOT NULL,
  `win` bit(1) NOT NULL,
  `barracksKilled` tinyint(3) unsigned NOT NULL COMMENT 'Number of destroyed inhibitors',
  `totalScoreRank` tinyint(3) unsigned NOT NULL,
  `objectivePlayerScore` smallint(5) unsigned NOT NULL,
  `victoryPointTotal` smallint(5) unsigned NOT NULL,
  `nodeCaptureAssist` tinyint(3) unsigned NOT NULL,
  `totalPlayerScore` smallint(5) unsigned NOT NULL,
  `nodeCapture` tinyint(3) unsigned NOT NULL,
  `nodeNeutralize` tinyint(3) unsigned NOT NULL,
  `nodeNeutralizeAssist` tinyint(3) unsigned NOT NULL,
  `teamObjective` tinyint(3) unsigned NOT NULL,
  `combatPlayerScore` smallint(5) unsigned NOT NULL,
  `consumablesPurchased` tinyint(3) unsigned NOT NULL,
  `firstBlood` bit(1) NOT NULL,
  `spell1Cast` mediumint(8) unsigned NOT NULL,
  `spell2Cast` mediumint(8) unsigned NOT NULL,
  `spell3Cast` mediumint(8) unsigned NOT NULL,
  `spell4Cast` mediumint(8) unsigned NOT NULL,
  `summonSpell1Cast` tinyint(3) unsigned NOT NULL,
  `summonSpell2Cast` tinyint(3) unsigned NOT NULL,
  `superMonsterKilled` smallint(5) unsigned NOT NULL,
  `timePlayed` int(10) unsigned NOT NULL,
  `unrealKills` smallint(5) unsigned NOT NULL,
  `doubleKills` smallint(5) unsigned NOT NULL,
  `tripleKills` smallint(5) unsigned NOT NULL,
  `quadraKills` smallint(5) unsigned NOT NULL,
  `pentaKills` smallint(5) unsigned NOT NULL,
  `nexusKilled` bit(1) NOT NULL,
  `gold` mediumint(8) unsigned NOT NULL,
  `itemsPurchased` tinyint(3) unsigned NOT NULL,
  `numItemsBought` tinyint(3) unsigned NOT NULL,
  `dataVersion` tinyint(4) NOT NULL COMMENT '1: lolhistory | 2: mashape | 3: riotgames',
  `dataIp` varchar(16) NOT NULL COMMENT 'ip adress that registered this entry',
  `dataStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp of the last edition of the entry',
  PRIMARY KEY (`gameId`,`summonerId`,`region`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
