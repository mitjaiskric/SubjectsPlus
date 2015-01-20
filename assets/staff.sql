-- phpMyAdmin SQL Dump
-- version 4.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost:8889
-- Generation Time: Jan 14, 2015 at 07:26 PM
-- Server version: 5.5.38
-- PHP Version: 5.5.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sp3`
--

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `lname`, `fname`, `title`, `tel`, `department_id`, `staff_sort`, `email`, `ip`, `access_level`, `user_type_id`, `password`, `active`, `ptags`, `extra`, `bio`, `position_number`, `job_classification`, `room_number`, `supervisor_id`, `emergency_contact_name`, `emergency_contact_relation`, `emergency_contact_phone`, `street_address`, `city`, `state`, `zip`, `home_phone`, `cell_phone`, `fax`, `intercom`, `lat_long`) VALUES
(1, 'Admin', 'Super', 'SubjectsPlus Admin', '5555', 1, 0, 'cgb37@miami.edu', '', 0, 1, '1feac3d174d7a8943b652bce764e4c0d', 1, 'talkback|faq|records|eresource_mgr|videos|admin|librarian|supervisor', '{"css": "basic"}', 0x54686973206973207468652064656661756c74207573657220776974682061205375626a65637473506c757320696e7374616c6c2e2020596f752073686f756c642064656c657465206f722072656e616d65206d65206265666f726520796f7520676f206c69766521, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
