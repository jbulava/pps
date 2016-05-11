--
-- mysql_database_schema.sql

CREATE TABLE IF NOT EXISTS `tweets` (
  `object_id` int(10) unsigned NOT NULL,
  `tweet_id` bigint(20) unsigned NOT NULL,
  `tweet_text` varchar(160) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `screen_name` char(20) NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `profile_image_url` varchar(200) DEFAULT NULL,
  `cache_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `raw_tweet` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`tweet_id`),
  FOREIGN KEY (`object_id`) 
    REFERENCES objects(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- Simplified schema based on http://140dev.com/free-twitter-api-source-code-library/twitter-database-server/mysql-database-schema/

CREATE TABLE IF NOT EXISTS `tweet_media` (
  `media_id` int(10) unsigned NOT NULL,
  `tweet_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `content_type` varchar(255),
  `url` varchar(200) NULL,
  PRIMARY KEY (`media_id`),
  FOREIGN KEY (`tweet_id`) 
    REFERENCES tweets(`tweet_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `object` int(10) unsigned DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `rank` int(10) unsigned DEFAULT NULL,
  `type` varchar(10) NOT NULL DEFAULT 'jpg',
  `caption` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `objects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `rank` int(10) unsigned DEFAULT NULL,
  `name1` tinytext,
  `name2` tinytext,
  `address1` text,
  `address2` text,
  `city` tinytext,
  `state` tinytext,
  `zip` tinytext,
  `country` tinytext,
  `phone` tinytext,
  `fax` tinytext,
  `url` tinytext,
  `email` tinytext,
  `begin` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `head` tinytext,
  `deck` blob,
  `body` blob,
  `notes` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

CREATE TABLE `wires` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` int(1) unsigned NOT NULL DEFAULT '1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `fromid` int(10) unsigned DEFAULT NULL,
  `toid` int(10) unsigned DEFAULT NULL,
  `weight` float NOT NULL DEFAULT '1',
  `notes` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;