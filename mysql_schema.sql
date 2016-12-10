--
-- Table structure for table `buckets`
--
CREATE TABLE `buckets` (
  `bucket_name` varchar(50) NOT NULL,
  `bucket_description` text,
  PRIMARY KEY (`bucket_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `objects`
--
CREATE TABLE `objects` (
  `bucket_name` varchar(50) NOT NULL,
  `object_key` varchar(1000) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  `content_md5` varchar(32) NOT NULL,
  `content_type` varchar(500) DEFAULT NULL,
  `fs_location` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`bucket_name`,`object_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
