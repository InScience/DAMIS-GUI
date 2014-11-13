SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `damis`
--

-- --------------------------------------------------------

--
-- Table structure for table `cluster`
--

CREATE TABLE IF NOT EXISTS `cluster` (
  `ClusterName` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ClusterWorkloadHost` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ClusterDescription` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ClusterID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkloadUrl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ClusterUrl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ClusterID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `component`
--

CREATE TABLE IF NOT EXISTS `component` (
  `ComponentName` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ComponentIcon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ComponentWSDLRunHost` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ComponentWSDLCallFunction` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ComponentDescription` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ComponentAltDescription` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ComponentLabelLT` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ComponentLabelEN` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ComponentID` int(11) NOT NULL AUTO_INCREMENT,
  `FormType` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ClusterID` int(11) DEFAULT NULL,
  `ComponentTypeID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ComponentID`),
  UNIQUE KEY `COMPONENT_PK` (`ComponentID`),
  KEY `IDX_49FEA157B26192BE` (`ClusterID`),
  KEY `IDX_49FEA1576ACDD642` (`ComponentTypeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=47 ;

-- --------------------------------------------------------

--
-- Table structure for table `componenttype`
--

CREATE TABLE IF NOT EXISTS `componenttype` (
  `ComponentType` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ComponentTypeID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ComponentTypeID`),
  UNIQUE KEY `COMPONENTTYPE_PK` (`ComponentTypeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `dataset`
--

CREATE TABLE IF NOT EXISTS `dataset` (
  `DatasetIsMIDAS` int(11) NOT NULL,
  `DatasetTitle` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `DatasetCreated` int(11) NOT NULL,
  `DatasetFilePath` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DatasetUpdated` int(11) DEFAULT NULL,
  `file` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `DatasetDescription` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DatasetID` int(11) NOT NULL AUTO_INCREMENT,
  `Hidden` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  PRIMARY KEY (`DatasetID`),
  UNIQUE KEY `DATASET_PK` (`DatasetID`),
  KEY `IDX_B7A041D058746832` (`UserID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=447 ;

-- --------------------------------------------------------

--
-- Table structure for table `entity_log`
--

CREATE TABLE IF NOT EXISTS `entity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `logged_at` datetime NOT NULL,
  `object_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `version` int(11) NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:array)',
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=132 ;

-- --------------------------------------------------------

--
-- Table structure for table `experiment`
--

CREATE TABLE IF NOT EXISTS `experiment` (
  `ExperimentName` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ExperimentMaxDuration` time DEFAULT NULL,
  `ExpermentStart` int(11) DEFAULT NULL,
  `ExperimentFinish` int(11) DEFAULT NULL,
  `ExperimentUseCPU` int(11) DEFAULT NULL,
  `ExperimentUsePrimaryMemory` int(11) DEFAULT NULL,
  `ExperimentUseSecMemory` int(11) DEFAULT NULL,
  `ExperimentGUIData` longtext COLLATE utf8_unicode_ci,
  `ExperimentID` int(11) NOT NULL AUTO_INCREMENT,
  `ExperimentStatusID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ExperimentID`),
  UNIQUE KEY `EXPERIMENT_PK` (`ExperimentID`),
  KEY `IDX_136F58B234472C01` (`ExperimentStatusID`),
  KEY `IDX_136F58B258746832` (`UserID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=348 ;

-- --------------------------------------------------------

--
-- Table structure for table `experimentstatus`
--

CREATE TABLE IF NOT EXISTS `experimentstatus` (
  `ExperimentStatus` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ExperimentStatusID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ExperimentStatusID`),
  UNIQUE KEY `EXPERIMENTSTATUS_PK` (`ExperimentStatusID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

CREATE TABLE IF NOT EXISTS `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `groupName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` longtext COLLATE utf8_unicode_ci,
  `position` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `language` enum('lt','en') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_140AB620989D9B62` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `parameter`
--

CREATE TABLE IF NOT EXISTS `parameter` (
  `ParameterName` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ParameterIsRequired` int(11) NOT NULL,
  `ParameterDefault` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterDescription` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterLabelLT` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterLabelEN` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterID` int(11) NOT NULL AUTO_INCREMENT,
  `ParameterSlug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterPosition` int(11) DEFAULT NULL,
  `ParameterTypeID` int(11) DEFAULT NULL,
  `ParameterConnectionTypeID` int(11) DEFAULT NULL,
  `ComponentID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ParameterID`),
  UNIQUE KEY `PARAMETER_PK` (`ParameterID`),
  KEY `IDX_2A979110D50E99E0` (`ParameterTypeID`),
  KEY `IDX_2A97911010C17FF9` (`ParameterConnectionTypeID`),
  KEY `IDX_2A979110C364FDFE` (`ComponentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=191 ;

-- --------------------------------------------------------

--
-- Table structure for table `parameterconnectiontype`
--

CREATE TABLE IF NOT EXISTS `parameterconnectiontype` (
  `ParameterConnectionType` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ParameterConnectionTypeID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ParameterConnectionTypeID`),
  UNIQUE KEY `PARAMETERCONNECTIONTYPE_PK` (`ParameterConnectionTypeID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `parametertype`
--

CREATE TABLE IF NOT EXISTS `parametertype` (
  `ParameterType` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `ParameterTypeID` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ParameterTypeID`),
  UNIQUE KEY `PARAMETERTYPE_PK` (`ParameterTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `parametervalue`
--

CREATE TABLE IF NOT EXISTS `parametervalue` (
  `ParameterValue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParameterValueID` int(11) NOT NULL AUTO_INCREMENT,
  `WorkflowTaskID` int(11) DEFAULT NULL,
  `ParameterID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ParameterValueID`),
  UNIQUE KEY `PARAMETERVALUE_PK` (`ParameterValueID`),
  KEY `IDX_EF5C2B2A199F0DB9` (`WorkflowTaskID`),
  KEY `IDX_EF5C2B2A5A8577F9` (`ParameterID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4469 ;

-- --------------------------------------------------------

--
-- Table structure for table `pvalueoutpvaluein`
--

CREATE TABLE IF NOT EXISTS `pvalueoutpvaluein` (
  `InParameterValueID` int(11) NOT NULL,
  `OutParameterValueID` int(11) DEFAULT NULL,
  PRIMARY KEY (`InParameterValueID`),
  KEY `IDX_A522F8AE88C1F20` (`OutParameterValueID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username_canonical` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_canonical` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `expired` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `confirmation_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `credentials_expired` tinyint(1) NOT NULL,
  `credentials_expire_at` datetime DEFAULT NULL,
  `registeredAt` datetime NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `surname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `organisation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_1483A5E992FC23A8` (`username_canonical`),
  UNIQUE KEY `UNIQ_1483A5E9A0D96FBF` (`email_canonical`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `workflowtask`
--

CREATE TABLE IF NOT EXISTS `workflowtask` (
  `WorkflowTaskIsRunning` int(11) NOT NULL,
  `WorkflowTaskID` int(11) NOT NULL AUTO_INCREMENT,
  `TaskBox` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Message` longtext COLLATE utf8_unicode_ci,
  `ExecutionTime` double DEFAULT NULL,
  `ExperimentID` int(11) DEFAULT NULL,
  PRIMARY KEY (`WorkflowTaskID`),
  UNIQUE KEY `WORKFLOWTASK_PK` (`WorkflowTaskID`),
  KEY `IDX_5F598CF2BAA1BE51` (`ExperimentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1884 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `component`
--
ALTER TABLE `component`
  ADD CONSTRAINT `FK_49FEA1576ACDD642` FOREIGN KEY (`ComponentTypeID`) REFERENCES `componenttype` (`ComponentTypeID`),
  ADD CONSTRAINT `FK_49FEA157B26192BE` FOREIGN KEY (`ClusterID`) REFERENCES `cluster` (`ClusterID`);

--
-- Constraints for table `dataset`
--
ALTER TABLE `dataset`
  ADD CONSTRAINT `FK_B7A041D058746832` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `experiment`
--
ALTER TABLE `experiment`
  ADD CONSTRAINT `FK_136F58B234472C01` FOREIGN KEY (`ExperimentStatusID`) REFERENCES `experimentstatus` (`ExperimentStatusID`),
  ADD CONSTRAINT `FK_136F58B258746832` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`);

--
-- Constraints for table `parameter`
--
ALTER TABLE `parameter`
  ADD CONSTRAINT `FK_2A97911010C17FF9` FOREIGN KEY (`ParameterConnectionTypeID`) REFERENCES `parameterconnectiontype` (`ParameterConnectionTypeID`),
  ADD CONSTRAINT `FK_2A979110C364FDFE` FOREIGN KEY (`ComponentID`) REFERENCES `component` (`ComponentID`),
  ADD CONSTRAINT `FK_2A979110D50E99E0` FOREIGN KEY (`ParameterTypeID`) REFERENCES `parametertype` (`ParameterTypeID`);

--
-- Constraints for table `parametervalue`
--
ALTER TABLE `parametervalue`
  ADD CONSTRAINT `FK_EF5C2B2A199F0DB9` FOREIGN KEY (`WorkflowTaskID`) REFERENCES `workflowtask` (`WorkflowTaskID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_EF5C2B2A5A8577F9` FOREIGN KEY (`ParameterID`) REFERENCES `parameter` (`ParameterID`);

--
-- Constraints for table `pvalueoutpvaluein`
--
ALTER TABLE `pvalueoutpvaluein`
  ADD CONSTRAINT `FK_A522F8AE88C1F20` FOREIGN KEY (`OutParameterValueID`) REFERENCES `parametervalue` (`ParameterValueID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_A522F8AEF169B8E2` FOREIGN KEY (`InParameterValueID`) REFERENCES `parametervalue` (`ParameterValueID`) ON DELETE CASCADE;

--
-- Constraints for table `workflowtask`
--
ALTER TABLE `workflowtask`
  ADD CONSTRAINT `FK_5F598CF2BAA1BE51` FOREIGN KEY (`ExperimentID`) REFERENCES `experiment` (`ExperimentID`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
