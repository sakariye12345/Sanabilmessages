-- --------------------------------------------------------
-- 🏢 Sanabil Messages - CI3 Demo Database
-- Jidka aad u marayso (How to use):
-- 1. Furo phpMyAdmin oo samee Database cusub (Tusaale 'sanabil_demo')
-- 2. Import u dheh Faylkan ('database.sql')
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `tbl_allowed_parents` (Waalidiinta Loo Oggolyahay)
--

CREATE TABLE `tbl_allowed_parents` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(250) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active' COMMENT 'active ama inactive',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tbl_allowed_parents` (Xogta Macmalka ah ee Waalidiinta)
--

INSERT INTO `tbl_allowed_parents` (`id`, `parent_name`, `phone`, `status`, `address`, `city`) VALUES
(1001, 'Axmed Jaamac Cali', '+252-63-444-4444', 'active', 'Hargeysa', 'Hargeysa'),
(1002, 'Xaliimo Faarax Xasan', '063 555 5555', 'active', 'Jigjiga Yar', 'Hargeysa'),
(1003, 'Mahad Cabdi Nuur', '252636666666', 'inactive', 'Xaafadda Shacabka', 'Hargeysa'),
(1004, 'Faadumo Xuseen Ducaale', '+252 63-7#77 7777', 'active', 'New Hargeysa', 'Hargeysa');

--
-- Indexes for table `tbl_allowed_parents`
--
ALTER TABLE `tbl_allowed_parents`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `tbl_allowed_parents`
--
ALTER TABLE `tbl_allowed_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;
COMMIT;
