<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MyShelfie implementation : © Pietro Luigi Porcedda pietro.l.porcedda@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 10 )
    ),
    

    10 => array(
    		"name" => "choosingTiles",
    		"description" => clienttranslate('${actplayer} must choose one or more item tiles from the living room'),
    		"descriptionmyturn" => clienttranslate('${you} must choose one or more item tiles from the living room'),
    		"type" => "activeplayer",
            "args" => "argChoosingTiles",
    		"possibleactions" => array("chooseTiles"),
    		"transitions" => array("chooseTiles" => 20,"zombiePass" => 30)
    ),

    20 => array(
        "name" => "fillingShelf",
        "description" => clienttranslate('${actplayer} must choose where to insert the chosen tile(s) on his/her shelf'),
        "descriptionmyturn" => clienttranslate('${you} must choose where to insert the chosen tile(s) on your shelf'),
        "type" => "activeplayer",
        "args" => "argFillingShelf",
        "possibleactions" => array("insertTiles","undoTileSelection"),
        "transitions" => array("insertTiles" => 30, "undoTileSelection" => 10)
    ),

    30 => array(
        "name" => "nextPlayer",
        "description" => clienttranslate('Ending turn..'),
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextTurn" => 10,"playerScoring" => 31)
    ),

    31 => array(
        "name" => "playerFinalScoring",
        "description" => clienttranslate('Calculating ${player_name} score..'),
        "type" => "game",
        "args" => "argPlayerFinalScoring",
        "action" => "stPlayerFinalScoring",
        "transitions" => array("nextScoring" => 31, "gameEnd" => 99)
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



