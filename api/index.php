<?php
/**
 * Dota Knocker API for querying Dota 2 Hero statistics from Dota Web API and
 * Firebase.
 */

// INIT

if (!isset($_GET['player_name'])) {
    returnJson(array(), 400);
}

define('VENDORPATH', '../lib/vendor/');


// FUNCTIONS

function returnJson($content, $statusCode) {
    header('Content-type: application/json', true, $statusCode);
    echo json_encode($content);
    exit;
}


// SETUP

require_once (VENDORPATH . 'firebase-php/firebaseLib.php');
require_once (VENDORPATH . 'dota2-api/config.php');


// ACT

$searchedPlayerName = $_GET['player_name'];

$firebase = new Firebase('https://dotaknocker.firebaseio.com/');

// Fetch recordings from Firebase

// IDs of recorded matches
/*
matchId
*/
$recordedMatches = json_decode($firebase->get('matches'));

// Hero data per each player
/*
playerId ->
    personas
        -> personaName
    heroes ->
        heroId ->
            useCount
*/
$recordedHeroData = json_decode($firebase->get('heroData'));


// Parse new data

$heroes = new heroes();
$heroes->parse();

$playerHeroData = array();
$playerAliases = array();

// Return data
/*
heroData ->
    heroData (for matching player)
privateCount ->
    privateCount
dotaIdCount ->
    dotaIdCount
matchCount ->
    matchCount
*/
$returnData = array();

// Fetch matches by player name from DOTA API

$matchesMapper = new matches_mapper_web();
$matchesMapper
    ->set_player_name($searchedPlayerName)
    ->set_matches_requested(1);
$matches = $matchesMapper->load();

$privateCount = 0;
$dotaIdCount = 0;

foreach ($matches as $match) {
    $matchId = $match->get('match_id');

    // Skip match if it has been recorded already
    if (in_array($matchId, $recordedMatches)) {
        continue;
    }

    $recordedMatches[] = $matchId;

    foreach ($match->get_all_slots() as $slot) {
        $dotaIdCount++;

        $dotaId = $slot->get('account_id');
        $heroId = $slot->get('hero_id');

        // Skip players who have set their profiles private and/or when
        // their hero stats are not available
        if ($dotaId == player::ANONYMOUS || $heroId == 0) {
            $privateCount++;

            continue;
        }

        $steamId = player::convert_id($dotaId);

        // Initialize hero data entry
        if (!isset($recordedHeroData[$dotaId])) {
            $recordedHeroData[$dotaId] = array(
                'personas' => array(),
                'heroes' => array(),
                'matches' => array()
            );
        }

        // Add hero to statistics
        if (!isset($recordedHeroData[$dotaId]['heroes'][$heroId])) {
            $recordedHeroData[$dotaId]['heroes'][$heroId] = 0;
        }

        $recordedHeroData[$dotaId]['heroes'][$heroId]++;

        // Add persona name to statistics
        $players_mapper_web = new players_mapper_web();
        $playerInfo = $players_mapper_web
            ->add_id($steamId)
            ->load();

        foreach ($playerInfo as $player) {
            $personaName = $player->get('personaname');

            if (!in_array(
                $personaName,
                $recordedHeroData[$dotaId]['personas'])
            ) {
                $recordedHeroData[$dotaId]['personas'][] = $personaName;
            }

            // Record aliases and heroes for matching persona
            if ($personaName === $searchedPlayerName) {
                // Add persona aliases
                foreach ($recordedHeroData[$dotaId]['personas'] as $persona) {
                    if (!in_array($persona, $playerAliases)) {
                        $playerAliases[] = $persona;
                    }
                }

                // Add heroes
                if (!isset($playerHeroData[$heroId])) {
                    // TODO
                    // $playerHeroData[]
                    //
                }
            }
        }
    }
}

// Save data back in Firebase

// Format return data
$returnData['heroData'] = $playerHeroData;
$returnData['privateCount'] = $privateCount;
$returnData['dotaIdCount'] = $dotaIdCount;
$returnData['matchCount'] = count($recordedMatches);
$returnData['aliases'] = $playerAliases;

returnJson($returnData, 200);
