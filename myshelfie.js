/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MyShelfie implementation : © Pietro Luigi Porcedda pietro.l.porcedda@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.myshelfie", ebg.core.gamegui, {

        // --- SETUP --- //
        // #region

        constructor: function() {
            //console.log('myshelfie constructor');

            this.uiscale = 0.6;
            this.colorValues = {
                green: 'rgb(131 162 44)',
                white: 'rgb(236 228 181)',
                orange: 'rgb(217 161 38)',
                blue: 'rgb(39 95 142)',
                cyan: 'rgb(106 186 175)',
                fuchsia: 'rgb(200 46 122)'
            }
        },
        
        setup: function(gamedatas) {
            //console.log("Starting game setup");

            // SETUP GAME BOARD
            let board = $('board');
            for (let r = 0; r < 9; r++) {
                for (let c = 0; c < 9; c++) {
                    board.insertAdjacentHTML('beforeend',this.format_block('jstpl_square',{
                        r: r,
                        c: c
                    }));
                }
            }

            // fill board with item tiles
            gamedatas.boardTiles.forEach(t => {
                let square = $(`square_${t.y}_${t.x}`);

                square.insertAdjacentHTML('beforeend',this.format_block('jstpl_itemTile',{
                    id: t.id,
                    color: t.color,
                    type: t.type
                }));

                /* boardUnder.insertAdjacentHTML('beforeend',this.format_block('jstpl_itemTile',{
                    id: t.id,
                    color: t.color,
                    type: t.type,
                    left: t.x*(100+5),
                    top: t.y*(100+5),
                    bgcol: t.color,
                })); */
            });

            this.addTooltipHtml('scoring-board',this.format_block('jstpl_tooltip_cont',{
                type:'horizontal-l',
                title:_("Item tiles groups"),
                img:"<div id='scoring-board-img'></div>",
                text:_("Groups of adjacent Item tiles of the same color in your Bookshelf grant victory points (the bigger the group, the better).")
            }));

            // SETUP PLAYER BOARD AND GOAL
            for (var player_id in gamedatas.players) {
                var player = gamedatas.players[player_id];
                         
                let cont;
                let personalGoal;

                $('player_board_'+player_id).insertAdjacentHTML('beforeend',this.format_block('jstpl_pb_cont'));
                // place first player seat
                if (player_id == gamedatas.first_player) {
                    document.querySelector(`#player_board_${player_id} .player_board_cont`).insertAdjacentHTML('beforeend',this.format_block('jstpl_first_player_seat'));
                }

                if (this.getCurrentPlayerId() == player_id) {
                    // place goal card in side player board
                    personalGoal = this.format_block('jstpl_card',{type:'personal', n:gamedatas.personalGoal});
                    // change shelf cont

                    cont = $('player-shelf');
                } else {
                    personalGoal = this.format_block('jstpl_personal_goal_back',{pid:player_id});
                    cont = $('opponents-shelf');
                }

                cont.insertAdjacentHTML('beforeend',this.format_block('jstpl_shelf',{
                    pid: player_id,
                    col: gamedatas.players[player_id].color,
                    name: gamedatas.players[player_id].name,
                    pGoal: personalGoal
                }));

                // set colorblind letters in personal goal
                /* if (this.getCurrentPlayerId() == player_id) {

                    let pg = document.querySelector(`#shelf_${player_id} .personal_goal`);

                    pg.insertAdjacentHTML('beforeend','<div class="mini-shelf-oversurface"></div>');

                    let minishelf = pg.lastElementChild;
                    
                    for (let r = 0; r < 6; r++) {
                        for (let c = 0; c < 5; c++) {
                            minishelf.insertAdjacentHTML('beforeend',this.format_block('jstpl_slot',{
                                r: r,
                                c: c
                            }));
                        }
                    }

                    for (const col in gamedatas.personalGoalCoords) {
                        let y = gamedatas.personalGoalCoords[col].split(',')[0];
                        let x = gamedatas.personalGoalCoords[col].split(',')[1];

                        let slot = document.querySelector(`#shelf_${player_id} .mini-shelf-oversurface .slot_${y}_${x}`);

                        let colorLetters = {
                            green: 'A',
                            white: 'B',
                            orange: 'C',
                            blue: 'D',
                            cyan: 'E',
                            fuchsia: 'F'
                        }
                        slot.insertAdjacentHTML('beforeend',`<div class="colorblind_pg_tile ${col}"></div>`);
                    }
                } */

                // add on  hover highlight effects
                if (this.getCurrentPlayerId() == player_id) {
                    let pg = document.querySelector(`#shelf_${player_id} .personal_goal`);

                    pg.onmouseover = () => this.highlightPersonalGoalPositions();
                    pg.onmouseout = () => this.highlightPersonalGoalPositions(true);
                }

                // add tooltip
                if (this.getCurrentPlayerId() == player_id) {
                    this.addTooltipHtml('personal_goal_'+gamedatas.personalGoal,this.format_block('jstpl_tooltip_cont',{
                        type:'vertical',
                        title:_("Your Personal Goal card"),
                        img:$('personal_goal_'+gamedatas.personalGoal).outerHTML,
                        text:_("The personal goal card grants victory points if you match the highlighted spaces with the corresponding item tiles in your Bookshelf")
                    }));
                }

                this.addTooltipHtmlToClass('personal_goal_back',this.format_block('jstpl_tooltip_cont',{
                    type:'vertical',
                    title:_("Opponent's Personal Goal card"),
                    img:this.format_block('jstpl_personal_goal_back',{pid:''}),
                    text:_("The Personal Goal card of every player is kept secret")
                }));

                let shelf = document.querySelector(`#shelf_${player_id} .inner-shelf`); //inner shelf
                let shelf_oversurface = document.querySelector(`#shelf_${player_id} .shelf-oversurface`); // shelf oversurface

                for (let r = 0; r < 6; r++) {
                    for (let c = 0; c < 5; c++) {
                        shelf.insertAdjacentHTML('beforeend',this.format_block('jstpl_slot',{
                            r: r,
                            c: c
                        }));
                        shelf_oversurface.insertAdjacentHTML('beforeend',this.format_block('jstpl_slot',{
                            r: r,
                            c: c
                        }));
                    }
                }

                gamedatas.shelves[player_id].forEach(t => {

                    if (t.y && t.x) {
                        let slot = document.querySelector(`#shelf_${player_id} .slot_${t.y}_${t.x}`);

                        slot.insertAdjacentHTML('beforeend',this.format_block('jstpl_itemTile',{
                            id: t.id,
                            color: t.color,
                            type: t.type
                        }));

                    } else if (this.getActivePlayerId() == player_id) {

                        let cont = document.querySelector(`#shelf_${player_id} .temp_tile_area`);
                        cont.insertAdjacentHTML('beforeend',this.format_block('jstpl_tilePlaceholder'));

                        let placeholder = cont.lastElementChild;

                        placeholder.insertAdjacentHTML('beforeend',this.format_block('jstpl_itemTile',{
                            id: t.id,
                            color: t.color,
                            type: t.type
                        }));
                    }                    
                });                
            }

            this.addTooltip('first_player_seat',_("This is the first player seat, it indicates which player played first this game. After one player fills it's shelf, the game will end that round, with the player just before the one holding the first player seat (in the turn order)."),'')

            // SETUP COMMON GOALS
            let cg_tooltip = _("The Common Goal card grant a scoring token (from the top of the pile) to the players who achieve the illustrated pattern.")+"<br><br>"+_('This goal:')+' ';

            let cg1 = Object.keys(gamedatas.commonGoals)[0];
            $('common-goal-cards').insertAdjacentHTML('beforeend',this.format_block('jstpl_card',{type:'common',n:cg1}));

            let cg2 = Object.keys(gamedatas.commonGoals)[1];
            $('common-goal-cards').insertAdjacentHTML('beforeend',this.format_block('jstpl_card',{type:'common',n:cg2}));

            // PLACE TOKENS
            gamedatas.tokens.forEach(t => {
 
                if (t.location == 'board') {
                    $('living-room').lastElementChild.insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                    let finishing_token = $('living-room').lastElementChild.lastElementChild;

                    finishing_token.id = 'finishing_token';
                    this.addTooltip('finishing_token',_("The player who completes their Bookshelf first, wins this scoring token"),'');
                }
                else if (t.location.includes('common_goal_')) {
                    let goalnum = t.location.split('_').pop();
                    document.querySelector(`#common_goal_${goalnum}`).insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                }
                else {
                    document.querySelector(`#player_board_${t.location} .tokens_cont`).insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                }
                /* switch (t.location) {
                    case 'board':
                        $('living-room').lastElementChild.insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                        let finishing_token = $('living-room').lastElementChild.lastElementChild;

                        finishing_token.id = 'finishing_token';
                        this.addTooltip('finishing_token',_("The player who completes their Bookshelf first, wins this scoring token"),'');

                        break;
                    
                    case 'common_goal_1':
                    case 'common_goal_2':
                        let goalnum = t.location.split('_').pop();
                        document.querySelector(`.common_goal:nth-child(${goalnum})`).insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                        break;
                
                    default:
                        document.querySelector(`#player_board_${t.location} .tokens_cont`).insertAdjacentHTML('beforeend',this.format_block('jstpl_vptoken',{n:t.n}));
                        break;
                } */
            });

            this.addTooltipHtml('common_goal_'+cg1,this.format_block('jstpl_tooltip_cont',{
                type: 'horizontal',
                title: _("Common Goal card"),
                img: document.querySelector('#common_goal_'+cg1).outerHTML,
                text: cg_tooltip+_(gamedatas.commonGoals[cg1].tooltip)
            }));

            this.addTooltipHtml('common_goal_'+cg2,this.format_block('jstpl_tooltip_cont',{
                type: 'horizontal',
                title: _("Common Goal card"),
                img: document.querySelector('#common_goal_'+cg2).outerHTML,
                text: cg_tooltip+_(gamedatas.commonGoals[cg2].tooltip)
            }));

            // add options to colorblind mode select (couldn't do it on server side as translation didn't work)
            $('pref_select_100').insertAdjacentHTML('beforeend',`<option value="1">${_('On')}</option>`);
            $('pref_select_100').insertAdjacentHTML('beforeend',`<option value="2">${_('Off')}</option>`);

            this.initPreferencesObserver();
            this.setupUIControls();
            this.setupNotifications();

            //console.log("Ending game setup");
        },
        
        // preference change observer (copyed from doc)
        initPreferencesObserver: function () {      
            // Call onPreferenceChange() when any value changes
            dojo.query('.preference_control').on('change', (e) => {
                const match = e.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
                if (!match) {
                    return;
                }
                const pref = match[1];
                const newValue = e.target.value;
                this.prefs[pref].value = newValue;
                this.onPreferenceChange(pref, newValue);
            });

            // Call onPreferenceChange() now
            dojo.forEach(
                dojo.query("#ingame_menu_content .preference_control"),
                function (el) {
                    // Create a new 'change' event
                    var event = new Event('change');

                    // Dispatch it.
                    el.dispatchEvent(event);
                }
            );
        },

        // change preference programmatically (copyed from doc)
        updatePreference: function(prefId, newValue) {
            // Select preference value in control:
            dojo.query('#preference_control_' + prefId + ' > option[value="' + newValue
            // Also select fontrol to fix a BGA framework bug:
                + '"], #preference_fontrol_' + prefId + ' > option[value="' + newValue
                + '"]').forEach((value) => dojo.attr(value, 'selected', true));
            // Generate change event on control to trigger callbacks:
            const newEvt = new CustomEvent('change', {bubbles: false, cancelable: true});
            $('preference_control_' + prefId).dispatchEvent(newEvt);
        },
        
        onPreferenceChange: function (prefId, prefValue) {
            //console.log("Preference changed", prefId, prefValue);
            
            switch (prefId) {
                case '100':
                    if (prefValue == 1) {
                        document.documentElement.classList.add('colorblind-mode');
                    } else document.documentElement.classList.remove('colorblind-mode');
                    
                    break;
            
                default:
                    break;
            }
        },

        onScreenWidthChange: function() {
            this.refitOuterLivingroom();

            $('player_board_config').style.minHeight = $('player_board_config').style.height;
        },

        setupUIControls: function() {

            let settings_panel = $('player_board_config');
            //let panel_h = settings_panel.style.height = settings_panel.offsetHeight+'px';



            let settings_arrow = $('settings-arrow');
            let settings_options = $('settings-options');
            settings_arrow.addEventListener('click', evt => {
                settings_panel.style.height = 'auto';
                if (settings_arrow.classList.contains('open')) {
                    settings_arrow.classList.remove('open');
                    settings_options.style.height = '0px';
                    
                } else {
                    settings_arrow.classList.add('open');
                    settings_options.style.height = 'fit-content';
                    let h = settings_options.offsetHeight;
                    settings_options.style.height = '0px';
                    settings_options.offsetHeight;
                    settings_options.style.height = h+'px';
                }
            })

            // handle zoom control
            let zoom_range = $('board-scale');
            zoom_range.addEventListener('input', evt => {
                let scale = zoom_range.value / 1000;

                $('living-room').style.setProperty('--zoom',scale);

                this.refitOuterLivingroom();

                localStorage.setItem('boardSize', zoom_range.value);
            })

            let boardSize = localStorage.getItem('boardSize');
            if (boardSize) {
                zoom_range.value = boardSize;
                $('living-room').style.setProperty('--zoom',boardSize/1000);
                this.refitOuterLivingroom();
            }

            // handle colorblind mode
            let colorblind_mode = $('pref_select_100');
            colorblind_mode.addEventListener('input', evt => {
                this.updatePreference(100,colorblind_mode.value);
            });
            
            colorblind_mode.value = this.prefs[100].value;
        },

        /* @Override */
        updatePlayerOrdering() {
            this.inherited(arguments);
            dojo.place('player_board_config', 'player_boards', 'first');
        },

        //#endregion

        // --- GAME STATES --- //
        // #region
        
        onEnteringState: function(stateName,args) {
            //console.log('Entering state: '+stateName);
            // console.log('State arguments:',args.args);

            this.gamedatas.gamestate.temp = {};

            // way of calling state handlers dinamically without big f switch
            // Call appropriate method
            let methodName = "onEnteringState_" + stateName;
            if (this[methodName] !== undefined) {             
                //console.log('Calling ' + methodName, args.args);
                this[methodName](args.args);
            }
        },

        onEnteringState_choosingTiles: function(args) {

            //document.querySelector(`#shelf_${this.getActivePlayerId()} .interactable_shelf`).classList.add('open');

            this.gamedatas.gamestate.temp.selected = [];

            args.availableTiles.forEach(tile => {
                let tileEl = $(`item-tile_${tile.id}`);
                tileEl.classList.add('active');

                if (this.isCurrentPlayerActive()) tileEl.classList.add('pointer');

                tileEl.onclick = () => {
                    if (this.isCurrentPlayerActive())
                        this.selectTile(tile);
                    else {
                        this.showMessage(_("It is not your turn"),'error');
                    }
                }
            });

            document.querySelectorAll('.item-tile:not(.active)').forEach(t => {
                t.classList.add('unactive');
            });
        },

        onEnteringState_fillingShelf: function(args) {
            document.querySelector(`#shelf_${this.getActivePlayerId()} .interactable_shelf`).classList.add('open');

            this.gamedatas.gamestate.temp.selected = {};
            
            let pid = this.getActivePlayerId();

            this.gamedatas.gamestate.temp.UIcurrState = document.querySelector(`#shelf_${pid} > div:first-child`).outerHTML;

            //console.log('// ENTERED FILLING SHELF STATE');
            //console.log('// SHELF HTML FOR RESET',this.gamedatas.gamestate.temp.UIcurrState);
            
            Object.keys(args.availableColumns).forEach(n => {

                let columnArr = document.querySelector(`#shelf_${pid} .column_arrow:nth-child(${+n+1})`);
                columnArr.classList.add('visible');
                if (this.isCurrentPlayerActive()) columnArr.classList.add('pointer');
                
                columnArr.onclick = () => {
                    if (this.isCurrentPlayerActive())
                        this.selectColumn(n);
                    else {
                        this.showMessage(_("It is not your turn"),'error');
                    }
                };
                
                columnArr.onmouseover = () => {
                    if (this.isCurrentPlayerActive()) this.previewColumn(n);   
                }

                columnArr.onmouseout = () => {
                    if (this.isCurrentPlayerActive())
                        setTimeout(() => {
                            this.previewColumn(n,true);
                        }, 100);
                }
            });

            document.querySelector(`#shelf_${pid} .select_columns`).classList.add('active');
        },

        onLeavingState: function(stateName){
            //console.log( 'Leaving state: '+stateName );
            
            switch (stateName) {

                case 'choosingTiles':
                    this.resetTilesClassList();
                    break;

                case 'fillingShelf':

                    document.querySelectorAll(`#shelf_${this.getActivePlayerId()} .tile_placeholder`).forEach(ph => ph.remove());
                    document.querySelectorAll(`#shelf_${this.getActivePlayerId()} .column_arrow`).forEach(arr => {arr.classList.remove('visible');});
                    break;
            }               
        },

        onUpdateActionButtons: function(stateName,args) {
            //console.log('onUpdateActionButtons: '+stateName );
                      
            if (this.isCurrentPlayerActive()) {            

                let methodName = "onUpdateActionButtons_" + stateName;
                if (this[methodName] !== undefined) {             
                    //console.log('Calling ' + methodName, args);
                    this[methodName](args);
                }
            }
        },

        onUpdateActionButtons_choosingTiles: function(args) {
            this.addActionButton("confirmTileSelection_button",_("Confirm"),() => {
                if (this.gamedatas.gamestate.temp.selected.length == 0) {
                    this.showMessage("You need to select at least one Item tile",'info');
                    return;
                }
                
                let tiles = [];
                this.gamedatas.gamestate.temp.selected.forEach(t => {
                    tiles.push(t.id);
                });
                tiles = tiles.join(',');

                this.ajaxcallwrapper('chooseTiles',{
                    tiles: tiles
                });
            });

            this.addActionButton("resetTileSelection_button",_("Reset"),() => {

                document.querySelectorAll('.item-tile').forEach(t => {
                    t.classList.remove('active');
                    t.classList.remove('unactive');
                    t.classList.remove('disabled');
                    t.classList.remove('selected');
                });

                this.onEnteringState_choosingTiles(args);
            },null,false,'gray');
        },

        onUpdateActionButtons_fillingShelf: function(args) {
            let pid = this.getActivePlayerId();

            this.addActionButton("confirmFillingShelf_button",_("Confirm"),() => {
                if (!this.gamedatas.gamestate.temp.selected.column && this.gamedatas.gamestate.temp.selected.column != 0) {
                    this.showMessage("You need to select the column where you want to slot in your new Item tiles",'info');
                    return;
                }
                if (this.gamedatas.gamestate.temp.selected.tiles.length != document.querySelectorAll(`#shelf_${pid} .temp_tile_area .item-tile`).length) {
                    this.showMessage("You need to choose the order for slotting in your new Item tiles",'info');
                    return;
                }

                document.querySelectorAll(`#shelf_${pid} .column_arrow`).forEach(c => {
                    c.classList.remove('pointer');
        
                    c.onclick = '';
                    c.onmouseover = '';
                    c.onmouseout = '';
                });

                document.querySelectorAll(`#shelf_${pid} .item-tile`).forEach(tile => {
                    tile.classList.remove('pointer');
                    tile.onclick = '';
                });

                this.ajaxcallwrapper('insertTiles',{
                    column: this.gamedatas.gamestate.temp.selected.column,
                    tiles: this.gamedatas.gamestate.temp.selected.tiles.join(',')
                });
            });

            this.addActionButton("resetFillingShelf_button",_("Reset"),() => {

                document.querySelector(`#shelf_${pid} > div:first-child`).outerHTML = this.gamedatas.gamestate.temp.UIcurrState;

                this.onEnteringState_fillingShelf(args);
            },null,false,'gray');

            this.addActionButton("undoChoosingTiles_button",_("Undo"),() => {
                this.ajaxcallwrapper('undoTileSelection',{});
            },null,false,'red');
        },
        
        // #endregion

        // --- UTILITY METHODS --- //
        // #region

        refitOuterLivingroom: function() {
            let main = $('main_area');
            let livingroom = $('living-room');
            let outerlr = $('outer-living-room');

            if (main.offsetWidth - livingroom.offsetWidth > 230) outerlr.className = 'tight';
            else outerlr.className = 'spread';
        },

        ajaxcallwrapper: function(action, args, handler) {
            if (!args) args = [];
                
            args.lock = true;
            if (this.checkAction(action)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", args, this, (result) => { }, handler);
            }
        },   

        // needed to inject html into log, imported from doc
        format_string_recursive : function(log, args) {
            try {
                if (log && args && !args.processed) {
                    args.processed = true;

                    for (const key in args) {
                        if (key.includes('tile_')) {
                            if (args[key] != '') {
                                let tid = key.split('_')[1];
                                let tile = $('item-tile_'+tid);
                                args[key] = `<div class='${tile.className}' style='--colorval: ${tile.style.getPropertyValue('--colorval')}'></div>`;
                            }
                        }

                        if (key.includes('scoring_token_')) {
                            let tokenNum = key.split('_')[2];
                            args[key] = this.format_block('jstpl_vptoken',{n:tokenNum});
                        }
                    }
                }
            } catch (e) {
                //console.error(log,args,"Exception thrown", e.stack);
            }
            return this.inherited(arguments);
        },

        placeElement: function(el, target) {

            let movingSurface = $('game_play_area');
            let movingSurfacePos = movingSurface.getBoundingClientRect();
            let targetPos = target.getBoundingClientRect();
            
            if (el.parentElement != movingSurface) movingSurface.append(el);

            // position it on its current coordinates, but on oversurface
            el.style.position = 'absolute';
            el.style.left = targetPos.left - movingSurfacePos.left + 'px';
            el.style.top = targetPos.top - movingSurfacePos.top + 'px';

            el.offsetWidth;
        },

        moveElement: function(el,target, duration=0, delay=0, onEnd=()=>{}) {

            this.placeElement(el,el);

            el.style.transition = `all ${duration}ms ${delay}ms ease-in-out`;
            el.offsetWidth;

            if (this.instantaneousMode) {
                duration = 0;
                delay = 0;
            }

            setTimeout(() => {
                el.style.transition = '';

                onEnd();
                
            }, duration);

            this.placeElement(el,target);
        },

        tilesAdjacent: function(t1, t2) {
            if (t1.x == t2.x && Math.abs(t1.y - t2.y) == 1) return true;
            if (t1.y == t2.y && Math.abs(t1.x - t2.x) == 1) return true;
    
            return false;
        },

        highlightShelfTilesGroup: function(id, group, del=true, duration=1000) {

            group.forEach(el => {
                
                let y = parseInt(el.split(',')[0]);
                let x = parseInt(el.split(',')[1]);

                let slot = document.querySelector(`#shelf_${id} .shelf-oversurface .slot_${y}_${x}`);
                slot.classList.add('highlighted');

                if (group.includes(`${y},${x+1}`)) {
                    slot.style.borderRight = 'transparent';
                }
                if (group.includes(`${y+1},${x}`)) {
                    slot.style.borderBottom = 'transparent';
                }
                if (group.includes(`${y},${x-1}`)) {
                    slot.style.borderLeft = 'transparent';
                }
                if (group.includes(`${y-1},${x}`)) {
                    slot.style.borderTop= 'transparent';
                }

                slot.style.borderRadius = `
                    ${(!group.includes(`${y},${x-1}`) && !group.includes(`${y-1},${x}`))? 5 : 0}px
                    ${(!group.includes(`${y-1},${x}`) && !group.includes(`${y},${x+1}`))? 5 : 0}px
                    ${(!group.includes(`${y},${x+1}`) && !group.includes(`${y+1},${x}`))? 5 : 0}px
                    ${(!group.includes(`${y+1},${x}`) && !group.includes(`${y},${x-1}`))? 5 : 0}px`;     
            });

            if (del) {
                if (this.instantaneousMode) {
                    duration = 0;
                }

                setTimeout(() => {
                    document.querySelectorAll(`#shelf_${id} .shelf-oversurface .slot`).forEach(el => {
                        el.classList.remove('highlighted');
                        el.style = '';
                    });
                }, duration);
            }
        },

        resetTilesClassList: function() {
            document.querySelectorAll('.item-tile').forEach(t => {
                t.classList.remove('active');
                t.classList.remove('unactive');
                t.classList.remove('disabled');
                t.classList.remove('selected');
                t.classList.remove('pointer')

                t.onclick = '';
            });
        },

        // #endregion

        // --- PLAYER ACTIONS --- //
        // #region

        // action handler for selecting tile on the board for picking
        // after 1st selection, highlights only adjancent tiles from the remaining
        // after 2nd slection, highlights only tiles in the seame line of previews two selections
        selectTile: function(tile) {

            let selectedTiles = this.gamedatas.gamestate.temp.selected.length;

            // if already three tiles selected gives error message (should not happen)
            if (selectedTiles == 3) {
                this.showMessage(_("You cannot choose more than 3 tiles"),'info');
                return;
            }

            // foreach active tile (tiles available this turn)
            document.querySelectorAll('.item-tile.active').forEach(t => {

                let square = t.parentElement;
                pos = square.id.split('_');
                pos = {
                    x: pos[2],
                    y: pos[1]
                };

                if (selectedTiles == 0) {
                    if (!this.tilesAdjacent(pos,tile))
                        t.classList.add('disabled');
                }

                if (selectedTiles == 1) {
                    let prevSelected = this.gamedatas.gamestate.temp.selected[0];

                    if (this.tilesAdjacent(pos,tile) || this.tilesAdjacent(pos,prevSelected)) {
                        if (prevSelected.y == tile.y && pos.y == tile.y) t.classList.remove('disabled');
                        else if (prevSelected.x == tile.x && pos.x == tile.x) t.classList.remove('disabled');
                        else t.classList.add('disabled');
                    }
                }

                if (selectedTiles == 2) {
                    t.classList.add('disabled');
                }

                if (pos.x == tile.x && pos.y == tile.y) {
                    t.onclick = () => {this.deselectTile(tile)};
                    //t.classList.remove('pointer');
                    t.classList.remove('active');
                    t.classList.remove('disabled');
                    t.classList.add('selected');
                }
            });

            this.gamedatas.gamestate.temp.selected.push(tile);
            //console.log(this.gamedatas.gamestate.temp.selected);
        },

        deselectTile: function(tile) {
            //console.log('deselecting tile',tile);
            let selected = this.gamedatas.gamestate.temp.selected;
            let first =  selected[0];
            let second = selected[1];
            //console.log('first',first);
            //console.log('second',second);
            //console.log('currently selected',JSON.stringify(selected));
            selected.sort((a,b) => {return (a.y - b.y)*2 + (a.x - b.x)});
            //console.log('currently selected sorted',JSON.stringify(selected));
            if (selected[0] != first && selected[0] != second) selected.reverse();
            //console.log('currently selected sorted reversed',JSON.stringify(selected));


            let i = selected.indexOf(tile);

            //console.log('deselected tile index',i);

            switch (i) {
                case -1: return;                    
                    break;

                case 0: selected = selected.slice(1);
                    break;
            
                case 1: selected = selected.slice(0,1)
                    break;

                case 2: selected = selected.slice(0,2);
                    break;
            }

            //console.log('new selected',JSON.stringify(selected));

            $('resetTileSelection_button').click();

            //console.log('reset state ui');
            //console.log('reselecting selected');
            selected.forEach(tile => {
                //console.log('reselecting tile',tile);
                this.selectTile(tile);
            });

        },

        // action handler that select the column for slotting in tiles, then sets handlers for next step in phase
        selectColumn: function(col) {

            if (col == this.gamedatas.gamestate.temp.selected.column) return;

            let pid = this.getActivePlayerId();

            let prevSlotTiles = this.gamedatas.gamestate.temp.selected.tiles;
         
            // clean prev selected
            $('resetFillingShelf_button').click();

            this.gamedatas.gamestate.temp.selected.tiles = prevSlotTiles;

            // activate temp tiles to slot (visual)
            document.querySelector(`#shelf_${pid} .temp_tile_area`).classList.add('active');

            // init array that stores tiles slot order
            this.gamedatas.gamestate.temp.selected.tiles = [];

            // set act handlers for temp tiles
            document.querySelectorAll(`#shelf_${pid} .item-tile`).forEach(tile => {
                tile.classList.add('pointer');
                tile.onclick = () => { this.selectTileSlot(tile); }
            });

            // change page title
            this.gamedatas.gamestate.descriptionmyturn = dojo.string.substitute( _("${you} must choose the order for inserting your tiles"), {
                you: `<span style="font-weight:bold;color:#${this.gamedatas.players[pid].color};">You</span>`
            });
            this.updatePageTitle();

            document.querySelectorAll(`#shelf_${pid} .column_arrow`).forEach(c => {
                //c.classList.remove('pointer');
    
                //c.onclick = '';
                c.onmouseover = '';
                c.onmouseout = '';
            });

            // mem selected col
            this.gamedatas.gamestate.temp.selected.column = +col;

            // highlight selected column
            this.previewColumn(col);

            // highlight clicked col arrow
            document.querySelector(`#shelf_${pid} .column_arrow:nth-child(${+col+1})`).classList.add('selected');

            if (prevSlotTiles) {
                this.gamedatas.gamestate.temp.selected.tiles = [];

                prevSlotTiles.forEach(t => {
                    let tile = $('item-tile_'+t);
                    this.selectTileSlot(tile);
                });
            } else {
                // set order if only one tile
                let tempTiles = document.querySelectorAll(`#shelf_${pid} .temp_tile_area .item-tile`);

                if (this.gamedatas.gamestate.args.tilesAllEqual) {
                    document.querySelectorAll(`#shelf_${pid} .temp_tile_area .item-tile`).forEach(t=> {
                        this.selectTileSlot(t);
                    });
                }
            }

            /* let pid = this.getActivePlayerId();

            if (this.gamedatas.gamestate.temp.selected.column || this.gamedatas.gamestate.temp.selected.column==0) {

                let prevSelected = this.gamedatas.gamestate.temp.selected.column;

                // remove highlight on prev selection
                this.previewColumn(prevSelected,true);

                // remove highlight prev clicked col arrow
                document.querySelector(`#shelf_${pid} .column_arrow:nth-child(${+prevSelected+1})`).classList.remove('selected');

                if (this.gamedatas.gamestate.temp.selected.tiles) {
                    $('resetFillingShelf_button').click();

                }
            } else {

                // activate temp tiles to slot (visual)
                document.querySelector(`#shelf_${pid} .temp_tile_area`).classList.add('active');

                // init array that stores tiles slot order
                this.gamedatas.gamestate.temp.selected.tiles = [];

                // set act handlers for temp tiles
                document.querySelectorAll(`#shelf_${pid} .item-tile`).forEach(tile => {
                    tile.classList.add('pointer');
                    tile.onclick = () => { this.selectTileSlot(tile); }
                });

                // set order if only one tile
                let tempTiles = document.querySelectorAll(`#shelf_${pid} .temp_tile_area .item-tile`);
                if (tempTiles.length == 1) {
                    this.selectTileSlot(tempTiles[0]);
                }

                // change page title
                this.gamedatas.gamestate.descriptionmyturn = dojo.string.substitute( _("${you} must choose the order for inserting your tiles"), {
                    you: `<span style="font-weight:bold;color:#${this.gamedatas.players[pid].color};">You</span>`
                });
                this.updatePageTitle();

                document.querySelectorAll(`#shelf_${pid} .column_arrow`).forEach(c => {
                    //c.classList.remove('pointer');
    
                    //c.onclick = '';
                    c.onmouseover = '';
                    c.onmouseout = '';
                });
            }

            // mem selected col
            this.gamedatas.gamestate.temp.selected.column = +col;

            // highlight selected column
            this.previewColumn(col);

            // highlight clicked col arrow
            document.querySelector(`#shelf_${pid} .column_arrow:nth-child(${+col+1})`).classList.add('selected'); */
        },

        selectOtherColumn: function(col) {
            this.gamedatas.gamestate.temp.selected.column = +col;
        },

        // action handler that choses the order for slotting a tile into the shelf. previews tile in slot after click
        selectTileSlot: function(tile) {

            let tId = tile.id.split('_').pop(); // get tile id

            if (this.gamedatas.gamestate.temp.selected.tiles.includes(tId)) {

                let selCol = this.gamedatas.gamestate.temp.selected.column;
                let selTiles = this.gamedatas.gamestate.temp.selected.tiles;

                selTiles = selTiles.filter(t => {return t!=tId});
                $('resetFillingShelf_button').click();

                this.gamedatas.gamestate.temp.selected.tiles = selTiles;
                this.selectColumn(selCol);

            } else {

                let pid = this.getActivePlayerId();
            
                let slotOrderPos = this.gamedatas.gamestate.temp.selected.tiles.length; // number of currently selected tiles (of which it has been assigned an order)
                
                this.gamedatas.gamestate.temp.selected.tiles.push(tId); // mem it
    
                let selectedCol = this.gamedatas.gamestate.temp.selected.column; // get selected column
    
                tile.insertAdjacentHTML('beforeend',`<div class='slot_order_marker'>${slotOrderPos+1}</div>`); // add order indicator inside tile
    
                let destSlot = document.querySelector(`#shelf_${pid} .slot_${5-(+this.gamedatas.gamestate.args.availableColumns[selectedCol] + slotOrderPos)}_${selectedCol}`); // fetch destination slot
    
                destSlot.innerHTML = `<div class="${tile.className} preview"></div>`; // add tile preview to dest slot
    
                //tile.onclick = ''; // remove handler to avoid doubles
                //tile.classList.remove('pointer');
    
                // if number of tiles selected for slot ordeer is equal to total numer of temp tiles, deactivate container to give visual feedback of task completed
                if (slotOrderPos == document.querySelectorAll(`#shelf_${pid} .temp_tile_area .item-tile`).length - 1) {
                    document.querySelector(`#shelf_${pid} .temp_tile_area`).classList.remove('active');
                }
            }
        },

        // highlight each slot in column of the shelf of the active player (if remove, remove effect)
        previewColumn: function(col,remove=false) {
            let pid = this.getActivePlayerId();

            // foreach slot in the shelf
            document.querySelectorAll(`#shelf_${pid} .inner-shelf .slot`).forEach(s => {
                let slotX = s.className.split(' ').filter(c => c.includes('slot_'))[0].split('_')[2]; // shenanigans to extract slot coordinate in shelf
                // if slot is in col
                if (slotX == col) {
                    // add/remove highlight
                    if (remove) s.classList.remove('highlighted');
                    else s.classList.add('highlighted');
                }
                    
            });
        },

        highlightPersonalGoalPositions: function(remove=false) {

            let id = this.getCurrentPlayerId()
            let positions = this.gamedatas.personalGoalCoords;

            let colorLetters = {
                green: 'A',
                white: 'B',
                orange: 'C',
                blue: 'D',
                cyan: 'E',
                fuchsia: 'F'
            }

            for (const col in positions) {
                let y = positions[col].split(',')[0];
                let x = positions[col].split(',')[1];

                let slot = document.querySelector(`#shelf_${id} .inner-shelf .slot_${y}_${x}`);

                if (remove) {
                    slot.style.backgroundColor = '';
                    if (slot.innerHTML.includes('colorblind_pg_tile')) slot.innerHTML = '';
                }
                else {
                    let bgcol = this.colorValues[col];
                    bgcol = bgcol.slice(0, -1) + "/ 0.7" + bgcol.slice(-1);
                    slot.style.backgroundColor = bgcol;
                    if (slot.innerHTML=='') slot.innerHTML = `<div class="colorblind_pg_tile ${col}"></div>`;
                }
            }
        },

        // #endregion
        
        // --- NOTIFICATIONS --- //
        // #region

        setupNotifications: function() {
            //console.log('notifications subscriptions setup');

            dojo.subscribe('chooseTiles', this, 'notif_chooseTiles');
            this.notifqueue.setSynchronous( 'chooseTiles');

            dojo.subscribe('undoTileSelection', this, 'notif_undoTileSelection');
            this.notifqueue.setSynchronous( 'undoTileSelection');

            dojo.subscribe('insertTiles', this, 'notif_insertTiles');
            this.notifqueue.setSynchronous('insertTiles');

            dojo.subscribe('clearBoard', this, 'notif_clearBoard');
            this.notifqueue.setSynchronous('clearBoard');

            dojo.subscribe('refillBoard', this, 'notif_refillBoard');
            this.notifqueue.setSynchronous('refillBoard');

            dojo.subscribe('completeCommonGoal', this, 'notif_completeCommonGoal');
            this.notifqueue.setSynchronous('completeCommonGoal',2000);

            dojo.subscribe('shelfFilled', this, 'notif_shelfFilled');
            this.notifqueue.setSynchronous('shelfFilled',500);

            dojo.subscribe('scorePersonalGoal', this, 'notif_scorePersonalGoal');
            this.notifqueue.setSynchronous('scorePersonalGoal');

            dojo.subscribe('scoreGroupPoints', this, 'notif_scoreGroupPoints');
            this.notifqueue.setSynchronous('scoreGroupPoints', 1600);

            dojo.subscribe('updateGroupScoring', this, 'notif_updateGroupScoring');
            this.notifqueue.setSynchronous('updateGroupScoring', 1);

            
        },

        // animate picking tile from board into hand (temp tile area)
        notif_chooseTiles: function(notif) {

            // open temp area for picked tiles
            document.querySelector(`#shelf_${this.getActivePlayerId()} .interactable_shelf`).classList.add('open');

            // remove visual enhancement classes from tiles used during picking phase
            this.resetTilesClassList();

            let cont = document.querySelector(`#shelf_${notif.args.player_id} .temp_tile_area`); // fetch destination tile container

            // now for each tile set the animation
            notif.args.tilesList.forEach((t,i) => {
                let tile = $('item-tile_'+t); // fetch tile
                //console.log('// animating picking tile',tile);
                tile.onclick = null;

                // prefill container with stubs to get matching final position for the animation
                cont.insertAdjacentHTML('beforeend',this.format_block('jstpl_tilePlaceholder'));
                let dest = cont.lastElementChild;
                //console.log('// destination',dest);

                // set animation
                setTimeout(() => {
                    this.moveElement(tile,dest,500,0,()=>{
                        //console.log('// animation ended');
                        dest.append(tile); // append tile after stub
                        //console.log('// appended tile in dest');
                        //dest.remove();  // remove stub
                        //console.log('// removed dest');
                        tile.style = ''; // remove positioning properties set by animation
                        //console.log('// reset tile style');

                        if (i == notif.args.tilesList.length-1) {
                            this.notifqueue.setSynchronousDuration(100);
                        }
                    });
                }, (this.instantaneousMode)? 0 : (500+(i*500)));
            });
        },

        notif_undoTileSelection: function(notif) {        
            
            document.querySelector(`#shelf_${notif.args.player_id} > div:first-child`).outerHTML = this.gamedatas.gamestate.temp.UIcurrState;

            // now for each tile set the animation
            notif.args.tiles.forEach((t,i) => {
                let tile = $('item-tile_'+t.id); // fetch tile
                //console.log('// animating picking tile',tile);
                tile.onclick = null;
                tile.innerHTML = '';

                let dest = document.querySelector(`#board #square_${t.y}_${t.x}`);
                //console.log('// destination',dest);

                // set animation
                setTimeout(() => {
                    this.moveElement(tile,dest,500,0,()=>{
                        //console.log('// animation ended');
                        dest.append(tile); // append tile after stub
                        //console.log('// appended tile in dest');
                        //dest.remove();  // remove stub
                        //console.log('// removed dest');
                        tile.style = ''; // remove positioning properties set by animation
                        //console.log('// reset tile style');

                        if (i == notif.args.tiles.length-1) {
                            document.querySelectorAll(`#shelf_${this.getActivePlayerId()} .tile_placeholder`).forEach(ph => ph.remove());
                            document.querySelector(`#shelf_${this.getActivePlayerId()} .interactable_shelf`).classList.remove('open');

                            this.notifqueue.setSynchronousDuration(100);
                        }
                    });
                }, (this.instantaneousMode)? 0 : (500+(i*500)));
            });
        },

        // animate each tile in hand, from temp area to column start to then the proper spot in the shelf
        notif_insertTiles: function(notif) {

            // set notification duration (depends from the number of tiles and animation duration)
            this.notifqueue.setSynchronousDuration((notif.args.tiles.length * 1500/2) + 1500/2 + 500);

            let shelf = document.querySelector(`#shelf_${notif.args.player_id} .shelf`); // fetch shelf

            let arrow = document.querySelector(`#shelf_${notif.args.player_id} .column_arrow:nth-child(${+notif.args.column+1})`); // fetch column arrow
            // unselect arrow
            arrow.classList.remove('selected');

            // remove highlight from column
            document.querySelectorAll(`#shelf_${notif.args.player_id} .slot`).forEach(s => {
                s.classList.remove('highlighted');
            });

            let slotStart = arrow.firstElementChild; // fetch column start position (1st step in animation)
            
            // for each tile to slot inside the shelf
            notif.args.tiles.forEach((t,i) => {
                let tile = $('item-tile_'+t); // fetch tile
                tile.innerHTML = ''; // remove order indicator

                let slot = document.querySelector(`#shelf_${notif.args.player_id} .slot_${5-(+notif.args.firstPos+i)}_${notif.args.column}`); // fetch destination slot

                // set animation
                setTimeout(() => {
                    // first move elment to start of column
                    this.moveElement(tile,slotStart,500,0,()=>{

                        // to cover moving elements and give slotting effect, duplicate shelf and append it on moving surface
                        let coveringShelf = shelf.cloneNode(true);
                        coveringShelf.style.filter = 'unset';
                        this.placeElement(coveringShelf,shelf);
                        
                        // then begin second transition, the actual slotting of the tile
                        this.moveElement(tile,slot,1000,0,()=>{

                            // at animation end, clean slot from preview and append tile
                            slot.innerHTML = '';
                            slot.append(tile);

                            // unset positioning properties given by the animation
                            tile.style.position = '';
                            tile.style.left = '';
                            tile.style.top = '';

                            // remove covering shelf
                            coveringShelf.remove();
                            
                            if (i+1==notif.args.tiles.length) {
                                document.querySelector(`#shelf_${this.getActivePlayerId()} .interactable_shelf`).classList.remove('open');
                            }
                        })
                    })
                }, (this.instantaneousMode)? 0 : (750*i)); // or 1500? (second animation starts after half the animation time of element before)
            });

        },

        notif_clearBoard: function (notif) {

            let bag = $('bag');
            let tiles_remaining = document.querySelectorAll(`#board .item-tile`);
            tiles_remaining.forEach((tile,i) => {
                setTimeout(() => {
                    this.moveElement(tile,bag,700,0,()=>{
                        tile.remove();

                        if (i == tiles_remaining.length-1) this.notifqueue.setSynchronousDuration(100);
                    });
                }, (this.instantaneousMode)? 0 : (i*100));
            });

            if (tiles_remaining.length == 0) this.notifqueue.setSynchronousDuration(100);
        },

        notif_refillBoard: function(notif) {

            this.notifqueue.setSynchronousDuration((Object.keys(notif.args.tiles).length * 100)+700);
            
            let i = 0;

            for (const tpos in notif.args.tiles) {

                let t = notif.args.tiles[tpos];
                t.y = tpos.split(',')[0];
                t.x = tpos.split(',')[1];

                let square = $(`square_${t.y}_${t.x}`);
                let bag = $('bag');

                bag.insertAdjacentHTML('beforeend',this.format_block('jstpl_itemTile',{
                    id: t.id,
                    color: t.color,
                    type: t.type
                }));
                
                let tile = bag.lastElementChild;

                // calc board tile scale
                let s = document.querySelector('#board .square').getBoundingClientRect().width / 250;
                tile.style.setProperty('--scale',s); // apply scale

                setTimeout(() => {
                    this.moveElement(tile,square,700,0,()=>{
                        square.append(tile);
                        tile.style = '';
                    });
                }, (this.instantaneousMode)? 0 : (i*100));

                i++;
            }
        },

        notif_completeCommonGoal: function(notif) {

            let token = document.querySelector(`#common_goal_${notif.args.goal_num} .token_${notif.args.token_num}`);

            let cont = document.querySelector(`#player_board_${notif.args.player_id} .tokens_cont`);
            cont.insertAdjacentHTML('beforeend',this.format_block('jstpl_tilePlaceholder'));
            let dest = cont.lastElementChild;

            this.moveElement(token,dest,500,0,()=>{
                dest.append(token);
                token.style = ''; // remove positioning properties set by animation
            });

            this.scoreCtrl[notif.args.player_id].incValue(notif.args.token_num);

            if (typeof notif.args.highlight_tiles[0] == 'string') {
                this.highlightShelfTilesGroup(notif.args.player_id,notif.args.highlight_tiles,true,2000);
            } else {
                notif.args.highlight_tiles.forEach(g => {
                    this.highlightShelfTilesGroup(notif.args.player_id,g,true,2000);
                });
            }

            // replace card tooltip to update tokens count
            let cg_tooltip = _("The Common Goal card grant a scoring token (from the top of the pile) to the players who achieve the illustrated pattern.")+"<br><br>"+_('This goal: ');
            this.addTooltipHtml('common_goal_'+notif.args.goal_num,this.format_block('jstpl_tooltip_cont',{
                type: 'horizontal',
                title: _("Common Goal card"),
                img: document.querySelector('#common_goal_'+notif.args.goal_num).outerHTML,
                text: cg_tooltip + this.gamedatas.commonGoals[notif.args.goal_num].tooltip
            }));
        },

        notif_shelfFilled: function(notif) {

            let token = document.querySelector(`#board .token_1`);

            let cont = document.querySelector(`#player_board_${notif.args.player_id} .tokens_cont`);
            cont.insertAdjacentHTML('beforeend',this.format_block('jstpl_tilePlaceholder'));
            let dest = cont.lastElementChild;

            this.moveElement(token,dest,500,0,()=>{
                dest.append(token);
                token.style = ''; // remove positioning properties set by animation
            });

            this.scoreCtrl[notif.args.player_id].incValue(1);
        },

        notif_updateGroupScoring: function (notif) {
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.scoreInc);
        },

        notif_scorePersonalGoal: function(notif) {

            let duration = 1000;

            if (this.instantaneousMode) {
                duration = 0;
            }

            this.notifqueue.setSynchronousDuration(1100 + notif.args.matches.length*duration + duration);

            // if player is not current, reveal personal goal by flpping
            if (this.getCurrentPlayerId() != notif.args.player_id) {
                let pg_back = document.querySelector(`#shelf_${notif.args.player_id} .personal_goal_cont .personal_goal`);
                let pg_front = this.format_block('jstpl_card',{type:'personal',n:notif.args.personal_goal_num});

                pg_back.outerHTML = this.format_block('jstpl_personal_goal_flipping_cont',{
                    front: pg_front,
                    back: pg_back.outerHTML
                })
    
                let pg = document.querySelector(`#shelf_${notif.args.player_id} .personal_goal_flip_cont`);

                if (this.instantaneousMode) {
                    pg.style.animationDuration = 0;
                }

                pg.onanimationend = () => {
                    pg.outerHTML = pg_front;
                }
                pg.classList.add('flip');
            } else {
                let pg = document.querySelector(`#shelf_${notif.args.player_id} .personal_goal`);

                pg.style.transition = 'transform 1s ease-in';
                if (this.instantaneousMode) {
                    pg.style.transitionDuration = 0;
                }

                pg.offsetWidth;

                pg.style.transform = 'scale(1.2)';
            };

            // highlight all matches
            setTimeout(() => {
                let pg = document.querySelector(`#shelf_${notif.args.player_id} .personal_goal`);

                pg.insertAdjacentHTML('beforeend','<div class="mini-shelf-oversurface"></div>');

                let minishelf = pg.lastElementChild;
                
                for (let r = 0; r < 6; r++) {
                    for (let c = 0; c < 5; c++) {
                        minishelf.insertAdjacentHTML('beforeend',this.format_block('jstpl_slot',{
                            r: r,
                            c: c
                        }));
                    }
                }

                notif.args.matches.forEach((pos,j) => {
                    setTimeout(() => {
                        //document.querySelectorAll(`#shelf_${notif.args.player_id} .shelf-oversurface .slot`).forEach(s => s.classList.remove('highlighted'));
                        //document.querySelectorAll(`#shelf_${notif.args.player_id} .mini-shelf-oversurface .slot`).forEach(s => s.classList.remove('highlighted'));

                        document.querySelector(`#shelf_${notif.args.player_id} .shelf-oversurface .slot_${pos.split(',')[0]}_${pos.split(',')[1]}`).classList.add('highlighted');
                        document.querySelector(`#shelf_${notif.args.player_id} .mini-shelf-oversurface .slot_${pos.split(',')[0]}_${pos.split(',')[1]}`).classList.add('highlighted');
                    }, j*duration);
                });

                setTimeout(() => {
                    document.querySelectorAll(`#shelf_${notif.args.player_id} .shelf-oversurface .slot`).forEach(s => s.classList.remove('highlighted'));
                    //document.querySelectorAll(`#shelf_${notif.args.player_id} .mini-shelf-oversurface .slot`).forEach(s => s.classList.remove('highlighted'));

                    this.scoreCtrl[notif.args.player_id].incValue(notif.args.score);
 
                    document.querySelector(`#shelf_${notif.args.player_id} .personal_goal`).insertAdjacentHTML('beforeend',`<div class="score-text">+${notif.args.score}</div>`);

                }, notif.args.matches.length*duration);

            },(this.instantaneousMode)? 0 : 1100);
        },

        notif_scoreGroupPoints: function(notif) {

            //this.scoreCtrl[notif.args.player_id].incValue(notif.args.score);
            this.highlightShelfTilesGroup(notif.args.player_id,notif.args.group,false);

            notif.args.group = notif.args.group.sort();
            let mid = notif.args.group[Math.floor(notif.args.group.length/2)];
            let y = mid.split(',')[0];
            let x = mid.split(',')[1];

            let slot = document.querySelector(`#shelf_${notif.args.player_id} .shelf-oversurface .slot_${y}_${x}`);
            slot.innerHTML = `<div class="score-text">${notif.args.score}</div>`;

        }

        // #endregion
   });             
});
