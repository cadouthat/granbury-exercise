-- phpMyAdmin SQL Dump
-- version 3.3.9.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 16, 2015 at 01:29 PM
-- Server version: 5.6.14
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `granbury`
--

-- --------------------------------------------------------

--
-- Table structure for table `apivars`
--

CREATE TABLE IF NOT EXISTS `apivars` (
  `name` varchar(50) NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `apivars`
--

INSERT INTO `apivars` (`name`, `value`) VALUES
('next_order', 1);

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `name` varchar(50) NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `item`
--


-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE IF NOT EXISTS `order` (
  `orderId` int(11) NOT NULL AUTO_INCREMENT,
  `orderNumber` int(11) NOT NULL DEFAULT '0',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subTotal` int(11) NOT NULL DEFAULT '0',
  `totalTax` int(11) NOT NULL DEFAULT '0',
  `grandTotal` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `order`
--


-- --------------------------------------------------------

--
-- Table structure for table `orderlineitem`
--

CREATE TABLE IF NOT EXISTS `orderlineitem` (
  `orderId` int(11) NOT NULL,
  `itemName` varchar(50) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `extendedPrice` int(11) NOT NULL,
  PRIMARY KEY (`orderId`,`itemName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `orderlineitem`
--


-- --------------------------------------------------------

--
-- Table structure for table `salestaxrate`
--

CREATE TABLE IF NOT EXISTS `salestaxrate` (
  `description` varchar(50) NOT NULL,
  `rate` int(11) NOT NULL,
  PRIMARY KEY (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `salestaxrate`
--


-- --------------------------------------------------------

--
-- Table structure for table `tenderrecord`
--

CREATE TABLE IF NOT EXISTS `tenderrecord` (
  `orderId` int(11) NOT NULL,
  `amountTendered` int(11) NOT NULL,
  `changeGiven` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`orderId`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tenderrecord`
--

