<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MyShelfie implementation : © Pietro Luigi Porcedda pietro.l.porcedda@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 */
  
  
class action_myshelfie extends APP_GameAction { 

    // Constructor: please do not modify
   	public function __default() {
  	    if (self::isArg( 'notifwindow')) {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
  	    } else {
            $this->view = "myshelfie_myshelfie";
            self::trace( "Complete reinitialization of board game" );
      	}
  	}

	public function chooseTiles() {

        self::setAjaxMode();

        $tiles = self::getArg("tiles", AT_numberlist, true);
		$tiles = explode(',',$tiles);
        $this->game->chooseTiles($tiles);

        self::ajaxResponse();
    }

    public function undoTileSelection() {

        self::setAjaxMode();
        $this->game->undoTileSelection();

        self::ajaxResponse();
    }

    public function insertTiles() {

        self::setAjaxMode();

        $col = self::getArg("column", AT_int, true);
        $tiles = self::getArg("tiles", AT_numberlist, true);
		$tiles = explode(',',$tiles);

        $this->game->insertTiles($col,$tiles);

        self::ajaxResponse();
    }
}
  

