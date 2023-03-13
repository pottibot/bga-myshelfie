{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MyShelfie implementation : © Pietro Luigi Porcedda pietro.l.porcedda@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div class="player_board_config player-board" id="player_board_config">
    <div id="settings-icon">
        <svg id="cog-icon" xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24">
            <path d="M21.32,9.55l-1.89-.63.89-1.78A1,1,0,0,0,20.13,6L18,3.87a1,1,0,0,0-1.15-.19l-1.78.89-.63-1.89A1,1,0,0,0,13.5,2h-3a1,1,0,0,0-.95.68L8.92,4.57,7.14,3.68A1,1,0,0,0,6,3.87L3.87,6a1,1,0,0,0-.19,1.15l.89,1.78-1.89.63A1,1,0,0,0,2,10.5v3a1,1,0,0,0,.68.95l1.89.63-.89,1.78A1,1,0,0,0,3.87,18L6,20.13a1,1,0,0,0,1.15.19l1.78-.89.63,1.89a1,1,0,0,0,.95.68h3a1,1,0,0,0,.95-.68l.63-1.89,1.78.89A1,1,0,0,0,18,20.13L20.13,18a1,1,0,0,0,.19-1.15l-.89-1.78,1.89-.63A1,1,0,0,0,22,13.5v-3A1,1,0,0,0,21.32,9.55ZM20,12.78l-1.2.4A2,2,0,0,0,17.64,16l.57,1.14-1.1,1.1L16,17.64a2,2,0,0,0-2.79,1.16l-.4,1.2H11.22l-.4-1.2A2,2,0,0,0,8,17.64l-1.14.57-1.1-1.1L6.36,16A2,2,0,0,0,5.2,13.18L4,12.78V11.22l1.2-.4A2,2,0,0,0,6.36,8L5.79,6.89l1.1-1.1L8,6.36A2,2,0,0,0,10.82,5.2l.4-1.2h1.56l.4,1.2A2,2,0,0,0,16,6.36l1.14-.57,1.1,1.1L17.64,8a2,2,0,0,0,1.16,2.79l1.2.4ZM12,8a4,4,0,1,0,4,4A4,4,0,0,0,12,8Zm0,6a2,2,0,1,1,2-2A2,2,0,0,1,12,14Z"/>
        </svg>
        <div id="settings-arrow"></div>
    </div>
    <div id="settings-options">
        <div id="board-scale-pref">
            <div class='pref-lable'>{BOARD_SIZE}:</div>
            <span style="font-weight: 900;">-</span> <input type="range" id="board-scale" min="500" max="1000" value="650" style="margin: 5px;position: relative;top: 3px;"> <span style="font-weight: 900;">+</span>
        </div>
        <div id="colorblind-mod-pref">
            <div class='pref-lable' style="display: inline-block;">{COLORBLIND_MODE}:</div>
            <select id="pref_select_100">
            </select>
        </div>
    </div>
</div>
<div id="game-ui">
    <div id='main_area'>
        <div id="outer-living-room">
            <div id="bag"><div id="bag-shadow"></div></div>
            <div id="common-goal-cards"></div>
        </div>
        <div id="living-room">
            <div id='scoring-board'></div>
            <div id="board">
            </div>
        </div>
    </div>
    <div id="player-shelf">
        <div class="personal-goal-card-window"></div>
    </div>
    <div id="opponents-shelf">
    </div>
</div>

<script type="text/javascript">

// game board (living room)
var jstpl_square = `<div id="square_\${r}_\${c}" class="square"></div>`;

// player board (shelf)
var jstpl_shelf =  `<div id="shelf_\${pid}" class='shelf-area'>
                        <div>
                            <div class="interactable_shelf">
                                <div class="temp_tile_area"></div>
                                <div class="select_columns">
                                    <div class="column_arrow"><div class="slot_start"></div></div>
                                    <div class="column_arrow"><div class="slot_start"></div></div>
                                    <div class="column_arrow"><div class="slot_start"></div></div>
                                    <div class="column_arrow"><div class="slot_start"></div></div>
                                    <div class="column_arrow"><div class="slot_start"></div></div>
                                </div>
                            </div>
                            <div class="shelf-cont">
                                <div class="shelf"><div class="shelf-name" style="--col:#\${col}">\${name}</div></div>
                                <div class="inner-shelf"></div>
                                <div class="shelf-oversurface"></div>
                            </div>
                        </div>
                        <div class="personal_goal_cont">
                            \${pGoal}
                        </div>
                    </div>`;
var jstpl_slot = `<div class="slot slot_\${r}_\${c}"></div>`;

var jstpl_itemTile = `<div id="item-tile_\${id}" class="item-tile \${color}_\${type}"></div>`;
// var jstpl_tileStub = `<div class="item-tile tile-stub" style="visibility:hidden"></div>`;
var jstpl_tilePlaceholder = `<div class="tile_placeholder"></div>`;

var jstpl_card = `<div id="\${type}_goal_\${n}" class="card \${type}_goal"></div>`;
var jstpl_personal_goal_back = `<div id="personal_goal_back_\${pid}" class="card personal_goal personal_goal_back"></div>`;
var jstpl_personal_goal_flipping_cont = `<div class="personal_goal_flip_cont">
                                            <div class="front">\${front}</div>
                                            <div class="back">\${back}</div>
                                        </div>`;

var jstpl_vptoken = `<div class="token token_\${n}"></div>`;

var jstpl_pb_cont = `<div class='player_board_cont'>
                         <div class='tokens_cont'></div>
                     </div>`;
var jstpl_first_player_seat = `<div id="first_player_seat"></div>`;

var jstpl_tooltip_cont =   `<div class="tooltip_cont \${type}">
                                <h3>\${title}</h3>
                                <div class="tooltip_img">\${img}</div>
                                <div class="tooltip_text">\${text}</div>
                            </div>`

</script>  

{OVERALL_GAME_FOOTER}
