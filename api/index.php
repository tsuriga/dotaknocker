<?php
/**
 * Dota Knocker API for querying Dota 2 Hero statistics from Dota Web API
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

require_once (VENDORPATH . 'dota2-api/config.php');

$pdo = new PDO('sqlite:dotaknocker.db');
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_COLUMN);


// ACT

$searchedPlayerName = $_GET['player_name'];

// Recorded data
$records['match'] = $pdo->query('SELECT id FROM match')
    ->fetchAll();
$records['player'] = $pdo->query('SELECT id FROM player')
    ->fetchAll();

// New data to insert to database
$inserts = array(
    'match' => array(),
    'player' => array(),
    'heroUse' => array(),
    'persona' => array()
);
$updates = array(
    'heroUse' => array(),
);

$heroes = new heroes();
$heroes->parse();

// Fetch matches by player name from DOTA API

$matchesMapper = new matches_mapper_web();
$matchesMapper
    ->set_player_name($searchedPlayerName)
    ->set_matches_requested(25);
$matches = $matchesMapper->load();

$privateCount = 0;

foreach ($matches as $match) {
    $matchId = $match->get('match_id');

    // Skip match if it has been recorded already
    if (in_array($matchId, $records['match'])) {
        continue;
    }

    $inserts['match'][] = array('id' => $matchId);

    foreach ($match->get_all_slots() as $slot) {
        $dotaId = $slot->get('account_id');
        $heroId = $slot->get('hero_id');

        // Skip players who have set their profiles private and/or when
        // their hero stats are not available
        if ($dotaId == player::ANONYMOUS || $heroId == 0) {
            $privateCount++;

            continue;
        }

        if (!in_array($dotaId, $records['player'])) {
            $inserts['player'][] = array('id' => $dotaId);
        }

        $steamId = player::convert_id($dotaId);

        // Initialize hero use entry
        $heroDataForAccount =
            $pdo->query(
                sprintf(
                    'SELECT
                        heroId, useCount
                    FROM
                        heroUse
                    WHERE
                        playerId = %s
                    AND
                        heroId = %s',
                    $pdo->quote($dotaId),
                    $pdo->quote($heroId)
                )
            )->fetch(PDO::FETCH_ASSOC);

        if (!$heroDataForAccount) {
            $inserts['heroUse'][] = array(
                'playerId' => $dotaId,
                'heroId' => $heroId,
                'useCount' => 1
            );
        } else {
            $updates['heroUse'][] = array(
                'playerId' => $dotaId,
                'heroId' => $heroId,
                'useCount' => $heroDataForAccount['useCount'] + 1
            );
        }

        // Add persona name to statistics
        $players_mapper_web = new players_mapper_web();
        $playerInfo = $players_mapper_web
            ->add_id($steamId)
            ->load();

        $personaDataForAccount =
            $pdo->query(
                sprintf(
                    'SELECT name FROM persona WHERE playerId = %s',
                    $pdo->quote($dotaId)
                )
            )->fetchAll();

        foreach ($playerInfo as $player) {
            $personaName = $player->get('personaname');

            if (!in_array($personaName, $personaDataForAccount)) {
                $inserts['persona'][] = array(
                    'name' => $personaName,
                    'playerId' => $dotaId
                );
            }
        }
    }
}

// Save data back into the database
foreach ($inserts as $table => $values) {
    if ($values[0] == null) {
        continue;
    }

    foreach ($values as $entry) {
        $pdo->query(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                implode(', ', array_keys($entry)),
                implode(', ',
                    array_map(function ($value) {
                        global $pdo;
                        return $pdo->quote($value);
                    }, $entry)
                )
            )
        );
    }
}

foreach ($updates['heroUse'] as $useData) {
    $pdo->query(
        sprintf(
            'UPDATE heroUse SET useCount = %s WHERE heroId = %s',
            $useData['useCount'],
            $useData['heroId']
        )
    );
}


// FETCH RETURN DATA

$playerId =
    $pdo->query(
        sprintf(
            'SELECT playerId FROM persona WHERE name LIKE %s',
            $pdo->quote($searchedPlayerName)
        )
    )->fetch();

// Played heroes
$heroResult = $playerId ?
    $pdo->query(
        sprintf(
            'SELECT heroId, useCount FROM heroUse WHERE playerId = %s',
            $pdo->quote($playerId)
        )
    )->fetchAll(PDO::FETCH_ASSOC):
    array();
$heroData = array();

foreach ($heroResult as $hero) {
    $heroData[$hero['heroId']] = (int)$hero['useCount'];
}

// Used aliases
$playerAliases = $playerId ?
    $pdo->query(
        sprintf(
            'SELECT name FROM persona WHERE playerId = %s',
            $pdo->quote($playerId)
        )
    )->fetchAll():
    array();

// Format return data
$returnData = array();
$returnData['heroes'] = $heroData;
$returnData['privateCount'] = $privateCount;
$returnData['newDotaIdCount'] = count($inserts['player']);
$returnData['totalDotaIdCount'] =
    count($records['player']) + count($inserts['player']);
$returnData['newMatchCount'] = count($inserts['match']);
$returnData['totalMatchCount'] =
    count($records['match']) + count($inserts['match']);
$returnData['aliases'] = $playerAliases;

returnJson($returnData, 200);
