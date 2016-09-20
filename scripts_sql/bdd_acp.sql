CREATE TABLE `dictionnary` (
  `entityType` varchar(100) CHARACTER SET utf8 NOT NULL,
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `hasEntityType` varchar(100) CHARACTER SET utf8 DEFAULT NULL, 
  PRIMARY KEY (`entityType`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `entities` (
 `entity` varchar(100) CHARACTER SET utf8 NOT NULL,
 `description` text CHARACTER SET utf8 NOT NULL,
 PRIMARY KEY (`entity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

