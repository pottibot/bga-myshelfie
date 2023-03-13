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


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class MyShelfie extends Table {

    /* ------------- */
    /* --- SETUP --- */
    /* ------------- */
    #region

	function __construct() {

        parent::__construct();
        
        self::initGameStateLabels(array( 
            "first_player" => 10,
            "common_goal_1" => 11,
            "common_goal_2" => 12, 
            "is_last_round" => 13,
            //    "my_first_game_variant" => 100,
        ));        
	}
	
    protected function getGameName() {
        return "myshelfie";
    }	

    protected function setupNewGame($players, $options = array()) {

        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
        $personalGoalsPool = range(1,12);
        shuffle($personalGoalsPool);
 
        // Create players
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_personal_goal) VALUES ";
        $values = array();
        foreach($players as $player_id => $player) {
            $personalGoalCard = array_shift($personalGoalsPool);
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."',$personalGoalCard)";
        }

        $sql .= implode($values,',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players,$gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        self::setGameStateInitialValue('first_player',self::getUniqueValueFromDb("SELECT player_id FROM player WHERE player_no = 1"));

        self::setGameStateInitialValue('is_last_round',0);

        // SET COMMON GOAL CARDS
        $commonGoalsPool = range(1,12);
        shuffle($commonGoalsPool);
        
        $cg1 = array_shift($commonGoalsPool);
        $cg2 = array_shift($commonGoalsPool);
        self::setGameStateInitialValue('common_goal_1', $cg1);
        self::setGameStateInitialValue('common_goal_2', $cg2);

        $tokenPool = [2,4,6,8];
        if (self::getPlayersNumber()<4) unset($tokenPool[0]);
        if (self::getPlayersNumber()<3) unset($tokenPool[2]);
        $tokenPool = array_values($tokenPool);

        // SET TOKENS
        $sql = "INSERT INTO token (id, n, `location`) VALUES ";
        $values = ["(1,1,'board')"];
        $j = 2;
        for ($i=0; $i < count($tokenPool); $i++) { 
            $n = $tokenPool[$i];
            $values[] = "($j,$n,'common_goal_$cg1')";
            $j++;
        }
        for ($i=0; $i < count($tokenPool); $i++) { 
            $n = $tokenPool[$i];
            $values[] = "($j,$n,'common_goal_$cg2')";
            $j++;
        }
        $sql .= implode($values,',');
        self::DbQuery($sql);
        
        // Init game statistics
        self::initStat('table','turns_number',0);
        self::initStat('table','green_drawn',0);
        self::initStat('table','white_drawn',0);
        self::initStat('table','orange_drawn',0);
        self::initStat('table','blue_drawn',0);
        self::initStat('table','cyan_drawn',0);
        self::initStat('table','fuchsia_drawn',0);

        self::initStat('player','points_common_goals',0);
        self::initStat('player','points_personal_goal',0);
        self::initStat('player','points_tiles_groups',0);
        self::initStat('player','favourite_color',0);

        // TODO: setup the initial game situation here
        
        // POPULATE DB WITH ITEM TILE (ALL IN BAG LOCATION)
        $sql = "INSERT INTO tile (`id`, `location`, `position_x`, `position_y`, `color`, `type`) VALUES ";
        $values = array();

        $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];

        $id = 0;
        for ($i=0; $i < 6; $i++) { 
            for ($j=0; $j < 23; $j++) { 
                
                $color = $colorPool[$i];
                $type = $j%3;

                $values[] = "($id, 'bag', NULL, NULL, '$color', $type)";

                $id += 1;
            }
        }

        $sql .= implode($values,',');
        self::DbQuery($sql);

        // test translation in stats
        clienttranslate("Cats (green)");
        clienttranslate("Books (white)");
        clienttranslate("Games (orange)");
        clienttranslate("Frames (blue)");
        clienttranslate("Trophies (cyan)");
        clienttranslate("Plants (fuchsia)");

        self::refillBoard();

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();
    }

    protected function getAllDatas() {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();  // !! RETURN INFORMATION VISIBILE ONLY TO THIS PLAYER !!
    
        $sql = "SELECT player_id id, player_score score FROM player ";

        $result['players'] = self::getCollectionFromDb( $sql );

        $personalGoal = self::getUniqueValueFromDb("SELECT player_personal_goal FROM player WHERE player_id = $current_player_id");
        $result['personalGoal'] = $personalGoal;
        if (!self::isSpectator()) {
            $result['personalGoalCoords'] = self::getPersonalGoalCoords($personalGoal);
        }

        $result['first_player'] = self::getGameStateValue('first_player');

        $cg1 = self::getGameStateValue('common_goal_1');
        $cg2 = self::getGameStateValue('common_goal_2');
        $result['commonGoals'] = array_filter($this->common_goals, function($key) use($cg1,$cg2) { return $key == $cg1 || $key == $cg2; }, ARRAY_FILTER_USE_KEY);
        //$result['commonGoals'][$cg1] = ['tooltip' => clienttranslate($this->common_goals[$cg1]['tooltip'])];
        //$result['commonGoals'][$cg2] = ['tooltip' => clienttranslate($this->common_goals[$cg2]['tooltip'])];


        /* [
            self::getGameStateValue('common_goal_1') => $this->common_goals[self::getGameStateValue('common_goal_1')]['tooltip'],
            self::getGameStateValue('common_goal_2') => $this->common_goals[self::getGameStateValue('common_goal_2')]['tooltip']] */
        $result['tokens'] = self::getObjectListFromDb("SELECT * FROM token");

        $result['validSquares'] = self::getAllValidSquares();
        $result['boardTiles'] = self::getObjectListFromDb("SELECT id, position_x x, position_y y, color, `type` FROM tile WHERE `location` = 'board'");
  
        $result['shelves'] = [];
        foreach (self::getPlayers() as $pid) {
            $result['shelves'][$pid] = self::getObjectListFromDb("SELECT id, position_x x, position_y y, color, `type` FROM tile WHERE `location` = '$pid'");
        }
        


        return $result;
    }


    function getGameProgression() {
        // TODO: compute and return the game progression

        $sql = "SELECT COUNT(*) as amt
                FROM tile
                WHERE `location` != 'board' AND `location` != 'bag'
                GROUP BY `location`
                ORDER BY amt DESC
                LIMIT 1";
        $mostTileShelf = self::getUniqueValueFromDb($sql);
        if (is_null($mostTileShelf)) $mostTileShelf = 0;

        return ($mostTileShelf / 30) * 100;
    }

    #endregion

    /* ----------------------- */
    /* --- UTILITY METHODS --- */
    /* ----------------------- */
    #region

    /* function test() {
        $id = 2352472;
        $y = 1;
        $x = 4;
        $shelf = self::getShelfMatrix($id);

        self::trace("// START OF GROUP FINDING ALGO");
        $group = self::groupFromTileAt($y,$x,$shelf,["$y,$x"]);
        self::dump("// GROUP SIZE",count($group));
    } */

    /* function testCommonGoals($cg1, $cg2) {
        $prevcg1 = self::getGameStateValue("common_goal_1");
        $prevcg2 = self::getGameStateValue("common_goal_2");

        self::setGameStateValue("common_goal_1",$cg1);
        self::setGameStateValue("common_goal_2",$cg2);

        self::dbQuery("UPDATE token SET `location` = 'common_goal_$cg1' WHERE `location` = 'common_goal_$prevcg1'");
        self::dbQuery("UPDATE token SET `location` = 'common_goal_$cg2' WHERE `location` = 'common_goal_$prevcg2'");
    } */

    /* function testPersonalGoal($pg) {
        $id = self::getCurrentPlayerId();
        self::dbQuery("UPDATE player SET player_personal_goal = $pg WHERE player_id = $id");
    } */

    /* function populateShelves() {

        $bagTiles = self::getObjectListFromDb("SELECT id FROM tile WHERE `location`='bag'",true);
        shuffle($bagTiles);

        foreach (self::getPlayers() as $pid) {
            for ($i=0; $i < 5; $i++) { 
                
                $tiles = [];
                $amt = ($i==0)? 5:6; // bga_rand(1,6);
                for ($j=0; $j < $amt; $j++) {
                    $tiles[] = array_shift($bagTiles);
                }

                self::slotTilesInColumn($tiles,$i,$pid);
            }
        }

    } */

    function getPlayers() {
        return self::getObjectListFromDb("SELECT player_id FROM player",true);
    }

    function getTileNameFromColor($col) {
        switch ($col) {
            case 'white': return clienttranslate('white tile');
                break;
            case 'orange': return clienttranslate('orange tile');
                break;
            case 'cyan': return clienttranslate('cyan tile');
                break;
            case 'blue': return clienttranslate('blue tile');
                break;
            case 'green': return clienttranslate('green tile');
                break;
            case 'fuchsia': return clienttranslate('fuchsia tile');
                break;
        }
    }

    function slotTilesInColumn($tiles,$column,$player) {

        // check action validity (column not full);
        $y = 0;
        $firstFreeSlot = self::getCollectionFromDb("SELECT position_y FROM tile WHERE `location` = '$player' AND position_x = $column",true);
        if (empty($firstFreeSlot)) $y = 5;
        else $firstFreeSlot = min($y)-1;

        foreach ($tiles as $tile) {
            self::dbQuery("UPDATE tile SET position_x = $column, position_y = $y, `location` = '$player' WHERE id = $tile");
            $y--;
        }
    }

    // get all tiles in specified location. can be 'bag', 'board' or a player id
    function getTilesIn($location) {
        // TODO security checks

        return self::getObjectListFromDb("SELECT id, position_x x, position_y y, color, `type` FROM tile WHERE `location` = '$location'");
    }

    function getAllValidSquares() {
        /* $baseTiles = [
            [],
            [3,4],
            [3,4,5],
            [2,3,4,5,6,7],
            [1,2,3,4,5,6,7],
            [2,3,4,5,6],
            [3,4,5],
            [4,5],
            []
        ] */

        $baseTiles = ['1,3','1,4',
                      '2,3','2,4','2,5',
                      '3,2','3,3','3,4','3,5','3,6','3,7',
                      '4,1','4,2','4,3','4,4','4,5','4,6','4,7',
                      '5,1','5,2','5,3','5,4','5,5','5,6',
                      '6,3','6,4','6,5',
                      '7,4','7,5'];
        $fourpTiles = ['0,4','1,5','3,1','4,0','4,8','5,7','7,3','8,4'];
        $threepTiles = ['0,3','2,2','2,6','3,8','5,0','6,2','6,6','8,5'];

        $playersNum = self::getPlayersNumber();

        $allTiles = $baseTiles;
        if ($playersNum > 2) $allTiles = array_merge($allTiles,$threepTiles);
        if ($playersNum > 3) $allTiles = array_merge($allTiles,$fourpTiles);

        return $allTiles;
    }

    function refillBoard() {

        self::dbQuery("UPDATE tile SET `location` = 'bag', position_x = NULL, position_y = NULL WHERE `location` = 'board'");

        self::notifyAllPlayers('clearBoard','',[]);

        //$occupiedTiles = self::getObjectListFromDb("SELECT CONCAT(position_y,',',position_x) as pos FROM tile WHERE `location` = 'board'",true);

        $allTiles = self::getAllValidSquares();
        
        $drawnTiles = [];
        for ($y=0; $y < 9; $y++) { 
            for ($x=0; $x < 9; $x++) {

                $position = "$y,$x";

                if (in_array($position,$allTiles) /* && !in_array($position,$occupiedTiles) */) {

                    $drawnTiles[$position] = self::drawTile($y,$x);

                    if (is_null($drawnTiles[$position])) {
                        unset($drawnTiles[$position]);
                    }
                }
            }
        }

        self::notifyAllPlayers('refillBoard',clienttranslate("The living room board is refilled with Item tiles"),[
            'tiles' => $drawnTiles
        ]);
    }

    // draw item tile from bag and position it on board at given coordinate (conceptually, no notification sent). returns details on tile drawn.
    // writing y,x in reverse as its more intuitive to think row (x), column(y) instead of vice versa 
    function drawTile($y,$x) {

        if ($y < 0 || $y > 9 || $x < 0 || $x > 9) throw new BgaSystemException("Invalid board position");

        $tilesBag = self::getObjectListFromDb("SELECT id, color, `type` FROM tile WHERE `location` = 'bag'");

        if (empty($tilesBag)) return null;

        $drawnTile = $tilesBag[bga_rand(0,count($tilesBag)-1)];

        $id = $drawnTile['id'];

        self::dbQuery("UPDATE tile SET `location` = 'board', position_x = $x, position_y = $y WHERE id = $id");

        self::incStat(1,$drawnTile['color'].'_drawn');
        return $drawnTile;
    }

    // fetches any tile in the game based on its id
    function fetchTile($tid) {
        $tile = self::getObjectFromDb("SELECT id, position_x x, position_y y, color, `type` FROM tile WHERE id = $tid");
        if (empty($tile)) throw new BgaSystemException ("Tile ($tid) does not exist");
        return $tile;
    }

    // fetches tile at specified row (y) and column (x) on the board
    function fetchTileAt($y,$x,$loc='board') {
        $tile = self::getObjectFromDb("SELECT id, position_x x, position_y y, color, `type` FROM tile WHERE position_x = $x AND position_y = $y AND `location` = '$loc'");
        // return null semantically useful
        return $tile;
    }

    // check if tile has at least one free side (it's not surrounded by any adjacent item tiles)
    function isTileFree($y,$x,$completely=false) {
    
        $left = self::fetchTileAt($y,$x-1);
        $right = self::fetchTileAt($y,$x+1);
        $top = self::fetchTileAt($y-1,$x);
        $bottom = self::fetchTileAt($y+1,$x);

        if ($completely)
            return is_null($top) && is_null($bottom) && is_null($left) && is_null($right);
        else 
            return is_null($top) || is_null($bottom) || is_null($left) || is_null($right);
    }

    function areTilesAdjacent($t1,$t2) {
        if ($t1['x'] == $t2['x'] && abs($t1['y'] - $t2['y']) == 1) return true;
        if ($t1['y'] == $t2['y'] && abs($t1['x'] - $t2['x']) == 1) return true;

        return false;
    }

    // check if tiles (objects as returned by db) are on the same line
    // mems x and y coordinates of all tiles, then remove duplicate elements from respective axis array. if array length is > 1 then not all elements where equals 
    function areTilesAlligned($tiles) {

        $xs = [];
        $ys = [];

        foreach ($tiles as $tile) {
            $xs[] = $tile['x'];
            $ys[] = $tile['y'];
        }

        if ((count(array_unique($xs)) == 1) || (count(array_unique($ys)) == 1)) return true;
        else return false;
    }

    // returns matrix 5x6 (5 coulmns, 6 rows) representing the tile colors (from 1 to 6, 0 is empty) inside the shelf of given player
    function getShelfMatrix($id) {
        $matrix = array_fill(0,6,array_fill(0,5,0));

        $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];

        for ($x=0; $x < 5; $x++) { 
            for ($y=0; $y < 6; $y++) { 
                $t = self::fetchTileAt($y,$x,$id);

                if (is_null($t)) $matrix[$y][$x] = 0; // usless?
                else $matrix[$y][$x] = array_search($t['color'],$colorPool)+1;
            }
        }

        return $matrix;
    }

    // same as getShelfMatrix, but returns a single array representing a single column
    function getShelfColumnArray($id,$x) {
        $column = array_fill(0,6,0);

        $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];

        for ($y=0; $y < 6; $y++) {
            $t = self::fetchTileAt($y,$x,$id);
            if (!is_null($t)) $column[$y] = array_search($t['color'],$colorPool)+1;
        }

        return $column;
    }

    // same as getShelfMatrix, but returns a single array representing a single row
    function getShelfRowArray($id,$y) {
        $row = array_fill(0,5,0);

        $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];

        for ($x=0; $x < 5; $x++) {
            $t = self::fetchTileAt($y,$x,$id);
            if (!is_null($t)) $row[$x] = array_search($t['color'],$colorPool)+1;
        }

        return $row;
    }

    function groupFromTileAt($y, $x, $shelfMatrix, $group = [], $stopAt = 0) {

        if (empty($group)) $group[] = "$y,$x";

        $matrixPrint = $shelfMatrix;
        foreach ($matrixPrint as &$row) {
            $row = '['.implode(',',$row).']';
        } unset($row);
        $matrixPrint = '<br>'.implode('<br>',$matrixPrint).'<br>';

        //self::trace("// GROUP FROM TILE AT ($y,$x)");
        //self::dump("// GROUP",implode('->',$group));
        //self::dump("// MATRIX",$matrixPrint);
        
        if ($x<4 && $shelfMatrix[$y][$x+1] == $shelfMatrix[$y][$x] && !in_array("$y,".($x+1),$group)) {
            //self::trace("// ADJACENCY ON THE RIGHT");
            $group[] = "$y,".($x+1);

            if (count($group) == $stopAt) return $group;
            else $group = self::groupFromTileAt($y,$x+1,$shelfMatrix,$group);
        }
        if ($y<5 && $shelfMatrix[$y+1][$x] == $shelfMatrix[$y][$x] && !in_array(($y+1).",$x",$group)) {
            //self::trace("// ADJACENCY ON THE BOTTOM");
            $group[] = ($y+1).",$x";

            if (count($group) == $stopAt) return $group;
            else $group = self::groupFromTileAt($y+1,$x,$shelfMatrix,$group);
        }
        if ($x>0 && $shelfMatrix[$y][$x-1] == $shelfMatrix[$y][$x] && !in_array("$y,".($x-1),$group)) {
            //self::trace("// ADJACENCY ON THE LEFT");
            $group[] = "$y,".($x-1);

            if (count($group) == $stopAt) return $group;
            else $group = self::groupFromTileAt($y,$x-1,$shelfMatrix,$group);
        }
        if ($y>0 && $shelfMatrix[$y-1][$x] == $shelfMatrix[$y][$x] && !in_array(($y-1).",$x",$group)) {
            //self::trace("// ADJACENCY ON THE TOP");
            $group[] = ($y-1).",$x";

            if (count($group) == $stopAt) return $group;
            else $group = self::groupFromTileAt($y-1,$x,$shelfMatrix,$group);
        }

        //self::trace("// NO (MORE) ADJACENCY");
        return $group;
    }

    function checkCommonGoals($id) {

        //self::trace("// CHECKING COMMON GOALS");

        for ($i=1; $i <= 2; $i++) {

            $n = self::getGameStateValue("common_goal_$i");
            //self::trace("// CHECKING COMMON GOAL $i: $n");

            // check if player already completed goal
            if (self::getUniqueValueFromDb("SELECT player_completed_common_goal_$i FROM player WHERE player_id = $id")) {
                //self::trace("// PLAYER HAS ALREADY COMPLETED COMMON GOAL $i");
            } else {

                $ret = null;

                switch ($n) {
                        case 1:
                            // Two groups each containing 4 tiles of the same type in a 2x2 square. The tiles of one square can be different from those of the other square

                            $matrix = self::getShelfMatrix($id);
                            $groups = [];
                            
                            // parse matrix (except for last column and row)
                            for ($x=0; $x < 4; $x++) { 
                                for ($y=0; $y < 5; $y++) { 
                                    $t = $matrix[$y][$x];

                                    // for each non empty tile
                                    if ($t != 0) {

                                        // check tiles
                                        $tr = $matrix[$y][$x+1]; // at right
                                        $tb = $matrix[$y+1][$x]; // at bottom
                                        $tbr = $matrix[$y+1][$x+1]; // at bottom-right
                                        // (so that they form a square)

                                        // get group (to remove all adjacent tiles);
                                        $g = self::groupFromTileAt($y,$x,$matrix);

                                        // if they are all of equal color
                                        if ($t == $tr && $t == $tb && $t == $tbr) {
                                            $groups[] = ["$y,$x",
                                                        "$y,".($x+1),
                                                        ($y+1).",$x",
                                                        ($y+1).",".($x+1)]; // then a group is formed

                                            // empty tile(s) of the whole group (not only 2x2 square)
                                            foreach ($g as $pos) {
                                                [$posy,$posx] = explode(',',$pos);
                                                $matrix[$posy][$posx] = 0;
                                            }
                                            /* $matrix[$y][$x] = 0;
                                            $matrix[$y][$x+1] = 0;
                                            $matrix[$y+1][$x] = 0;
                                            $matrix[$y+1][$x+1] = 0; */
                                            
                                            // if groups are 2, condition is met, stop searching
                                            if (count($groups) == 2) {
                                                $ret = $groups;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }

                            break;

                        case 2:
                            //Two columns each formed by 6 different types of tiles

                            $columns = [];
                            // for each column
                            for ($x=0; $x < 5; $x++) { 
                                // get col
                                $c = self::getShelfColumnArray($id,$x);

                                // if col doesn't contain empty tiles and has all unique tiles, condition is met
                                if (!in_array(0,$c) && count(array_unique($c)) == 6) {
                                    $columns[] = ["0,$x","1,$x","2,$x","3,$x","4,$x","5,$x"]; // store coordinates of all column tiles for return
                                }

                                // if condition is met 2 times, return
                                if (count($columns) == 2) {
                                    $ret = $columns;
                                    break;
                                }
                            }
                            
                            break;
                        case 3:
                            // Four separated groups each formed by four adjacent tiles (not necessarily in the depicted shape) of the same type. The tiles of one group can be different from those of another group.
                            
                            $groups = [];
                            $matrix = self::getShelfMatrix($id);

                            // parse matrix
                            for ($y=0; $y < 6; $y++) { 
                                for ($x=0; $x < 5; $x++) { 
                                    // for every non empty tile
                                    if ($matrix[$y][$x] != 0) {
                                        // get group of 4 from tile
                                        $group = self::groupFromTileAt($y,$x,$matrix,["$y,$x"]);
                                        // empty tile(s) regardless
                                        foreach ($group as $pos) {
                                            [$posy,$posx] = explode(',',$pos);
                                            $matrix[$posy][$posx] = 0;
                                        }

                                        // check condition
                                        if (count($group) >= 4) {
                                            $groups[] = array_slice($group,0,4);
                                            // check condition iteration
                                            if (count($groups) == 4) {
                                                $ret = $groups;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }

                            break;

                        case 4:
                            // Six separated groups each formed by 2 adjacent tiles (not necessarily in the depicted shape) of the same type. The tiles of one group can be different from those of another group.

                            $couples = [];
                            $matrix = self::getShelfMatrix($id);

                            // parse matrix
                            for ($y=0; $y < 6; $y++) { 
                                for ($x=0; $x < 5; $x++) { 
                                    // for every non empty tile
                                    if ($matrix[$y][$x] != 0) {
                                        // get couple from tile
                                        $couple = self::groupFromTileAt($y,$x,$matrix,["$y,$x"]);

                                        // empty tile(s) regardless
                                        foreach ($couple as $pos) {
                                            [$posy,$posx] = explode(',',$pos);
                                            $matrix[$posy][$posx] = 0;
                                        }

                                        // check condition
                                        if (count($couple) >= 2) {
                                            $couples[] = array_slice($couple,0,2);

                                            // check condition iteration
                                            if (count($couples) == 6) {
                                                $ret = $couples;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }

                            break;

                        case 5:
                            // Three columns each formed by 6 tiles of maximum three different types. One column can show the same or a different combination of another column
                            
                            $columns = [];

                            // for each column
                            for ($x=0; $x < 5; $x++) { 
                                // fetch all coulmn element
                                $c = self::getShelfColumnArray($id,$x);

                                //self::dump("// COLUMN $x",implode(',',$c));
                                //self::dump("// COLUMN $x UNIQUES",implode(',',array_unique($c)));

                                // if column has no empty slot and has 3 max unique elements, condition is met
                                if (!in_array(0,$c) && count(array_unique($c)) <= 3) {

                                    $columns[] = ["0,$x","1,$x","2,$x","3,$x","4,$x","5,$x"];

                                    //self::trace("// CONDITION MET");
                                    //self::dump("// COLUMNS",$columns);

                                    // check condition iteration
                                    if (count($columns) == 3) {
                                        $ret = $columns;
                                        break;
                                    }
                                }
                            }

                            break;

                        case 6:
                            //Two lines each formed by 5 different types of tiles.
                            // (works similarly to n2)

                            $rows = [];
                            for ($y=0; $y < 6; $y++) { 
                                $r = self::getShelfRowArray($id,$y);

                                if (!in_array(0,$r) && count(array_unique($r)) == 5) {
                                    $rows[] = ["$y,0","$y,1","$y,2","$y,3","$y,4"];
                                }

                                if (count($rows) == 2) {
                                    $ret = $rows;
                                    break;
                                }
                            }
                            
                            break;
                        case 7:
                            // Four lines each formed by 5 tiles of maximum three different types. One line can show the same or a different combination of another line
                            // (works similarly to n5)

                            $rows = [];
                            for ($y=0; $y < 6; $y++) { 
                                $r = self::getShelfRowArray($id,$y);

                                // if row has no empty slot and has 3 max unique elements, condition is met
                                if (!in_array(0,$r) && count(array_unique($r)) <= 3) {
                                    $rows[] = ["$y,0","$y,1","$y,2","$y,3","$y,4"];

                                    // check condition iteration
                                    if (count($rows) == 4) {
                                        $ret = $rows;
                                        break;
                                    }
                                }
                            }

                            break;
                        case 8:
                            //Four tiles of the same type in the four corners of the bookshelf

                            $matrix = self::getShelfMatrix($id);

                            $tl = $matrix[0][0]; // top-left corner
                            $tr = $matrix[0][4]; // top-right corner
                            $bl = $matrix[5][0]; // bottom-left corner
                            $br = $matrix[5][4]; // bottom-right corner

                            // if slot are not empty and tiles are equal condition is met
                            if ($tl!=0 && $tl==$tr && $tr==$bl && $bl==$br) {
                                $ret = ['0,0','0,4','5,0','5,4'];
                            }

                            break;
                        case 9:
                            // Eight tiles of the same type. There’s no restriction about the position of these tiles

                            $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];
                            // search all colors
                            foreach ($colorPool as $col) {
                                // get tiles amt in shelf of that color
                                $amt = self::getUniqueValueFromDb("SELECT COUNT(*) FROM tile WHERE `location` = $id AND color = '$col'");

                                // if 8 or higher, condition is met
                                if ($amt >=8) {

                                    $tiles = self::getObjectListFromDb("SELECT CONCAT(position_y,',',position_x) as pos FROM tile WHERE `location` = $id AND color = '$col'",true);
                                    
                                    // if > 8 slice arr
                                    if (count($tiles) > 8) $tiles = array_slice($tiles,0,8);

                                    $ret = $tiles;
                                    break;
                                }
                            }
                            
                            break;
                        case 10:
                            // Five tiles of the same type forming an X

                            // (works similarly to n1)

                            $matrix = self::getShelfMatrix($id);
                            
                            // parse matrix (except for last and second to last column and row)
                            for ($x=0; $x < 3; $x++) { 
                                for ($y=0; $y < 4; $y++) { 
                                    $t = $matrix[$y][$x];

                                    // for each non empty tile
                                    if ($t != 0) {

                                        // []    []
                                        //    []
                                        // []    []

                                        // t     tr
                                        //     c
                                        // tbl   tbr

                                        // check tiles
                                        $tr = $matrix[$y][$x+2]; // at right
                                        $tc = $matrix[$y+1][$x+1]; // at center
                                        $tb = $matrix[$y+2][$x]; // at bottom
                                        $tbr = $matrix[$y+2][$x+2]; // at bottom-right
                                        // (so that they form an X)

                                        // if they are all of equal color
                                        if ($t == $tr && $t == $tc && $t == $tb && $t == $tbr) {
                                            $ret =  ["$y,$x",
                                                    "$y,".($x+2),
                                                    ($y+2).",$x",
                                                    ($y+1).",".($x+1),
                                                    ($y+2).",".($x+2)];

                                            // "empty" corresponding tiles
                                            $matrix[$y][$x] = 0;
                                            $matrix[$y][$x+2] = 0;
                                            $matrix[$y+1][$x+1] = 0;
                                            $matrix[$y+2][$x] = 0;
                                            $matrix[$y+2][$x+2] = 0;

                                            break 2;
                                        }
                                    }
                                }
                            }

                            break;
                        case 11:
                            // Five tiles of the same type forming a diagonal

                            /*
                                $diags = [[],[],[],[]]; // initiate array that store tiles for each diagonal
                                for ($x=0; $x < 5; $x++) {
                                    for ($y=0; $y < 6; $y++) {
                                        $t = self::fetchTileAt($y,$x,$id);

                                        if (!is_null($t)) {
                                            // 4 diagonals possible
                                            if ($x==$y) {
                                                $diags[0][] = $t['color'];
                                            }
                                            if ($x+1==$y) {
                                                $diags[1][] = $t['color'];
                                            }
                                            if ($y-$x = 4) {
                                                $diags[2][] = $t['color'];
                                            }
                                            if ($y-$x = 5) {
                                                $diags[3][] = $t['color'];
                                            }
                                        }                   
                                    }
                                }

                                foreach ($diags as $did => $colors) {
                                    if (count($colors) == 5 && count(array_unique($colors)) == 1) return true;
                                }

                                return false;
                            */

                            $m = self::getShelfMatrix($id);

                            // check diag top-left to bottom-right
                            if ($m[0][0] !=0 && $m[0][0] == $m[1][1] && $m[1][1] == $m[2][2] && $m[2][2] == $m[3][3] && $m[3][3] == $m[4][4]) {
                                $ret = ['0,0','1,1','2,2','3,3','4,4'];
                            }
                            // check diag top-left to bottom-right shifted 1 step down
                            else if ($m[1][0] !=0 && $m[1][0] == $m[2][1] && $m[2][1] == $m[3][2] && $m[3][2] == $m[4][3] && $m[4][3] == $m[5][4]) {
                                $ret = ['1,0','2,1','3,2','4,3','5,4'];
                            }
                            // check diag top-right to bottom-left
                            else if ($m[0][4] !=0 && $m[0][4] == $m[1][3] && $m[1][3] == $m[2][2] && $m[2][2] == $m[3][1] && $m[3][1] == $m[4][0]) {
                                $ret = ['0,4','1,3','2,2','3,1','4,0'];
                            }
                            // check diag top-right to bottom-left shifted 1 step down
                            else if ($m[1][4] !=0 && $m[1][4] == $m[2][3] && $m[2][3] == $m[3][2] && $m[3][2] == $m[4][1] && $m[4][1] == $m[5][0]) {
                                $ret = ['1,4','2,3','3,2','4,1','5,0'];
                            }

                            break;
                        case 12:
                            // Five columns of increasing or decreasing height. Starting from the first column on the left or on the right, each next column must be made of exactly one more tile. Tiles can be of any type. 

                            $columnsH = [];

                            // for each column
                            for ($x=0; $x < 5; $x++) { 
                                // fetch all coulmn element
                                $c = self::getShelfColumnArray($id,$x);
                                $c = array_filter($c,function($t) { return $t!=0; });

                                $columnsH[] = count($c); // column height is length of column array
                                $tilesPos[] = self::getObjectListFromDb("SELECT CONCAT(position_y,',',position_x) as pos FROM tile WHERE `location` = $id AND position_x = $x",true);
                            }

                            // check condition
                            $columnsH = implode(',',$columnsH);

                            if ($columnsH == '1,2,3,4,5' || $columnsH == '2,3,4,5,6' || $columnsH == '5,4,3,2,1' || $columnsH == '6,5,4,3,2') {
                                $ret = $tilesPos;
                            }

                            break;
                        
                        default:
                            throw new BgaSystemException("Invalid common goal id");
                            break;
                }

                if (!empty($ret)) {

                    $token = self::getUniqueValueFromDb("SELECT MAX(n) FROM token WHERE `location` = 'common_goal_$n'");

                    self::notifyAllPlayers('completeCommonGoal',clienttranslate('${player_name} completes a common goal and gains ${scoring_token}'),[
                        'player_id' => $id,
                        'player_name' => self::getPlayerNameById($id),
                        'goal_num' => $n,
                        'token_num' => $token,
                        "scoring_token" => [
                            'log' => '${scoring_token_'.$token.'}',
                            'args' => ["scoring_token_$token" => $token." ".clienttranslate("victory points")],
                            'i18n' => ["scoring_token_$token"]
                        ],
                        'highlight_tiles' => $ret
                    ]);
            
                    self::dbQuery("UPDATE player SET player_completed_common_goal_$i = 1 WHERE player_id = $id");
                    self::DbQuery("UPDATE token SET `location` = '$id' WHERE n=$token AND `location` = 'common_goal_$n'"); // remove token from card pile
                    self::DbQuery("UPDATE player SET player_score = player_score+$token WHERE player_id=$id"); // update player score

                    self::incStat($token,'points_common_goals',$id);
                } //else self::trace("// GOAL NOT COMPLETED");

            }
        }        
    }

    function calculatePersonalGoalScore($id) {

        $matrix = self::getShelfMatrix($id);
        $n = self::getUniqueValueFromDb("SELECT player_personal_goal FROM player WHERE player_id = $id");
        $matches = [];

        // check every match manually
        switch ($n) {
            case 1:
    
                if ($matrix[0][0] == 6) $matches[] = '0,0';
                if ($matrix[0][2] == 4) $matches[] = '0,2';
                if ($matrix[1][4] == 1) $matches[] = '1,4';
                if ($matrix[2][3] == 2) $matches[] = '2,3';
                if ($matrix[3][1] == 3) $matches[] = '3,1';
                if ($matrix[5][2] == 5) $matches[] = '5,2';

                break;
            case 2:

                if ($matrix[1][1] == 6) $matches[] = '1,1';
                if ($matrix[2][0] == 1) $matches[] = '2,0';
                if ($matrix[2][2] == 3) $matches[] = '2,2';
                if ($matrix[3][4] == 2) $matches[] = '3,4';
                if ($matrix[4][3] == 5) $matches[] = '4,3';
                if ($matrix[5][4] == 4) $matches[] = '5,4';

                break;
            case 3:

                if ($matrix[1][0] == 4) $matches[] = '1,0';
                if ($matrix[1][3] == 3) $matches[] = '1,3';
                if ($matrix[2][2] == 6) $matches[] = '2,2';
                if ($matrix[3][1] == 1) $matches[] = '3,1';
                if ($matrix[3][4] == 5) $matches[] = '3,4';
                if ($matrix[5][0] == 2) $matches[] = '5,0';
                break;
            case 4:

                if ($matrix[0][4] == 3) $matches[] = '0,4';
                if ($matrix[2][0] == 5) $matches[] = '2,0';
                if ($matrix[2][2] == 4) $matches[] = '2,2';
                if ($matrix[3][3] == 6) $matches[] = '3,3';
                if ($matrix[4][1] == 2) $matches[] = '4,1';
                if ($matrix[4][2] == 1) $matches[] = '4,2';

                break;
            case 5:

                if ($matrix[1][1] == 5) $matches[] = '1,1';
                if ($matrix[3][1] == 4) $matches[] = '3,1';
                if ($matrix[3][2] == 2) $matches[] = '3,2';
                if ($matrix[4][4] == 6) $matches[] = '4,4';
                if ($matrix[5][0] == 3) $matches[] = '5,0';
                if ($matrix[5][3] == 1) $matches[] = '5,3';

                break;
            case 6:

                if ($matrix[0][2] == 5) $matches[] = '0,2';
                if ($matrix[0][4] == 1) $matches[] = '0,4';
                if ($matrix[2][3] == 2) $matches[] = '2,3';
                if ($matrix[4][1] == 3) $matches[] = '4,1';
                if ($matrix[4][3] == 4) $matches[] = '4,3';
                if ($matrix[5][0] == 6) $matches[] = '5,0';

                break;
            case 7:

                if ($matrix[0][0] == 1) $matches[] = '0,0';
                if ($matrix[1][3] == 4) $matches[] = '1,3';
                if ($matrix[2][1] == 6) $matches[] = '2,1';
                if ($matrix[3][0] == 5) $matches[] = '3,0';
                if ($matrix[4][4] == 3) $matches[] = '4,4';
                if ($matrix[5][2] == 2) $matches[] = '5,2';

                break;
            case 8:

                if ($matrix[0][4] == 4) $matches[] = '0,4';
                if ($matrix[1][1] == 1) $matches[] = '1,1';
                if ($matrix[2][2] == 5) $matches[] = '2,2';
                if ($matrix[3][0] == 6) $matches[] = '3,0';
                if ($matrix[4][3] == 2) $matches[] = '4,3';
                if ($matrix[5][3] == 3) $matches[] = '5,3';

                break;
            case 9:

                if ($matrix[0][2] == 3) $matches[] = '0,2';
                if ($matrix[2][2] == 1) $matches[] = '2,2';
                if ($matrix[3][4] == 2) $matches[] = '3,4';
                if ($matrix[4][1] == 5) $matches[] = '4,1';
                if ($matrix[4][4] == 6) $matches[] = '4,4';
                if ($matrix[5][0] == 4) $matches[] = '5,0';

                break;
            case 10:

                if ($matrix[0][4] == 5) $matches[] = '0,4';
                if ($matrix[1][1] == 3) $matches[] = '1,1';
                if ($matrix[2][0] == 2) $matches[] = '2,0';
                if ($matrix[3][3] == 1) $matches[] = '3,3';
                if ($matrix[4][1] == 4) $matches[] = '4,1';
                if ($matrix[5][3] == 6) $matches[] = '5,3';

                break;
            case 11:

                if ($matrix[0][2] == 6) $matches[] = '0,2';
                if ($matrix[1][1] == 2) $matches[] = '1,1';
                if ($matrix[2][0] == 3) $matches[] = '2,0';
                if ($matrix[3][2] == 4) $matches[] = '3,2';
                if ($matrix[4][4] == 1) $matches[] = '4,4';
                if ($matrix[5][3] == 5) $matches[] = '5,3';

                break;
            case 12:
                
                if ($matrix[0][2] == 2) $matches[] = '0,2';
                if ($matrix[1][1] == 6) $matches[] = '1,1';
                if ($matrix[2][2] == 4) $matches[] = '2,2';
                if ($matrix[3][3] == 5) $matches[] = '3,3';
                if ($matrix[4][4] == 3) $matches[] = '4,4';
                if ($matrix[5][0] == 1) $matches[] = '5,0';

                break;
            default:
                throw new BgaSystemException("Invalid personal goal id");
                break;
        }

        $scoreTable = [0,1,2,4,6,9,12];
        $score = $scoreTable[count($matches)];

        // notification flips personal goal card and highlights matches
        self::notifyAllPlayers('scorePersonalGoal','',[
            'player_id' => $id,
            'player_name' => self::getPlayerNameById($id),
            'personal_goal_num' => $n,
            'score' => $score,
            'matches' => $matches
        ]);

        self::notifyAllPlayers('logScorePersonalGoal',clienttranslate('${player_name} scores ${score} victory points with its Personal Goal'),[
            'player_id' => $id,
            'player_name' => self::getPlayerNameById($id),
            'score' => $score,
        ]);

        // updates player score
        self::dbQuery("UPDATE player SET player_score = player_score + $score WHERE player_id = $id");

        self::incStat($score,'points_personal_goal',$id);
    }

    function getPersonalGoalCoords($n) {

        $ret = [];

        switch ($n) {
            case 1:
    
                $ret['fuchsia'] = '0,0';
                $ret['blue'] = '0,2';
                $ret['green'] = '1,4';
                $ret['white'] = '2,3';
                $ret['orange'] = '3,1';
                $ret['cyan'] = '5,2';

                break;
            case 2:

                $ret['fuchsia'] = '1,1';
                $ret['green'] = '2,0';
                $ret['orange'] = '2,2';
                $ret['white'] = '3,4';
                $ret['cyan'] = '4,3';
                $ret['blue'] = '5,4';

                break;
            case 3:

                $ret['blue'] = '1,0';
                $ret['orange'] = '1,3';
                $ret['fuchsia'] = '2,2';
                $ret['green'] = '3,1';
                $ret['cyan'] = '3,4';
                $ret['white'] = '5,0';
                break;
            case 4:

                $ret['orange'] = '0,4';
                $ret['cyan'] = '2,0';
                $ret['blue'] = '2,2';
                $ret['fuchsia'] = '3,3';
                $ret['white'] = '4,1';
                $ret['green'] = '4,2';

                break;
            case 5:

                $ret['cyan'] = '1,1';
                $ret['blue'] = '3,1';
                $ret['white'] = '3,2';
                $ret['fuchsia'] = '4,4';
                $ret['orange'] = '5,0';
                $ret['green'] = '5,3';

                break;
            case 6:

                $ret['cyan'] = '0,2';
                $ret['green'] = '0,4';
                $ret['white'] = '2,3';
                $ret['orange'] = '4,1';
                $ret['blue'] = '4,3';
                $ret['fuchsia'] = '5,0';

                break;
            case 7:

                $ret['green'] = '0,0';
                $ret['blue'] = '1,3';
                $ret['fuchsia'] = '2,1';
                $ret['cyan'] = '3,0';
                $ret['orange'] = '4,4';
                $ret['white'] = '5,2';

                break;
            case 8:

                $ret['blue'] = '0,4';
                $ret['green'] = '1,1';
                $ret['cyan'] = '2,2';
                $ret['fuchsia'] = '3,0';
                $ret['white'] = '4,3';
                $ret['orange'] = '5,3';

                break;
            case 9:

                $ret['orange'] = '0,2';
                $ret['green'] = '2,2';
                $ret['white'] = '3,4';
                $ret['cyan'] = '4,1';
                $ret['fuchsia'] = '4,4';
                $ret['blue'] = '5,0';

                break;
            case 10:

                $ret['cyan'] = '0,4';
                $ret['orange'] = '1,1';
                $ret['white'] = '2,0';
                $ret['green'] = '3,3';
                $ret['blue'] = '4,1';
                $ret['fuchsia'] = '5,3';

                break;
            case 11:

                $ret['fuchsia'] = '0,2';
                $ret['white'] = '1,1';
                $ret['orange'] = '2,0';
                $ret['blue'] = '3,2';
                $ret['green'] = '4,4';
                $ret['cyan'] = '5,3';

                break;
            case 12:
                
                $ret['white'] = '0,2';
                $ret['fuchsia'] = '1,1';
                $ret['blue'] = '2,2';
                $ret['cyan'] = '3,3';
                $ret['orange'] = '4,4';
                $ret['green'] = '5,0';

                break;
            default:
                throw new BgaSystemException("Invalid personal goal id");
                break;
        }

        return $ret;
    }

    function calculateGroupScoring($id) {

        $totScore = 0;

        // parse shelf matrix
        $matrix = self::getShelfMatrix($id);
        for ($y=0; $y < 6; $y++) { 
            for ($x=0; $x < 5; $x++) { 
                // if tile present
                if ($matrix[$y][$x] != 0) {
                    // calc group from tile
                    $group = self::groupFromTileAt($y,$x,$matrix);

                    // if size > 2 -> players gains victory points
                    $size = count($group);
                    if ($size > 2) {
                        
                        $score; // score function of group size
                        if ($size==3) $score = 2;
                        if ($size==4) $score = 3;
                        if ($size==5) $score = 5;
                        if ($size>=6) $score = 8;

                        $totScore += $score;

                        // send notif to highlight group and increase score counter
                        self::notifyAllPlayers('scoreGroupPoints','',[
                            'player_id' => $id,
                            'score' => $score,
                            'group' => $group
                        ]);

                        // update player score
                        //self::dbQuery("UPDATE player SET player_score = player_score + $score WHERE player_id = $id");

                        //self::incStat($score,'points_tiles_groups',$id);
                    }

                    // for every group (or single tile or couple that doesn't form any group) remove tiles from matrix (to avoid duplicate scoring)
                    foreach ($group as $pos) {
                        [$posy,$posx] = explode(',',$pos);
                        $matrix[$posy][$posx] = 0;
                    }
                }
            }
        }

        self::notifyAllPlayers('logScoreTilesGroups',clienttranslate('${player_name} scores ${score} victory points with its adjacent tiles groups'),[
            'player_id' => $id,
            'player_name' => self::getPlayerNameById($id),
            'score' => $totScore,
        ]);
    }

    function updateGroupScoring($id) {

        $curr_score = self::getUniqueValueFromDb("SELECT player_score FROM player WHERE player_id = $id");

        $tokens_score = self::getUniqueValueFromDb("SELECT SUM(n) FROM token WHERE `location` = '$id'");
        if (is_null($tokens_score)) $tokens_score = 0;

        $groups_score = 0;

        // parse shelf matrix
        $matrix = self::getShelfMatrix($id);
        for ($y=0; $y < 6; $y++) { 
            for ($x=0; $x < 5; $x++) { 
                // if tile present
                if ($matrix[$y][$x] != 0) {
                    // calc group from tile
                    $group = self::groupFromTileAt($y,$x,$matrix);

                    // if size > 2 -> players gains victory points
                    $size = count($group);
                    if ($size > 2) {
                        
                        $score; // score function of group size
                        if ($size==3) $score = 2;
                        if ($size==4) $score = 3;
                        if ($size==5) $score = 5;
                        if ($size>=6) $score = 8;

                        $groups_score += $score;
                    }

                    // for every group (or single tile or couple that doesn't form any group) remove tiles from matrix (to avoid duplicate scoring)
                    foreach ($group as $pos) {
                        [$posy,$posx] = explode(',',$pos);
                        $matrix[$posy][$posx] = 0;
                    }
                }
            }
        }

        $prevGroupScore = $curr_score - $tokens_score;

        if ($groups_score != $prevGroupScore) {

            $scoreInc = $groups_score - $prevGroupScore;

            // update player score
            self::dbQuery("UPDATE player SET player_score = player_score + $scoreInc WHERE player_id = $id");

            self::incStat($scoreInc,'points_tiles_groups',$id);

            self::notifyAllPlayers('updateGroupScoring','',[
                'player_id' => $id,
                'scoreInc' => $scoreInc
            ]);
        }
    }

    #endregion

    /* ---------------------- */
    /* --- PLAYER ACTIONS --- */
    /* ---------------------- */
    #region

    function chooseTiles($tiles) { 
        if ($this->checkAction('chooseTiles')) {

            if (count($tiles) == 0) throw new BgaSystemException(clienttranslate("You need to select at least one item tile"));

            $id = self::getActivePlayerId();
            $args = self::argChoosingTiles();

            $tilesObj = [];
            foreach ($tiles as $t) {
                $tile = array_values(array_filter($args['availableTiles'], function($at) use($t) { return $at['id'] == $t; }));
                if (empty($tile)) throw new BgaSystemException(clienttranslate("One or more selected tiles are invalid"));
                else $tilesObj[$t] = $tile[0];
            }

            if (!self::areTilesAlligned($tilesObj)) throw new BgaSystemException(clienttranslate("Tiles are not on the same line"));

            $tileArgNames = [];
            $tileArgValues = [];
            foreach ($tilesObj as $t => $tile) {

                if (count($tilesObj) > 1) {
                    $adjacent = false;
                    foreach ($tilesObj as $t2 => $tile2) {
                        if ($t != $t2) {
                            if (self::areTilesAdjacent($tile,$tile2)) {
                                $adjacent = true;
                                break;
                            }
                        }
                    }
                    if (!$adjacent) throw new BgaSystemException(clienttranslate("Tiles are not adjacent to each other"));
                }

                $tileArgNames[] = '${tile_'.$t.'}';
                $tileArgValues['tile_'.$t] = self::getTileNameFromColor($tile['color']);

                $x = $tile['x'];
                $y = $tile['y'];

                try {
                    self::dbQuery("INSERT INTO tile_undo (id, position_x, position_y) VALUES ($t,$x,$y)");
                } catch (Exception $e) {}
                
                self::dbQuery("UPDATE tile SET `location` = $id, position_x = NULL, position_y = NULL WHERE id = $t");
            }

            if (empty(self::argFillingShelf()['availableColumns'])) throw new BgaUserException(clienttranslate("You don't have enough space in your Bookshelf for this many item tiles"));

            self::notifyAllPlayers('chooseTiles',clienttranslate('${player_name} takes ${tiles} from the living room'),[
                'player_id' => $id,
                'player_name' => self::getPlayerNameById($id),
                'tilesList' => $tiles,
                'tiles' => [
                    'log' => implode(', ',$tileArgNames),
                    'args' => $tileArgValues,
                    'i18n' => array_keys($tileArgValues)
                ],
            ]);
            
            $this->gamestate->nextState('chooseTiles');
        }
    }

    function undoTileSelection() {
        if ($this->checkAction('undoTileSelection')) {

            $id = self::getActivePlayerId();
            
            try {
                $handTiles = self::getObjectListFromDb("SELECT id FROM tile WHERE `location` = $id AND position_x IS NULL AND position_y IS NULL",true);

                foreach ($handTiles as $t) {

                    ['x'=>$x,'y'=>$y] = self::getObjectFromDb("SELECT position_x x, position_y y FROM tile_undo WHERE id = $t");
                    self::dbQuery("UPDATE tile SET `location` = 'board', position_x = $x, position_y = $y WHERE id = $t");
                    self::dbQuery("DELETE FROM tile_undo WHERE id = $t");

                    $tiles[] = [
                        'id' => $t,
                        'x' => $x,
                        'y' => $y
                    ];
                }

                self::notifyAllPlayers('undoTileSelection',clienttranslate('${player_name} undoes his/her Item tile(s) selection'),[
                    'player_name' => self::getPlayerNameById($id),
                    'player_id' => $id,
                    'tiles' => $tiles
                ]);
                $this->gamestate->nextState('undoTileSelection');
            } catch (Exception $e) { throw new BgaUserException('You cannot undo the Item tile(s) selection at this time'); }
        }
    }

    function insertTiles($column,$tiles) { 
        if ($this->checkAction('insertTiles')) {

            $id = self::getActivePlayerId();
            $args = self::argFillingShelf();
            $handTiles = self::getObjectListFromDb("SELECT id FROM tile WHERE `location` = $id AND position_x IS NULL AND position_y IS NULL",true);

            if (!in_array($column,array_keys($args['availableColumns']))) throw new BgaSystemException("Selected column is not available");
            
            if (count($tiles) != count($handTiles)) throw new BgaSystemException("You need to choose the slotting order for all your new Item tiles");

            foreach ($tiles as $pos => $tid) {
                if (!in_array($tid,$handTiles)) throw new BgaSystemException("One or more tiles don't match the ones previously taken from the board");

                $y = 5 - ($args['availableColumns'][$column]+$pos);
                self::dbQuery("UPDATE tile SET position_x = $column, position_y = $y WHERE id = $tid");
            }

            self::notifyAllPlayers('insertTiles',clienttranslate('${player_name} slots the Item tiles in his/her Bookshelf'),[
                'player_id' => $id,
                'player_name' => self::getPlayerNameById($id),
                'column' => $column,
                'tiles' => $tiles,
                'firstPos' => $args['availableColumns'][$column]
            ]);

            // check completion of common goal and send notif
            self::checkCommonGoals($id);
            self::updateGroupScoring($id);

            $this->gamestate->nextState('insertTiles');
        }
    }

    #endregion
    
    /* ----------------------- */
    /* --- STATE ARGUMENTS --- */
    /* ----------------------- */
    #region

    function argChoosingTiles() {

        $availableTiles = [];
        foreach (self::getTilesIn('board') as $tile) {
            if (self::isTileFree($tile['y'],$tile['x'])) $availableTiles[] = $tile;
        }

        return ['availableTiles' => $availableTiles];
    }

    function argFillingShelf() {

        $id = self::getActivePlayerId();

        $handTilesCol = self::getObjectListFromDb("SELECT color FROM tile WHERE `location` = $id AND position_x is NULL and position_y is NULL",true);
        $amtToFill = count($handTilesCol);

        $availableColumns = [];
        for ($i=0; $i < 5; $i++) { 
            $coulmnItems = self::getUniqueValueFromDb("SELECT count(*) FROM tile WHERE `location` = $id AND position_x = $i");
            if ($coulmnItems + $amtToFill <= 6) $availableColumns[$i] = $coulmnItems;
        }

        $tilesAllEqual = count(array_unique($handTilesCol)) == 1;

        return ['availableColumns' => $availableColumns, 'tilesAllEqual' => $tilesAllEqual];
    }

    function argPlayerFinalScoring() {
        return ['player_name' => '<span style="font-weight:bold;color:#'.self::getPlayerColorById(self::getActivePlayerId()).';">'.self::getPlayerNameById(self::getActivePlayerId()).'</span>'];
    }

    #endregion

    /* --------------------- */
    /* --- STATE ACTIONS --- */
    /* --------------------- */
    #region

    function stNextPlayer() {
        $id = self::getActivePlayerId();

        // check if player has filled its shelf (game ends) (if not on last round already)
        if (!self::getGameStateValue('is_last_round')) {
            if (self::getUniqueValueFromDb("SELECT count(id) FROM tile WHERE `location`='$id'") == 30) {
                self::setGameStateValue('is_last_round',1);

                self::notifyAllPlayers('shelfFilled',clienttranslate('${player_name} fills completly its Bookshelf and gains ${scoring_token_1}, triggering the game end'),[
                    'player_id' => $id,
                    'player_name' => self::getPlayerNameById($id),
                    'scoring_token_1' => "1 ".clienttranslate("victory points"),
                    'i18n' => ["scoring_token_1"]
                ]);

                self::DbQuery("UPDATE token SET `location` = '$id' WHERE n=1 AND `location` = 'board'"); // remove token from card pile
                self::DbQuery("UPDATE player SET player_score = player_score+1 WHERE player_id=$id"); // update player score
            }
        }

        if (self::getGameStateValue('is_last_round')) {
            if (self::getPlayerAfter($id) == self::getGameStateValue('first_player')) {

                $this->activeNextPlayer();
                $this->gamestate->nextState('playerScoring');
            }
        }

        // check if all board tiles are free
        $boardTiles = self::getTilesIn('board');
        $allFree = true;

        foreach ($boardTiles as $tile) {
            if (!self::isTileFree($tile['y'],$tile['x'],true)) {
                $allFree = false;
                break;
            }
        }

        if ($allFree) self::refillBoard();

        self::incStat(1,'turns_number');

        $this->activeNextPlayer();
        self::giveExtraTime(self::getPlayerAfter($id));
        $this->gamestate->nextState('nextTurn');
    }

    function stPlayerFinalScoring() {
        $id = self::getActivePlayerId();

        self::calculatePersonalGoalScore($id);
        self::calculateGroupScoring($id);

        $sql = "SELECT color, COUNT(*) as freq
                FROM tile
                WHERE `location` = '$id'
                GROUP BY color
                ORDER BY freq DESC
                LIMIT 1";
        $favCol = self::getUniqueValueFromDb($sql);
        if (is_null($favCol)) $favCol = 'green';

        $colorPool = ['green', 'white', 'orange', 'blue', 'cyan', 'fuchsia'];

        $favCol = array_search($favCol,$colorPool);

        self::setStat($favCol,'favourite_color',$id);

        // give aux score for tie scenarios (player furtherst from first player wins)
        self::dbQuery("UPDATE player SET player_score_aux = player_no - 1");

        if (self::getPlayerAfter($id) == self::getGameStateValue('first_player')) {
            $this->gamestate->nextState('gameEnd');
        } else {
            $this->activeNextPlayer();
            $this->gamestate->nextState('nextScoring');
        }
    }

    #endregion

    /* ------------------------- */
    /* --- ZOMBIES MANAGMENT --- */
    /* ------------------------- */
    #region

    function zombieTurn($state, $active_player) {

    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                case "fillingShelf":

                    $columns = array_keys(self::argFillingShelf()['availableColumns']);
                    $column = $columns[bga_rand(0,count($columns)-1)];

                    $handTiles = self::getObjectListFromDb("SELECT id FROM tile WHERE `location` = $active_player AND position_x IS NULL AND position_y IS NULL",true);
                    shuffle($handTiles);

                    self::insertTiles($column,$handTiles);
                    break;
                default:
                    $this->gamestate->nextState("zombiePass");
                	break;
            }

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

    #endregion
    
    /* ------------------------ */
    /* --- STATE DB UPGRADE --- */
    /* ------------------------ */
    #region
    
    function upgradeTableDb($from_version) {

        if ($from_version <= 2209151141) {

            $sql = "CREATE TABLE IF NOT EXISTS `tile_undo` (
                `id` TINYINT UNSIGNED NOT NULL,
                `position_x` TINYINT UNSIGNED NOT NULL,
                `position_y` TINYINT UNSIGNED NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            try {
                self::applyDbUpgradeToAllDB( $sql );
            } catch (Exception $e) {
                $sql = str_replace("DBPREFIX_", "", $sql);
                self::applyDbUpgradeToAllDB( $sql );
            }
        }
    }

    #endregion
}
