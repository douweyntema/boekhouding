-- phpMyAdmin SQL Dump
-- version 3.3.7deb5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 01, 2011 at 03:33 AM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-7+squeeze3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `treva-panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomer`
--

CREATE TABLE IF NOT EXISTS `adminCustomer` (
  `customerID` int(11) NOT NULL AUTO_INCREMENT,
  `fileSystemID` int(11) NOT NULL,
  `mailSystemID` int(11) NOT NULL,
  `nameSystemID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `companyName` varchar(255) DEFAULT NULL,
  `initials` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `postalCode` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `countryCode` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phoneNumber` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  `diskQuota` int(11) DEFAULT NULL COMMENT 'in MiB',
  `mailQuota` int(11) DEFAULT NULL COMMENT 'in MiB',
  `mijnDomeinResellerContactID` int(11) DEFAULT NULL,
  PRIMARY KEY (`customerID`),
  KEY `fileSystemID` (`fileSystemID`),
  KEY `mailSystemID` (`mailSystemID`),
  KEY `nameSystemID` (`nameSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomerRight`
--

CREATE TABLE IF NOT EXISTS `adminCustomerRight` (
  `customerRightID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `right` varchar(255) NOT NULL,
  PRIMARY KEY (`customerRightID`),
  UNIQUE KEY `customerID` (`customerID`,`right`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminNews`
--

CREATE TABLE IF NOT EXISTS `adminNews` (
  `newsID` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `date` int(11) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`newsID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminUser`
--

CREATE TABLE IF NOT EXISTS `adminUser` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `shell` varchar(255) NOT NULL DEFAULT '/bin/bash',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminUserRight`
--

CREATE TABLE IF NOT EXISTS `adminUserRight` (
  `userID` int(11) NOT NULL,
  `customerRightID` int(11) DEFAULT NULL,
  UNIQUE KEY `userID` (`userID`,`customerRightID`),
  KEY `customerRightID` (`customerRightID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsDomain`
--

CREATE TABLE IF NOT EXISTS `dnsDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `parentDomainID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `serial` int(11) NOT NULL DEFAULT '1',
  `ttl` int(11) NOT NULL DEFAULT '28800',
  `useTrevaNameservers` tinyint(1) NOT NULL DEFAULT '1',
  `useTrevaMailservers` tinyint(1) NOT NULL DEFAULT '1',
  `syncContactInfo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `parentDomainID` (`parentDomainID`,`name`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsHost`
--

CREATE TABLE IF NOT EXISTS `dnsHost` (
  `hostID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`hostID`),
  KEY `domainID` (`domainID`,`hostname`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpDomain`
--

CREATE TABLE IF NOT EXISTS `httpDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `parentDomainID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `customConfigText` text,
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `parentDomainID` (`parentDomainID`,`name`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpGroup`
--

CREATE TABLE IF NOT EXISTS `httpGroup` (
  `groupID` int(11) NOT NULL AUTO_INCREMENT,
  `userDatabaseID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`groupID`),
  UNIQUE KEY `userDatabaseID` (`userDatabaseID`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpGroupUser`
--

CREATE TABLE IF NOT EXISTS `httpGroupUser` (
  `userID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`groupID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpPath`
--

CREATE TABLE IF NOT EXISTS `httpPath` (
  `pathID` int(11) NOT NULL AUTO_INCREMENT,
  `parentPathID` int(11) DEFAULT NULL,
  `domainID` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` enum('NONE','HOSTED','SVN','REDIRECT','MIRROR','AUTH') NOT NULL,
  `hostedUserID` int(11) DEFAULT NULL,
  `hostedPath` varchar(255) DEFAULT NULL,
  `hostedIndexes` tinyint(1) DEFAULT NULL,
  `svnPath` varchar(255) DEFAULT NULL,
  `redirectTarget` varchar(255) DEFAULT NULL,
  `mirrorTargetPathID` int(11) DEFAULT NULL,
  `userDatabaseID` int(11) DEFAULT NULL,
  `userDatabaseRealm` varchar(255) DEFAULT NULL,
  `customLocationConfigText` text,
  `customDirectoryConfigText` text,
  PRIMARY KEY (`pathID`),
  UNIQUE KEY `parentPathID` (`parentPathID`,`name`),
  KEY `userDatabaseID` (`userDatabaseID`),
  KEY `domainID` (`domainID`),
  KEY `mirrorTargetPathID` (`mirrorTargetPathID`),
  KEY `hostedUserID` (`hostedUserID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpPathGroup`
--

CREATE TABLE IF NOT EXISTS `httpPathGroup` (
  `pathID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `rights` varchar(255) NOT NULL,
  PRIMARY KEY (`pathID`,`groupID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpPathUser`
--

CREATE TABLE IF NOT EXISTS `httpPathUser` (
  `pathID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `rights` varchar(255) NOT NULL,
  PRIMARY KEY (`pathID`,`userID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpUser`
--

CREATE TABLE IF NOT EXISTS `httpUser` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `userDatabaseID` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userDatabaseID` (`userDatabaseID`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `httpUserDatabase`
--

CREATE TABLE IF NOT EXISTS `httpUserDatabase` (
  `userDatabaseID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`userDatabaseID`),
  UNIQUE KEY `customerID` (`customerID`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureFileSystem`
--

CREATE TABLE IF NOT EXISTS `infrastructureFileSystem` (
  `fileSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `fileSystemVersion` int(11) NOT NULL DEFAULT '1',
  `httpVersion` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`fileSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureHost`
--

CREATE TABLE IF NOT EXISTS `infrastructureHost` (
  `hostID` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) NOT NULL,
  `sshPort` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`hostID`),
  UNIQUE KEY `hostname` (`hostname`,`sshPort`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureMailServer`
--

CREATE TABLE IF NOT EXISTS `infrastructureMailServer` (
  `hostID` int(11) NOT NULL,
  `mailSystemID` int(11) NOT NULL,
  `dovecotVersion` int(11) NOT NULL DEFAULT '-1',
  `eximVersion` int(11) NOT NULL DEFAULT '-1',
  `primary` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`mailSystemID`),
  KEY `infrastructureMailServer_ibfk_2` (`mailSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureMailSystem`
--

CREATE TABLE IF NOT EXISTS `infrastructureMailSystem` (
  `mailSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`mailSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureMount`
--

CREATE TABLE IF NOT EXISTS `infrastructureMount` (
  `hostID` int(11) NOT NULL,
  `fileSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  `allowCustomerLogin` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`fileSystemID`),
  KEY `fileSystemID` (`fileSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureNameServer`
--

CREATE TABLE IF NOT EXISTS `infrastructureNameServer` (
  `hostID` int(11) NOT NULL,
  `nameSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  `primary` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`nameSystemID`),
  KEY `nameSystemID` (`nameSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureNameSystem`
--

CREATE TABLE IF NOT EXISTS `infrastructureNameSystem` (
  `nameSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `mijnDomeinResellerNameServerSetID` int(11) DEFAULT NULL,
  PRIMARY KEY (`nameSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureWebServer`
--

CREATE TABLE IF NOT EXISTS `infrastructureWebServer` (
  `hostID` int(11) NOT NULL,
  `fileSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`hostID`,`fileSystemID`),
  KEY `fileSystemID` (`fileSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mailAddress`
--

CREATE TABLE IF NOT EXISTS `mailAddress` (
  `addressID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `localpart` varchar(255) NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `canUseSmtp` tinyint(1) NOT NULL DEFAULT '1',
  `canUseImap` tinyint(1) NOT NULL DEFAULT '1',
  `spambox` varchar(255) DEFAULT NULL,
  `virusbox` varchar(255) DEFAULT NULL,
  `quota` bigint(20) DEFAULT NULL,
  `spamQuota` bigint(20) DEFAULT NULL,
  `virusQuota` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`addressID`),
  UNIQUE KEY `domainID` (`domainID`,`localpart`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mailAlias`
--

CREATE TABLE IF NOT EXISTS `mailAlias` (
  `aliasID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `localpart` varchar(255) NOT NULL,
  `targetAddress` varchar(255) NOT NULL,
  PRIMARY KEY (`aliasID`),
  KEY `domainID` (`domainID`,`localpart`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mailDomain`
--

CREATE TABLE IF NOT EXISTS `mailDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`domainID`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ticketReply`
--

CREATE TABLE IF NOT EXISTS `ticketReply` (
  `replyID` int(11) NOT NULL AUTO_INCREMENT,
  `threadID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`replyID`),
  KEY `threadID` (`threadID`,`userID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ticketThread`
--

CREATE TABLE IF NOT EXISTS `ticketThread` (
  `threadID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `status` enum('OPEN','CLOSED') NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`threadID`),
  KEY `customerID` (`customerID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adminCustomer`
--
ALTER TABLE `adminCustomer`
  ADD CONSTRAINT `adminCustomer_ibfk_1` FOREIGN KEY (`fileSystemID`) REFERENCES `infrastructureFileSystem` (`fileSystemID`),
  ADD CONSTRAINT `adminCustomer_ibfk_2` FOREIGN KEY (`mailSystemID`) REFERENCES `infrastructureMailSystem` (`mailSystemID`),
  ADD CONSTRAINT `adminCustomer_ibfk_3` FOREIGN KEY (`nameSystemID`) REFERENCES `infrastructureNameSystem` (`nameSystemID`);

--
-- Constraints for table `adminCustomerRight`
--
ALTER TABLE `adminCustomerRight`
  ADD CONSTRAINT `adminCustomerRight_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `adminUser`
--
ALTER TABLE `adminUser`
  ADD CONSTRAINT `adminUser_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `adminUserRight`
--
ALTER TABLE `adminUserRight`
  ADD CONSTRAINT `adminUserRight_ibfk_2` FOREIGN KEY (`customerRightID`) REFERENCES `adminCustomerRight` (`customerRightID`),
  ADD CONSTRAINT `adminUserRight_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `adminUser` (`userID`);

--
-- Constraints for table `dnsDomain`
--
ALTER TABLE `dnsDomain`
  ADD CONSTRAINT `dnsDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `dnsDomain_ibfk_2` FOREIGN KEY (`parentDomainID`) REFERENCES `dnsDomain` (`domainID`);

--
-- Constraints for table `dnsHost`
--
ALTER TABLE `dnsHost`
  ADD CONSTRAINT `dnsHost_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`);

--
-- Constraints for table `httpDomain`
--
ALTER TABLE `httpDomain`
  ADD CONSTRAINT `httpDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `httpDomain_ibfk_2` FOREIGN KEY (`parentDomainID`) REFERENCES `httpDomain` (`domainID`);

--
-- Constraints for table `httpGroup`
--
ALTER TABLE `httpGroup`
  ADD CONSTRAINT `httpGroup_ibfk_1` FOREIGN KEY (`userDatabaseID`) REFERENCES `httpUserDatabase` (`userDatabaseID`);

--
-- Constraints for table `httpGroupUser`
--
ALTER TABLE `httpGroupUser`
  ADD CONSTRAINT `httpGroupUser_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `httpUser` (`userID`),
  ADD CONSTRAINT `httpGroupUser_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `httpGroup` (`groupID`);

--
-- Constraints for table `httpPath`
--
ALTER TABLE `httpPath`
  ADD CONSTRAINT `httpPath_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `httpDomain` (`domainID`),
  ADD CONSTRAINT `httpPath_ibfk_2` FOREIGN KEY (`parentPathID`) REFERENCES `httpPath` (`pathID`),
  ADD CONSTRAINT `httpPath_ibfk_3` FOREIGN KEY (`mirrorTargetPathID`) REFERENCES `httpPath` (`pathID`),
  ADD CONSTRAINT `httpPath_ibfk_4` FOREIGN KEY (`hostedUserID`) REFERENCES `adminUser` (`userID`),
  ADD CONSTRAINT `httpPath_ibfk_5` FOREIGN KEY (`userDatabaseID`) REFERENCES `httpUserDatabase` (`userDatabaseID`);

--
-- Constraints for table `httpPathGroup`
--
ALTER TABLE `httpPathGroup`
  ADD CONSTRAINT `httpPathGroup_ibfk_1` FOREIGN KEY (`groupID`) REFERENCES `httpGroup` (`groupID`),
  ADD CONSTRAINT `httpPathGroup_ibfk_2` FOREIGN KEY (`pathID`) REFERENCES `httpPath` (`pathID`);

--
-- Constraints for table `httpPathUser`
--
ALTER TABLE `httpPathUser`
  ADD CONSTRAINT `httpPathUser_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `httpUser` (`userID`),
  ADD CONSTRAINT `httpPathUser_ibfk_2` FOREIGN KEY (`pathID`) REFERENCES `httpPath` (`pathID`);

--
-- Constraints for table `httpUser`
--
ALTER TABLE `httpUser`
  ADD CONSTRAINT `httpUser_ibfk_1` FOREIGN KEY (`userDatabaseID`) REFERENCES `httpUserDatabase` (`userDatabaseID`);

--
-- Constraints for table `httpUserDatabase`
--
ALTER TABLE `httpUserDatabase`
  ADD CONSTRAINT `httpUserDatabase_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `infrastructureMailServer`
--
ALTER TABLE `infrastructureMailServer`
  ADD CONSTRAINT `infrastructureMailServer_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureMailServer_ibfk_2` FOREIGN KEY (`mailSystemID`) REFERENCES `infrastructureMailSystem` (`mailSystemID`);

--
-- Constraints for table `infrastructureMount`
--
ALTER TABLE `infrastructureMount`
  ADD CONSTRAINT `infrastructureMount_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureMount_ibfk_2` FOREIGN KEY (`fileSystemID`) REFERENCES `infrastructureFileSystem` (`fileSystemID`);

--
-- Constraints for table `infrastructureNameServer`
--
ALTER TABLE `infrastructureNameServer`
  ADD CONSTRAINT `infrastructureNameServer_ibfk_2` FOREIGN KEY (`nameSystemID`) REFERENCES `infrastructureNameSystem` (`nameSystemID`),
  ADD CONSTRAINT `infrastructureNameServer_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`);

--
-- Constraints for table `infrastructureWebServer`
--
ALTER TABLE `infrastructureWebServer`
  ADD CONSTRAINT `infrastructureWebServer_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureWebServer_ibfk_2` FOREIGN KEY (`fileSystemID`) REFERENCES `infrastructureFileSystem` (`fileSystemID`);

--
-- Constraints for table `mailAddress`
--
ALTER TABLE `mailAddress`
  ADD CONSTRAINT `mailAddress_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `mailDomain` (`domainID`);

--
-- Constraints for table `mailAlias`
--
ALTER TABLE `mailAlias`
  ADD CONSTRAINT `mailAlias_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `mailDomain` (`domainID`);

--
-- Constraints for table `mailDomain`
--
ALTER TABLE `mailDomain`
  ADD CONSTRAINT `mailDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `ticketReply`
--
ALTER TABLE `ticketReply`
  ADD CONSTRAINT `ticketReply_ibfk_1` FOREIGN KEY (`threadID`) REFERENCES `ticketThread` (`threadID`);

--
-- Constraints for table `ticketThread`
--
ALTER TABLE `ticketThread`
  ADD CONSTRAINT `ticketThread_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `ticketThread_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `adminUser` (`userID`);
