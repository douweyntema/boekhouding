-- phpMyAdmin SQL Dump
-- version 3.4.3.1
-- http://www.phpmyadmin.net
--
-- Host: earth.treva.nl
-- Generation Time: Jul 21, 2011 at 04:01 AM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-7+squeeze3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `treva-panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminComponent`
--

CREATE TABLE IF NOT EXISTS `adminComponent` (
  `componentID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `order` int(11) NOT NULL,
  `rootOnly` tinyint(1) NOT NULL,
  PRIMARY KEY (`componentID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomer`
--

CREATE TABLE IF NOT EXISTS `adminCustomer` (
  `customerID` int(11) NOT NULL AUTO_INCREMENT,
  `filesystemID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL,
  `groupname` varchar(255) NOT NULL,
  PRIMARY KEY (`customerID`),
  KEY `filesystemID` (`filesystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomerRight`
--

CREATE TABLE IF NOT EXISTS `adminCustomerRight` (
  `customerID` int(11) NOT NULL,
  `componentID` int(11) NOT NULL,
  PRIMARY KEY (`customerID`,`componentID`),
  KEY `componentID` (`componentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `componentID` int(11) DEFAULT '0',
  UNIQUE KEY `userID` (`userID`,`componentID`),
  KEY `componentID` (`componentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsDomain`
--

CREATE TABLE IF NOT EXISTS `dnsDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `parentDomainID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `hostmaster` varchar(255) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `serial` int(11) NOT NULL DEFAULT '1',
  `refresh` int(11) NOT NULL DEFAULT '28800',
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `parentDomainID` (`parentDomainID`,`name`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsHost`
--

CREATE TABLE IF NOT EXISTS `dnsHost` (
  `hostID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`hostID`),
  KEY `domainID` (`domainID`,`hostname`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsMailServer`
--

CREATE TABLE IF NOT EXISTS `dnsMailServer` (
  `mailServerID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `serverID` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`mailServerID`),
  KEY `domainID` (`domainID`),
  KEY `serverID` (`serverID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsNameServer`
--

CREATE TABLE IF NOT EXISTS `dnsNameServer` (
  `nameServerID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `serverID` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`nameServerID`),
  KEY `domainID` (`domainID`),
  KEY `serverID` (`serverID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dnsServer`
--

CREATE TABLE IF NOT EXISTS `dnsServer` (
  `serverID` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) NOT NULL,
  PRIMARY KEY (`serverID`)
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
-- Table structure for table `infrastructureFilesystem`
--

CREATE TABLE IF NOT EXISTS `infrastructureFilesystem` (
  `filesystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `filesystemVersion` int(11) NOT NULL,
  `httpVersion` int(11) NOT NULL,
  PRIMARY KEY (`filesystemID`)
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
-- Table structure for table `infrastructureMount`
--

CREATE TABLE IF NOT EXISTS `infrastructureMount` (
  `hostID` int(11) NOT NULL,
  `filesystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  `allowCustomerLogin` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`filesystemID`),
  KEY `filesystemID` (`filesystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureWebServer`
--

CREATE TABLE IF NOT EXISTS `infrastructureWebServer` (
  `hostID` int(11) NOT NULL,
  `filesystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`hostID`,`filesystemID`),
  KEY `filesystemID` (`filesystemID`)
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
  `spamThreshold` int(11) NOT NULL DEFAULT '50',
  `spambox` varchar(255) NOT NULL,
  `virusbox` varchar(255) NOT NULL,
  `quota` bigint(20) NOT NULL,
  PRIMARY KEY (`addressID`),
  UNIQUE KEY `domainID` (`domainID`,`localpart`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  KEY `threadID` (`threadID`,`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ticketThread`
--

CREATE TABLE IF NOT EXISTS `ticketThread` (
  `threadID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
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
  ADD CONSTRAINT `adminCustomer_ibfk_1` FOREIGN KEY (`filesystemID`) REFERENCES `infrastructureFilesystem` (`filesystemID`);

--
-- Constraints for table `adminCustomerRight`
--
ALTER TABLE `adminCustomerRight`
  ADD CONSTRAINT `adminCustomerRight_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `adminCustomerRight_ibfk_2` FOREIGN KEY (`componentID`) REFERENCES `adminComponent` (`componentID`);

--
-- Constraints for table `adminUser`
--
ALTER TABLE `adminUser`
  ADD CONSTRAINT `adminUser_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `adminUserRight`
--
ALTER TABLE `adminUserRight`
  ADD CONSTRAINT `adminUserRight_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `adminUser` (`userID`),
  ADD CONSTRAINT `adminUserRight_ibfk_2` FOREIGN KEY (`componentID`) REFERENCES `adminComponent` (`componentID`);

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
-- Constraints for table `dnsMailServer`
--
ALTER TABLE `dnsMailServer`
  ADD CONSTRAINT `dnsMailServer_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`),
  ADD CONSTRAINT `dnsMailServer_ibfk_2` FOREIGN KEY (`serverID`) REFERENCES `dnsServer` (`serverID`);

--
-- Constraints for table `dnsNameServer`
--
ALTER TABLE `dnsNameServer`
  ADD CONSTRAINT `dnsNameServer_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`),
  ADD CONSTRAINT `dnsNameServer_ibfk_2` FOREIGN KEY (`serverID`) REFERENCES `dnsServer` (`serverID`);

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
-- Constraints for table `infrastructureMount`
--
ALTER TABLE `infrastructureMount`
  ADD CONSTRAINT `infrastructureMount_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureMount_ibfk_2` FOREIGN KEY (`filesystemID`) REFERENCES `infrastructureFilesystem` (`filesystemID`);

--
-- Constraints for table `infrastructureWebServer`
--
ALTER TABLE `infrastructureWebServer`
  ADD CONSTRAINT `infrastructureWebServer_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureWebServer_ibfk_2` FOREIGN KEY (`filesystemID`) REFERENCES `infrastructureFilesystem` (`filesystemID`);

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
