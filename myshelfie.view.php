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
  
require_once( APP_BASE_PATH."view/common/game.view.php" );
  
class view_myshelfie_myshelfie extends game_view {

    function getGameName() {
        return "myshelfie";
    }    

  	function build_page($viewArgs) {

        // Display a string to be translated in all languages: 
        $this->tpl['BOARD_SIZE'] = self::_("Board size");
        $this->tpl['COLORBLIND_MODE'] = self::_("Colorblind mode");
  	}

    
}
  

