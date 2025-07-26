`username` varchar(50) NOT NULL,
`connection_count` int(11) DEFAULT 0,
`last_connect` datetime DEFAULT NULL,
`last_disconnect` datetime DEFAULT NULL,
`total_time` int(11) DEFAULT 0,
PRIMARY KEY (`username`)
