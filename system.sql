-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 10, 2014 at 05:14 PM
-- Server version: 5.5.40
-- PHP Version: 5.4.34-0+deb7u1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `boekhouding`
--

-- --------------------------------------------------------

--
-- Table structure for table `accountingAccount`
--

CREATE TABLE IF NOT EXISTS `accountingAccount` (
  `accountID` int(11) NOT NULL AUTO_INCREMENT,
  `parentAccountID` int(11) DEFAULT NULL,
  `currencyID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `isDirectory` tinyint(1) NOT NULL,
  `balance` int(11) NOT NULL,
  PRIMARY KEY (`accountID`),
  KEY `parentAccountID` (`parentAccountID`),
  KEY `currencyID` (`currencyID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingBalanceView`
--

CREATE TABLE IF NOT EXISTS `accountingBalanceView` (
  `balanceViewID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `dateBase` enum('NOW','STARTMONTH','STARTQUARTER','STARTYEAR','ABSOLUTE') NOT NULL,
  `dateOffsetType` enum('SECONDS','DAYS','MONTHS','YEARS') NOT NULL,
  `dateOffsetAmount` int(11) NOT NULL,
  PRIMARY KEY (`balanceViewID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingBalanceViewAccount`
--

CREATE TABLE IF NOT EXISTS `accountingBalanceViewAccount` (
  `balanceViewID` int(11) NOT NULL,
  `accountID` int(11) NOT NULL,
  `visibility` enum('VISIBLE','COLLAPSED','HIDDEN') NOT NULL,
  PRIMARY KEY (`balanceViewID`,`accountID`),
  KEY `accountID` (`accountID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingCar`
--

CREATE TABLE IF NOT EXISTS `accountingCar` (
  `carID` int(11) NOT NULL AUTO_INCREMENT,
  `drivenKmAccountID` int(11) NOT NULL,
  `expencesAccountID` int(11) NOT NULL,
  `defaultBankAccountID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `kmFee` float NOT NULL,
  PRIMARY KEY (`carID`),
  KEY `drivenKmID` (`drivenKmAccountID`),
  KEY `expencesID` (`expencesAccountID`),
  KEY `defaultBankAccountID` (`defaultBankAccountID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingCurrency`
--

CREATE TABLE IF NOT EXISTS `accountingCurrency` (
  `currencyID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`currencyID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingFixedAsset`
--

CREATE TABLE IF NOT EXISTS `accountingFixedAsset` (
  `fixedAssetID` int(11) NOT NULL AUTO_INCREMENT,
  `accountID` int(11) NOT NULL,
  `depreciationAccountID` int(11) NOT NULL,
  `expenseAccountID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `purchaseDate` int(11) NOT NULL,
  `depreciationFrequencyBase` enum('YEAR','MONTH','DAY') NOT NULL,
  `depreciationFrequencyMultiplier` int(11) NOT NULL,
  `nextDepreciationDate` int(11) NOT NULL,
  `totalDepreciations` int(11) NOT NULL,
  `performedDepreciations` int(11) NOT NULL DEFAULT '0',
  `residualValuePercentage` int(11) NOT NULL,
  `automaticDepreciation` tinyint(1) NOT NULL,
  PRIMARY KEY (`fixedAssetID`),
  KEY `accountID` (`accountID`),
  KEY `depreciationAccountID` (`depreciationAccountID`),
  KEY `expenseAccountID` (`expenseAccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingIncomeExpenseView`
--

CREATE TABLE IF NOT EXISTS `accountingIncomeExpenseView` (
  `incomeExpenseViewID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `startDateBase` enum('NOW','STARTMONTH','STARTQUARTER','STARTYEAR','ABSOLUTE') NOT NULL,
  `startDateOffsetType` enum('SECONDS','DAYS','MONTHS','YEARS') NOT NULL,
  `startDateOffsetAmount` int(11) NOT NULL,
  `endDateBase` enum('NOW','STARTMONTH','STARTQUARTER','STARTYEAR','ABSOLUTE') NOT NULL,
  `endDateOffsetType` enum('SECONDS','DAYS','MONTHS','YEARS') NOT NULL,
  `endDateOffsetAmount` int(11) NOT NULL,
  PRIMARY KEY (`incomeExpenseViewID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingIncomeExpenseViewAccount`
--

CREATE TABLE IF NOT EXISTS `accountingIncomeExpenseViewAccount` (
  `incomeExpenseViewID` int(11) NOT NULL,
  `accountID` int(11) NOT NULL,
  `visibility` enum('VISIBLE','COLLAPSED','HIDDEN') NOT NULL,
  PRIMARY KEY (`incomeExpenseViewID`,`accountID`),
  KEY `accountID` (`accountID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingTransaction`
--

CREATE TABLE IF NOT EXISTS `accountingTransaction` (
  `transactionID` int(11) NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`transactionID`),
  KEY `date` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `accountingTransactionLine`
--

CREATE TABLE IF NOT EXISTS `accountingTransactionLine` (
  `transactionLineID` int(11) NOT NULL AUTO_INCREMENT,
  `transactionID` int(11) NOT NULL,
  `accountID` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`transactionLineID`),
  KEY `transactionID` (`transactionID`,`accountID`),
  KEY `accountID` (`accountID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `adminCustomer`
--

CREATE TABLE IF NOT EXISTS `adminCustomer` (
  `customerID` int(11) NOT NULL AUTO_INCREMENT,
  `accountID` int(11) NOT NULL,
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
  `invoiceFrequencyBase` enum('YEAR','MONTH','DAY') NOT NULL DEFAULT 'MONTH',
  `invoiceFrequencyMultiplier` int(11) NOT NULL DEFAULT '1',
  `nextInvoiceDate` int(11) NOT NULL,
  `invoiceStatus` enum('UNSET','DISABLED','PREVIEW','ENABLED') NOT NULL DEFAULT 'UNSET',
  PRIMARY KEY (`customerID`),
  KEY `accountID` (`accountID`)
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
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `billingInvoice`
--

CREATE TABLE IF NOT EXISTS `billingInvoice` (
  `invoiceID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `transactionID` int(11) NOT NULL,
  `invoiceNumber` varchar(255) NOT NULL,
  `pdf` mediumblob,
  `tex` text,
  PRIMARY KEY (`invoiceID`),
  KEY `customerID` (`customerID`),
  KEY `transactionID` (`transactionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `billingInvoiceLine`
--

CREATE TABLE IF NOT EXISTS `billingInvoiceLine` (
  `invoiceLineID` int(11) NOT NULL AUTO_INCREMENT,
  `invoiceID` int(11) NOT NULL,
  `description` text NOT NULL,
  `periodStart` int(11) DEFAULT NULL,
  `periodEnd` int(11) DEFAULT NULL,
  `price` int(11) NOT NULL COMMENT 'ex btw',
  `discount` int(11) NOT NULL COMMENT 'ex btw',
  `tax` int(11) NOT NULL,
  `taxRate` varchar(255) NOT NULL,
  PRIMARY KEY (`invoiceLineID`),
  KEY `invoiceID` (`invoiceID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `billingPayment`
--

CREATE TABLE IF NOT EXISTS `billingPayment` (
  `paymentID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `transactionID` int(11) NOT NULL,
  PRIMARY KEY (`paymentID`),
  KEY `customerID` (`customerID`),
  KEY `transactionID` (`transactionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `billingSubscription`
--

CREATE TABLE IF NOT EXISTS `billingSubscription` (
  `subscriptionID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `revenueAccountID` int(11) NOT NULL,
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
  KEY `customerID` (`customerID`),
  KEY `revenueAccountID` (`revenueAccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `billingSubscriptionLine`
--

CREATE TABLE IF NOT EXISTS `billingSubscriptionLine` (
  `subscriptionLineID` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) NOT NULL,
  `revenueAccountID` int(11) NOT NULL,
  `description` text NOT NULL,
  `periodStart` int(11) DEFAULT NULL,
  `periodEnd` int(11) DEFAULT NULL,
  `price` int(11) NOT NULL COMMENT 'inc btw',
  `discount` int(11) NOT NULL COMMENT 'inc btw',
  PRIMARY KEY (`subscriptionLineID`),
  KEY `customerID` (`customerID`),
  KEY `revenueAccountID` (`revenueAccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `suppliersInvoice`
--

CREATE TABLE IF NOT EXISTS `suppliersInvoice` (
  `invoiceID` int(11) NOT NULL AUTO_INCREMENT,
  `supplierID` int(11) NOT NULL,
  `transactionID` int(11) NOT NULL,
  `invoiceNumber` varchar(255) NOT NULL,
  `pdf` mediumblob,
  PRIMARY KEY (`invoiceID`),
  KEY `supplierID` (`supplierID`),
  KEY `transactionID` (`transactionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `suppliersPayment`
--

CREATE TABLE IF NOT EXISTS `suppliersPayment` (
  `paymentID` int(11) NOT NULL AUTO_INCREMENT,
  `supplierID` int(11) NOT NULL,
  `transactionID` int(11) NOT NULL,
  PRIMARY KEY (`paymentID`),
  KEY `supplierID` (`supplierID`),
  KEY `transactionID` (`transactionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `suppliersSupplier`
--

CREATE TABLE IF NOT EXISTS `suppliersSupplier` (
  `supplierID` int(11) NOT NULL AUTO_INCREMENT,
  `accountID` int(11) NOT NULL,
  `defaultExpenseAccountID` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`supplierID`),
  KEY `accountID` (`accountID`),
  KEY `expenseAccountID` (`defaultExpenseAccountID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accountingAccount`
--
ALTER TABLE `accountingAccount`
  ADD CONSTRAINT `accountingAccount_ibfk_1` FOREIGN KEY (`parentAccountID`) REFERENCES `accountingAccount` (`accountID`),
  ADD CONSTRAINT `accountingAccount_ibfk_2` FOREIGN KEY (`currencyID`) REFERENCES `accountingCurrency` (`currencyID`);

--
-- Constraints for table `accountingBalanceViewAccount`
--
ALTER TABLE `accountingBalanceViewAccount`
  ADD CONSTRAINT `accountingBalanceViewAccount_ibfk_2` FOREIGN KEY (`balanceViewID`) REFERENCES `accountingBalanceView` (`balanceViewID`),
  ADD CONSTRAINT `accountingBalanceViewAccount_ibfk_3` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `accountingFixedAsset`
--
ALTER TABLE `accountingFixedAsset`
  ADD CONSTRAINT `accountingFixedAsset_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`),
  ADD CONSTRAINT `accountingFixedAsset_ibfk_2` FOREIGN KEY (`depreciationAccountID`) REFERENCES `accountingAccount` (`accountID`),
  ADD CONSTRAINT `accountingFixedAsset_ibfk_3` FOREIGN KEY (`expenseAccountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `accountingIncomeExpenseViewAccount`
--
ALTER TABLE `accountingIncomeExpenseViewAccount`
  ADD CONSTRAINT `accountingIncomeExpenseViewAccount_ibfk_4` FOREIGN KEY (`incomeExpenseViewID`) REFERENCES `accountingIncomeExpenseView` (`incomeExpenseViewID`),
  ADD CONSTRAINT `accountingIncomeExpenseViewAccount_ibfk_5` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `accountingTransactionLine`
--
ALTER TABLE `accountingTransactionLine`
  ADD CONSTRAINT `accountingTransactionLine_ibfk_1` FOREIGN KEY (`transactionID`) REFERENCES `accountingTransaction` (`transactionID`),
  ADD CONSTRAINT `accountingTransactionLine_ibfk_2` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `adminCustomer`
--
ALTER TABLE `adminCustomer`
  ADD CONSTRAINT `adminCustomer_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `billingInvoice`
--
ALTER TABLE `billingInvoice`
  ADD CONSTRAINT `billingInvoice_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingInvoice_ibfk_2` FOREIGN KEY (`transactionID`) REFERENCES `accountingTransaction` (`transactionID`);

--
-- Constraints for table `billingInvoiceLine`
--
ALTER TABLE `billingInvoiceLine`
  ADD CONSTRAINT `billingInvoiceLine_ibfk_1` FOREIGN KEY (`invoiceID`) REFERENCES `billingInvoice` (`invoiceID`);

--
-- Constraints for table `billingPayment`
--
ALTER TABLE `billingPayment`
  ADD CONSTRAINT `billingPayment_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingPayment_ibfk_2` FOREIGN KEY (`transactionID`) REFERENCES `accountingTransaction` (`transactionID`);

--
-- Constraints for table `billingSubscription`
--
ALTER TABLE `billingSubscription`
  ADD CONSTRAINT `billingSubscription_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingSubscription_ibfk_2` FOREIGN KEY (`revenueAccountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `billingSubscriptionLine`
--
ALTER TABLE `billingSubscriptionLine`
  ADD CONSTRAINT `billingSubscriptionLine_ibfk_1` FOREIGN KEY (`customerID`) REFERENCES `adminCustomer` (`customerID`),
  ADD CONSTRAINT `billingSubscriptionLine_ibfk_2` FOREIGN KEY (`revenueAccountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Constraints for table `suppliersInvoice`
--
ALTER TABLE `suppliersInvoice`
  ADD CONSTRAINT `suppliersInvoice_ibfk_1` FOREIGN KEY (`supplierID`) REFERENCES `suppliersSupplier` (`supplierID`),
  ADD CONSTRAINT `suppliersInvoice_ibfk_2` FOREIGN KEY (`transactionID`) REFERENCES `accountingTransaction` (`transactionID`);

--
-- Constraints for table `suppliersPayment`
--
ALTER TABLE `suppliersPayment`
  ADD CONSTRAINT `suppliersPayment_ibfk_1` FOREIGN KEY (`supplierID`) REFERENCES `suppliersSupplier` (`supplierID`),
  ADD CONSTRAINT `suppliersPayment_ibfk_2` FOREIGN KEY (`transactionID`) REFERENCES `accountingTransaction` (`transactionID`);

--
-- Constraints for table `suppliersSupplier`
--
ALTER TABLE `suppliersSupplier`
  ADD CONSTRAINT `suppliersSupplier_ibfk_1` FOREIGN KEY (`accountID`) REFERENCES `accountingAccount` (`accountID`),
  ADD CONSTRAINT `suppliersSupplier_ibfk_2` FOREIGN KEY (`defaultExpenseAccountID`) REFERENCES `accountingAccount` (`accountID`);

--
-- Dumping data for table `accountingCurrency`
--

INSERT INTO `accountingCurrency` (`currencyID`, `name`, `symbol`, `order`) VALUES
(1, 'EUR', '&euro;', 1);

--
-- Dumping data for table `adminUser`
--

INSERT INTO `adminUser` (`userID`, `username`, `password`) VALUES
(1, 'root', '$6$XW791GwKNEgGV8Od$hsNolFgFQ76a2SHXJLZWezA2uaYF33.YNcYjMT7DXJV./tc3aaYCMNpVp7ivEGQh/8hitVKCiGw9TTq5wqud1/');
