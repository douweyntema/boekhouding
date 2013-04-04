-- phpMyAdmin SQL Dump
-- version 3.3.7deb6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 27, 2012 at 10:23 PM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-7+squeeze3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `treva-panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomer`
--

CREATE TABLE `adminCustomer` (
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
  `balance` int(11) NOT NULL COMMENT 'in centen',
  `invoiceFrequencyBase` enum('YEAR','MONTH','DAY') NOT NULL DEFAULT 'MONTH',
  `invoiceFrequencyMultiplier` int(11) NOT NULL DEFAULT '1',
  `nextInvoiceDate` int(11) NOT NULL,
  `unpaidDomainPriceBase` int(11) NOT NULL DEFAULT '5000' COMMENT 'in centen',
  `unpaidDomainPriceHistoryPercentage` int(11) NOT NULL DEFAULT '50' COMMENT 'in procenten; percentage van betaalde-domeinen-prijs afgelopen jaar die een klant open mag hebben staan',
  `mijnDomeinResellerContactID` int(11) DEFAULT NULL,
  PRIMARY KEY (`customerID`),
  KEY `fileSystemID` (`fileSystemID`),
  KEY `mailSystemID` (`mailSystemID`),
  KEY `nameSystemID` (`nameSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `adminCustomer`:
--   `fileSystemID`
--       `infrastructureFileSystem` -> `fileSystemID`
--   `mailSystemID`
--       `infrastructureMailSystem` -> `mailSystemID`
--   `nameSystemID`
--       `infrastructureNameSystem` -> `nameSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomerRight`
--

CREATE TABLE `adminCustomerRight` (
  `customerRightID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `right` varchar(255) NOT NULL,
  PRIMARY KEY (`customerRightID`),
  UNIQUE KEY `customerID` (`customerID`,`right`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `adminCustomerRight`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminNews`
--

CREATE TABLE `adminNews` (
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

CREATE TABLE `adminUser` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `shell` varchar(255) NOT NULL DEFAULT '/bin/bash',
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `adminUser`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminUserRight`
--

CREATE TABLE `adminUserRight` (
  `userID` int(11) NOT NULL,
  `customerRightID` int(11) DEFAULT NULL,
  UNIQUE KEY `userID` (`userID`,`customerRightID`),
  KEY `customerRightID` (`customerRightID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `adminUserRight`:
--   `userID`
--       `adminUser` -> `userID`
--   `customerRightID`
--       `adminCustomerRight` -> `customerRightID`
--

-- --------------------------------------------------------

--
-- Table structure for table `billingInvoice`
--

CREATE TABLE `billingInvoice` (
  `invoiceID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `remainingAmount` int(11) NOT NULL COMMENT 'in centen',
  `invoiceNumber` varchar(255) NOT NULL,
  `pdf` mediumblob,
  `tex` text,
  PRIMARY KEY (`invoiceID`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- MIME TYPES FOR TABLE `billingInvoice`:
--   `pdf`
--       `application_octetstream`
--

--
-- RELATIONS FOR TABLE `billingInvoice`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `billingInvoiceLine`
--

CREATE TABLE `billingInvoiceLine` (
  `invoiceLineID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `invoiceID` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `periodStart` int(11) DEFAULT NULL,
  `periodEnd` int(11) DEFAULT NULL,
  `price` int(11) NOT NULL COMMENT 'in centen',
  `discount` int(11) NOT NULL COMMENT 'in centen',
  `domain` tinyint(1) NOT NULL,
  PRIMARY KEY (`invoiceLineID`),
  KEY `customerID` (`customerID`),
  KEY `invoiceID` (`invoiceID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `billingInvoiceLine`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--   `invoiceID`
--       `billingInvoice` -> `invoiceID`
--

-- --------------------------------------------------------

--
-- Table structure for table `billingPayment`
--

CREATE TABLE `billingPayment` (
  `paymentID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'in centen',
  `date` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`paymentID`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `billingPayment`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `billingSubscription`
--

CREATE TABLE `billingSubscription` (
  `subscriptionID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `domainTldID` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `price` int(11) DEFAULT NULL COMMENT 'in centen',
  `discountPercentage` int(11) DEFAULT NULL,
  `discountAmount` int(11) DEFAULT NULL COMMENT 'in centen',
  `frequencyBase` enum('YEAR','MONTH','DAY') NOT NULL,
  `frequencyMultiplier` int(11) NOT NULL,
  `invoiceDelay` int(11) NOT NULL COMMENT 'can be negative for early invoicing',
  `nextPeriodStart` int(11) NOT NULL,
  `endDate` int(11) DEFAULT NULL,
  PRIMARY KEY (`subscriptionID`),
  KEY `domainTldID` (`domainTldID`),
  KEY `customerID` (`customerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `billingSubscription`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--   `domainTldID`
--       `infrastructureDomainTld` -> `domainTldID`
--

-- --------------------------------------------------------

--
-- Table structure for table `dnsDelegatedNameServer`
--

CREATE TABLE `dnsDelegatedNameServer` (
  `nameServerID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `ipv4Address` varchar(255) NOT NULL,
  `ipv6Address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`nameServerID`),
  KEY `domainID` (`domainID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `dnsDelegatedNameServer`:
--   `domainID`
--       `dnsDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `dnsDomain`
--

CREATE TABLE `dnsDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `domainTldID` int(11) DEFAULT NULL,
  `parentDomainID` int(11) DEFAULT NULL,
  `subscriptionID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `addressType` enum('NONE','INHERIT','TREVA-WEB','IP','CNAME','DELEGATION','TREVA-DELEGATION') NOT NULL,
  `cnameTarget` varchar(255) DEFAULT NULL,
  `trevaDelegationNameSystemID` int(11) DEFAULT NULL,
  `subdomainsIncluded` tinyint(1) NOT NULL DEFAULT '1',
  `mailType` enum('NONE','TREVA','CUSTOM') NOT NULL,
  `ttl` int(11) DEFAULT NULL,
  `syncContactInfo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `parentDomainID` (`parentDomainID`,`name`),
  KEY `customerID` (`customerID`),
  KEY `domainTldID` (`domainTldID`),
  KEY `trevaDelegationNameSystemID` (`trevaDelegationNameSystemID`),
  KEY `subscriptionID` (`subscriptionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `dnsDomain`:
--   `subscriptionID`
--       `billingSubscription` -> `subscriptionID`
--   `customerID`
--       `adminCustomer` -> `customerID`
--   `parentDomainID`
--       `dnsDomain` -> `domainID`
--   `domainTldID`
--       `infrastructureDomainTld` -> `domainTldID`
--   `trevaDelegationNameSystemID`
--       `infrastructureNameSystem` -> `nameSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `dnsMailServer`
--

CREATE TABLE `dnsMailServer` (
  `mailServerID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL,
  PRIMARY KEY (`mailServerID`),
  UNIQUE KEY `domainID` (`domainID`,`priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `dnsMailServer`:
--   `domainID`
--       `dnsDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `dnsRecord`
--

CREATE TABLE `dnsRecord` (
  `recordID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`recordID`),
  KEY `domainID` (`domainID`,`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `dnsRecord`:
--   `domainID`
--       `dnsDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpDomain`
--

CREATE TABLE `httpDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `domainTldID` int(11) DEFAULT NULL,
  `parentDomainID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `customConfigText` text,
  PRIMARY KEY (`domainID`),
  UNIQUE KEY `parentDomainID` (`parentDomainID`,`name`),
  KEY `customerID` (`customerID`),
  KEY `domainTldID` (`domainTldID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpDomain`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--   `parentDomainID`
--       `httpDomain` -> `domainID`
--   `domainTldID`
--       `infrastructureDomainTld` -> `domainTldID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpGroup`
--

CREATE TABLE `httpGroup` (
  `groupID` int(11) NOT NULL AUTO_INCREMENT,
  `userDatabaseID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`groupID`),
  UNIQUE KEY `userDatabaseID` (`userDatabaseID`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpGroup`:
--   `userDatabaseID`
--       `httpUserDatabase` -> `userDatabaseID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpGroupUser`
--

CREATE TABLE `httpGroupUser` (
  `userID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`groupID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpGroupUser`:
--   `userID`
--       `httpUser` -> `userID`
--   `groupID`
--       `httpGroup` -> `groupID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpPath`
--

CREATE TABLE `httpPath` (
  `pathID` int(11) NOT NULL AUTO_INCREMENT,
  `parentPathID` int(11) DEFAULT NULL,
  `domainID` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` enum('NONE','HOSTED','SVN','REDIRECT','MIRROR','AUTH') NOT NULL,
  `hostedUserID` int(11) DEFAULT NULL,
  `hostedPath` varchar(255) DEFAULT NULL,
  `hostedIndexes` tinyint(1) DEFAULT NULL,
  `hostedExecCGI` tinyint(1) DEFAULT NULL,
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

--
-- RELATIONS FOR TABLE `httpPath`:
--   `domainID`
--       `httpDomain` -> `domainID`
--   `parentPathID`
--       `httpPath` -> `pathID`
--   `mirrorTargetPathID`
--       `httpPath` -> `pathID`
--   `hostedUserID`
--       `adminUser` -> `userID`
--   `userDatabaseID`
--       `httpUserDatabase` -> `userDatabaseID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpPathGroup`
--

CREATE TABLE `httpPathGroup` (
  `pathID` int(11) NOT NULL,
  `groupID` int(11) NOT NULL,
  `rights` varchar(255) NOT NULL,
  PRIMARY KEY (`pathID`,`groupID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpPathGroup`:
--   `groupID`
--       `httpGroup` -> `groupID`
--   `pathID`
--       `httpPath` -> `pathID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpPathUser`
--

CREATE TABLE `httpPathUser` (
  `pathID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `rights` varchar(255) NOT NULL,
  PRIMARY KEY (`pathID`,`userID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpPathUser`:
--   `userID`
--       `httpUser` -> `userID`
--   `pathID`
--       `httpPath` -> `pathID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpUser`
--

CREATE TABLE `httpUser` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `userDatabaseID` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userDatabaseID` (`userDatabaseID`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpUser`:
--   `userDatabaseID`
--       `httpUserDatabase` -> `userDatabaseID`
--

-- --------------------------------------------------------

--
-- Table structure for table `httpUserDatabase`
--

CREATE TABLE `httpUserDatabase` (
  `userDatabaseID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`userDatabaseID`),
  UNIQUE KEY `customerID` (`customerID`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `httpUserDatabase`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureDomainRegistrar`
--

CREATE TABLE `infrastructureDomainRegistrar` (
  `domainRegistrarID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `url` varchar(255) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `parameters` text NOT NULL,
  PRIMARY KEY (`domainRegistrarID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureDomainTld`
--

CREATE TABLE `infrastructureDomainTld` (
  `domainTldID` int(11) NOT NULL AUTO_INCREMENT,
  `domainRegistrarID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`domainTldID`),
  UNIQUE KEY `domainRegistrarID` (`domainRegistrarID`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `infrastructureDomainTld`:
--   `domainRegistrarID`
--       `infrastructureDomainRegistrar` -> `domainRegistrarID`
--

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureFileSystem`
--

CREATE TABLE `infrastructureFileSystem` (
  `fileSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `fileSystemVersion` int(11) NOT NULL DEFAULT '1',
  `httpVersion` int(11) NOT NULL DEFAULT '1',
  `phpMyAdmin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`fileSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureHost`
--

CREATE TABLE `infrastructureHost` (
  `hostID` int(11) NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) NOT NULL,
  `ipv4Address` varchar(255) NOT NULL,
  `ipv6Address` varchar(255) DEFAULT NULL,
  `sshPort` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`hostID`),
  UNIQUE KEY `hostname` (`hostname`,`sshPort`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureMailServer`
--

CREATE TABLE `infrastructureMailServer` (
  `hostID` int(11) NOT NULL,
  `mailSystemID` int(11) NOT NULL,
  `dovecotVersion` int(11) NOT NULL DEFAULT '-1',
  `eximVersion` int(11) NOT NULL DEFAULT '-1',
  `primary` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`mailSystemID`),
  KEY `infrastructureMailServer_ibfk_2` (`mailSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `infrastructureMailServer`:
--   `hostID`
--       `infrastructureHost` -> `hostID`
--   `mailSystemID`
--       `infrastructureMailSystem` -> `mailSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureMailSystem`
--

CREATE TABLE `infrastructureMailSystem` (
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

CREATE TABLE `infrastructureMount` (
  `hostID` int(11) NOT NULL,
  `fileSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  `allowCustomerLogin` tinyint(1) NOT NULL,
  PRIMARY KEY (`hostID`,`fileSystemID`),
  KEY `fileSystemID` (`fileSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `infrastructureMount`:
--   `hostID`
--       `infrastructureHost` -> `hostID`
--   `fileSystemID`
--       `infrastructureFileSystem` -> `fileSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureNameServer`
--

CREATE TABLE `infrastructureNameServer` (
  `hostID` int(11) NOT NULL,
  `nameSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`hostID`,`nameSystemID`),
  KEY `nameSystemID` (`nameSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `infrastructureNameServer`:
--   `hostID`
--       `infrastructureHost` -> `hostID`
--   `nameSystemID`
--       `infrastructureNameSystem` -> `nameSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureNameSystem`
--

CREATE TABLE `infrastructureNameSystem` (
  `nameSystemID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `mijnDomeinResellerNameServerSetID` int(11) DEFAULT NULL,
  `ttl` int(11) NOT NULL,
  PRIMARY KEY (`nameSystemID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `infrastructureWebServer`
--

CREATE TABLE `infrastructureWebServer` (
  `hostID` int(11) NOT NULL,
  `fileSystemID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`hostID`,`fileSystemID`),
  KEY `fileSystemID` (`fileSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `infrastructureWebServer`:
--   `hostID`
--       `infrastructureHost` -> `hostID`
--   `fileSystemID`
--       `infrastructureFileSystem` -> `fileSystemID`
--

-- --------------------------------------------------------

--
-- Table structure for table `mailAddress`
--

CREATE TABLE `mailAddress` (
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

--
-- RELATIONS FOR TABLE `mailAddress`:
--   `domainID`
--       `mailDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `mailAlias`
--

CREATE TABLE `mailAlias` (
  `aliasID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `localpart` varchar(255) NOT NULL,
  `targetAddress` varchar(255) NOT NULL,
  PRIMARY KEY (`aliasID`),
  KEY `domainID` (`domainID`,`localpart`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `mailAlias`:
--   `domainID`
--       `mailDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `mailDomain`
--

CREATE TABLE `mailDomain` (
  `domainID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `domainTldID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`domainID`),
  KEY `customerID` (`customerID`),
  KEY `domainTldID` (`domainTldID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `mailDomain`:
--   `domainTldID`
--       `infrastructureDomainTld` -> `domainTldID`
--   `customerID`
--       `adminCustomer` -> `customerID`
--

-- --------------------------------------------------------

--
-- Table structure for table `mailList`
--

CREATE TABLE `mailList` (
  `listID` int(11) NOT NULL AUTO_INCREMENT,
  `domainID` int(11) NOT NULL,
  `localpart` varchar(255) NOT NULL,
  PRIMARY KEY (`listID`),
  KEY `domainID` (`domainID`,`localpart`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `mailList`:
--   `domainID`
--       `mailDomain` -> `domainID`
--

-- --------------------------------------------------------

--
-- Table structure for table `mailListMember`
--

CREATE TABLE `mailListMember` (
  `memberID` int(11) NOT NULL AUTO_INCREMENT,
  `listID` int(11) NOT NULL,
  `targetAddress` varchar(255) NOT NULL,
  PRIMARY KEY (`memberID`),
  KEY `listID` (`listID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `mailListMember`:
--   `listID`
--       `mailList` -> `listID`
--

-- --------------------------------------------------------

--
-- Table structure for table `ticketReply`
--

CREATE TABLE `ticketReply` (
  `replyID` int(11) NOT NULL AUTO_INCREMENT,
  `threadID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`replyID`),
  KEY `threadID` (`threadID`,`userID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `ticketReply`:
--   `threadID`
--       `ticketThread` -> `threadID`
--

-- --------------------------------------------------------

--
-- Table structure for table `ticketThread`
--

CREATE TABLE `ticketThread` (
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
-- RELATIONS FOR TABLE `ticketThread`:
--   `customerID`
--       `adminCustomer` -> `customerID`
--   `userID`
--       `adminUser` -> `userID`
--

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
  ADD CONSTRAINT `adminUserRight_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `adminUser` (`userID`),
  ADD CONSTRAINT `adminUserRight_ibfk_2` FOREIGN KEY (`customerRightID`) REFERENCES `adminCustomerRight` (`customerRightID`);

--
-- Constraints for table `billingInvoice`
--
ALTER TABLE `billingInvoice`
  ADD CONSTRAINT `billingInvoice_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `billingInvoiceLine`
--
ALTER TABLE `billingInvoiceLine`
  ADD CONSTRAINT `billingInvoiceLine_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingInvoiceLine_ibfk_2` FOREIGN KEY (`invoiceID`) REFERENCES `billingInvoice` (`invoiceID`);

--
-- Constraints for table `billingPayment`
--
ALTER TABLE `billingPayment`
  ADD CONSTRAINT `billingPayment_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `billingSubscription`
--
ALTER TABLE `billingSubscription`
  ADD CONSTRAINT `billingSubscription_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingSubscription_ibfk_2` FOREIGN KEY (`domainTldID`) REFERENCES `infrastructureDomainTld` (`domainTldID`);

--
-- Constraints for table `dnsDelegatedNameServer`
--
ALTER TABLE `dnsDelegatedNameServer`
  ADD CONSTRAINT `dnsDelegatedNameServer_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`);

--
-- Constraints for table `dnsDomain`
--
ALTER TABLE `dnsDomain`
  ADD CONSTRAINT `dnsDomain_ibfk_6` FOREIGN KEY (`subscriptionID`) REFERENCES `billingSubscription` (`subscriptionID`),
  ADD CONSTRAINT `dnsDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `dnsDomain_ibfk_2` FOREIGN KEY (`parentDomainID`) REFERENCES `dnsDomain` (`domainID`),
  ADD CONSTRAINT `dnsDomain_ibfk_3` FOREIGN KEY (`domainTldID`) REFERENCES `infrastructureDomainTld` (`domainTldID`),
  ADD CONSTRAINT `dnsDomain_ibfk_5` FOREIGN KEY (`trevaDelegationNameSystemID`) REFERENCES `infrastructureNameSystem` (`nameSystemID`);

--
-- Constraints for table `dnsMailServer`
--
ALTER TABLE `dnsMailServer`
  ADD CONSTRAINT `dnsMailServer_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`);

--
-- Constraints for table `dnsRecord`
--
ALTER TABLE `dnsRecord`
  ADD CONSTRAINT `dnsRecord_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `dnsDomain` (`domainID`);

--
-- Constraints for table `httpDomain`
--
ALTER TABLE `httpDomain`
  ADD CONSTRAINT `httpDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `httpDomain_ibfk_2` FOREIGN KEY (`parentDomainID`) REFERENCES `httpDomain` (`domainID`),
  ADD CONSTRAINT `httpDomain_ibfk_3` FOREIGN KEY (`domainTldID`) REFERENCES `infrastructureDomainTld` (`domainTldID`);

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
-- Constraints for table `infrastructureDomainTld`
--
ALTER TABLE `infrastructureDomainTld`
  ADD CONSTRAINT `infrastructureDomainTld_ibfk_1` FOREIGN KEY (`domainRegistrarID`) REFERENCES `infrastructureDomainRegistrar` (`domainRegistrarID`);

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
  ADD CONSTRAINT `infrastructureNameServer_ibfk_1` FOREIGN KEY (`hostID`) REFERENCES `infrastructureHost` (`hostID`),
  ADD CONSTRAINT `infrastructureNameServer_ibfk_2` FOREIGN KEY (`nameSystemID`) REFERENCES `infrastructureNameSystem` (`nameSystemID`);

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
  ADD CONSTRAINT `mailDomain_ibfk_2` FOREIGN KEY (`domainTldID`) REFERENCES `infrastructureDomainTld` (`domainTldID`),
  ADD CONSTRAINT `mailDomain_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`);

--
-- Constraints for table `mailList`
--
ALTER TABLE `mailList`
  ADD CONSTRAINT `mailList_ibfk_1` FOREIGN KEY (`domainID`) REFERENCES `mailDomain` (`domainID`);

--
-- Constraints for table `mailListMember`
--
ALTER TABLE `mailListMember`
  ADD CONSTRAINT `mailListMember_ibfk_1` FOREIGN KEY (`listID`) REFERENCES `mailList` (`listID`);

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
