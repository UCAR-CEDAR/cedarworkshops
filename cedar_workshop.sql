-- phpMyAdmin SQL Dump
-- version 2.11.10.1
-- http://www.phpmyadmin.net
--
-- Host: databases.hao.ucar.edu:3306
-- Generation Time: Mar 01, 2013 at 07:18 PM
-- Server version: 5.0.95
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wikidb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cedar_workshop_20XX`
--

CREATE TABLE IF NOT EXISTS `cedar_workshop_2016` (
  `proposal_id` int(10) NOT NULL auto_increment,
  `long_title` varchar(256) NOT NULL,
  `short_title` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `justification` text NOT NULL,
  `proposer_id` int(10) NOT NULL,
  `proposer_name` varchar(64) NOT NULL,
  `proposer_email` varchar(64) NOT NULL,
  `convener1_id` int(10) NOT NULL,
  `convener1_name` varchar(64) NOT NULL,
  `convener1_email` varchar(64) NOT NULL,
  `convener2_id` int(10) NOT NULL,
  `convener2_name` varchar(64) NOT NULL,
  `convener2_email` varchar(64) NOT NULL,
  `convener3_id` int(10) NOT NULL,
  `convener3_name` varchar(64) NOT NULL,
  `convener3_email` varchar(64) NOT NULL,
  `convener4_id` int(10) NOT NULL,
  `convener4_name` varchar(64) NOT NULL,
  `convener4_email` varchar(64) NOT NULL,
  `convener5_id` int(10) NOT NULL,
  `convener5_name` varchar(64) NOT NULL,
  `convener5_email` varchar(64) NOT NULL,
  `convener6_id` int(10) NOT NULL,
  `convener6_name` varchar(64) NOT NULL,
  `convener6_email` varchar(64) NOT NULL,
  `altitudes` varchar(32) NOT NULL,
  `inst_model` varchar(32) NOT NULL,
  `latitudes` varchar(32) NOT NULL,
  `other_cat` varchar(64) NOT NULL,
  `workshop_format` varchar(32) NOT NULL,
  `duration` varchar(32) NOT NULL,
  `estimated_attendance` int(10) NOT NULL,
  `conflicts` varchar(512) NOT NULL,
  `requests` varchar(512) NOT NULL,
  `gc_request` varchar(512) NOT NULL,
  `gc_timeline` varchar(512) NOT NULL,
  `gc_speakers` varchar(512) NOT NULL,
  `date_submitted` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`proposal_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;
