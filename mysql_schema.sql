CREATE TABLE `buckets` (
  `bucket_name` varchar(50) NOT NULL,
  `bucket_description` text,
  PRIMARY KEY (`bucket_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `objects` (
  `bucket_name` varchar(50) NOT NULL,
  `object_key` varchar(1000) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `content_md5` varchar(32) DEFAULT '',
  `content_type` varchar(500) DEFAULT NULL,
  `fs_location` varchar(100) DEFAULT NULL,
  `content_size` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`bucket_name`,`object_key`),
  UNIQUE KEY `fs_location` (`fs_location`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
