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

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => [
            "id"=> 10,
            "name" => totranslate("Number of turns"),
            "type" => "int"
        ],

        "green_drawn" => [
            "id"=> 11,
            "name" => totranslate("Cats (green) tiles drawn"),
            "type" => "int"
        ],

        "white_drawn" => [
            "id"=> 12,
            "name" => totranslate("Books (white) tiles drawn"),
            "type" => "int"
        ],

        "orange_drawn" => [
            "id"=> 13,
            "name" => totranslate("Games (orange) tiles drawn"),
            "type" => "int"
        ],

        "blue_drawn" => [
            "id"=> 14,
            "name" => totranslate("Frames (blue) tiles drawn"),
            "type" => "int"
        ],

        "cyan_drawn" => [
            "id"=> 15,
            "name" => totranslate("Trophies (cyan) tiles drawn"),
            "type" => "int"
        ],

        "fuchsia_drawn" => [
            "id"=> 16,
            "name" => totranslate("Plants (fuchsia) tiles drawn"),
            "type" => "int"
        ],
    ),
    
    // Statistics existing for each player
    "player" => array(

        "points_common_goals" => [
            "id"=> 20,
            "name" => totranslate("Common goals points"),
            "type" => "int"
        ],

        "points_personal_goal" => [
            "id"=> 21,
            "name" => totranslate("Personal goal points"),
            "type" => "int"
        ],

        "points_tiles_groups" => [
            "id"=> 22,
            "name" => totranslate("Tile groups points"),
            "type" => "int"
        ],

        "favourite_color" => [
            "id"=> 23,
            "name" => totranslate("Favourite Item"),
            "type" => "int"
        ],
    ),

    "value_labels" => array(
		23 => array( 
			0 => clienttranslate("Cats (green)"),
			1 => clienttranslate("Books (white)"), 
			2 => clienttranslate("Games (orange)"), 
			3 => clienttranslate("Frames (blue)"), 
			4 => clienttranslate("Trophies (cyan)"), 
			5 => clienttranslate("Plants (fuchsia)")
		),
	)

);
