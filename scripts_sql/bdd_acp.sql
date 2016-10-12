CREATE TABLE IF NOT EXISTS `dictionnary` (
  `entityType` varchar(100) CHARACTER SET utf8 NOT NULL,
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `hasEntityType` varchar(100) CHARACTER SET utf8 DEFAULT NULL, 
  PRIMARY KEY (`entityType`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `entities` (
 `entity` varchar(100) CHARACTER SET utf8 NOT NULL,
 `description` text CHARACTER SET utf8 NOT NULL,
 `active` tinyint(1) NOT NULL DEFAULT '1'
 PRIMARY KEY (`entity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

