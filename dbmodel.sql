
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MyShelfie implementation : © Pietro Luigi Porcedda pietro.l.porcedda@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

ALTER TABLE `player` 
ADD `player_personal_goal` TINYINT UNSIGNED NOT NULL,
ADD `player_completed_common_goal_1` BIT DEFAULT 0,
ADD `player_completed_common_goal_2` BIT DEFAULT 0;

CREATE TABLE IF NOT EXISTS `tile` (
    `id` TINYINT UNSIGNED NOT NULL,
    `location` VARCHAR(8) NOT NULL,
    `position_x` TINYINT UNSIGNED,
    `position_y` TINYINT UNSIGNED,
    `color` VARCHAR(7) NOT NULL,
    `type` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- id: unique tile identifier
-- location: bag (position null), player shelf (playerid), board
-- position: x -> column, y-> row (both null when location is bag)
-- color: tile color (green, white, orange, blue, cyan, fucsia)
-- type: tile variant (drawing on shelf) 1-3 (0-2)

CREATE TABLE IF NOT EXISTS `token` (
    `id` TINYINT UNSIGNED NOT NULL,
    `n` TINYINT UNSIGNED NOT NULL,
    `location` VARCHAR(14) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- id: card number of that type (12 each)
-- type: personal / common
-- player: (if type personal, indicates the player to whom the card is assigned)

CREATE TABLE IF NOT EXISTS `tile_undo` (
    `id` TINYINT UNSIGNED NOT NULL,
    `position_x` TINYINT UNSIGNED NOT NULL,
    `position_y` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;