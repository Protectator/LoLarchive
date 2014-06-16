What is LoLarchive
==================

LoLarchive is a website that keeps track of each game of League of Legends of you and your friends (or every person you want !). Its main goal is to have a trace of your performances and 

How it works
============

LoLarchive is written mostly in PHP and is designed to run with a database and a job scheduler (like cron) that queries Riot Games' API for informations and games about summoners you want to see every few minutes. So you will also need a Riot Games' API key (developement or production, both work; but there are huge advantages with a production key)

Demo
====

You can see what it looks like on http://lolarchive.protectator.ch
I'm only tracking a few summoners here (me and a few friends), so it's normal you aren't able to find yourself. To test it, try for example a search on EUW of "Protectator".

License
=======

LoLarchive is distributed under [GNU Affero GPL License](http://www.gnu.org/licenses/agpl-3.0.en.html).