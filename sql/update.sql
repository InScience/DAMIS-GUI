-- File for templorary updates

-- Updates after Version 0.2.1
--2017-01-03 Fix of descrition 
UPDATE `component` SET `ComponentDescription` = 'SOM for Multidimensional Data Visualization' WHERE `component`.`ComponentID` = 32;

-- Updates after Version 0.2.0
-- Add RDF 
UPDATE `damis`.`component` SET `ComponentName` = 'RDF', `ComponentIcon` = 'RDF-ico.jpeg', `ComponentWSDLCallFunction` = 'DF', `ComponentDescription` = 'RDF classifier', `FormType` = 'RDF' WHERE `component`.`ComponentID` = 37; 
UPDATE `damis`.`component` SET `ComponentName` = 'RDF', `ComponentIcon` = 'RDF-ico.jpeg', `ComponentWSDLCallFunction` = 'DF', `ComponentDescription` = 'RDF classifier', `FormType` = 'RDF' WHERE `component`.`ComponentID` = 38;

SELECT * FROM `parameter` where componentId in (35, 36)

-- Change MLP
UPDATE `damis`.`parameter` SET `ParameterName` = 'Training type selection parameter', `ParameterSlug` = 'kFoldValidation', `ParameterPosition` = '3' WHERE `parameter`.`ParameterID` = 18; 
UPDATE `damis`.`parameter` SET `ParameterName` = 'Training type selection parameter', `ParameterSlug` = 'kFoldValidation', `ParameterPosition` = '3' WHERE `parameter`.`ParameterID` = 27;

UPDATE `damis`.`parameter` SET `ParameterName` = 'Size of training data/Cross validation k', `ParameterSlug` = 'qty', `ParameterPosition` = '4' WHERE `parameter`.`ParameterID` = 19; 
UPDATE `damis`.`parameter` SET `ParameterName` = 'Size of training data/Cross validation k', `ParameterSlug` = 'qty', `ParameterPosition` = '4' WHERE `parameter`.`ParameterID` = 28;

SET FOREIGN_KEY_CHECKS=0;

DELETE FROM `damis`.`parameter` WHERE `parameter`.`ParameterID` = 20;
DELETE FROM `damis`.`parameter` WHERE `parameter`.`ParameterID` = 21;
DELETE FROM `damis`.`parameter` WHERE `parameter`.`ParameterID` = 29;
DELETE FROM `damis`.`parameter` WHERE `parameter`.`ParameterID` = 30;

SET FOREIGN_KEY_CHECKS=1;

UPDATE `damis`.`parameter` SET `ParameterPosition` = '5' WHERE `parameter`.`ParameterID` = 15; 
UPDATE `damis`.`parameter` SET `ParameterPosition` = '5' WHERE `parameter`.`ParameterID` = 24;

-- User update
ALTER TABLE `users` ADD `user_id` VARCHAR(255) NULL AFTER `organisation` 

-- Updates after 2014-04-14  Version 0.1.1

-- Filter data component update
UPDATE `damis`.`component` SET `ComponentDescription` = 'Filter data description' WHERE `component`.`ComponentID` = 9; 
UPDATE `damis`.`component` SET `ComponentDescription` = 'Filter data description' WHERE `component`.`ComponentID` = 10;

--  NEW experiment statuses
INSERT INTO `damis`.`experimentstatus` (`ExperimentStatus`, `ExperimentStatusID`) VALUES ('SUSPENDED', 5), ('EXAMPLE', 6); 

-- DF component was with bad parameter. q set to r
UPDATE `damis`.`parameter` SET `ParameterSlug` = 'r' WHERE `parameter`.`ParameterID` = 169;
UPDATE `damis`.`parameter` SET `ParameterSlug` = 'r' WHERE `parameter`.`ParameterID` = 172; 

-- Updates after 2014-04-14  Version 0.2.0
-- MLP parameters order bug fix 
UPDATE `damis`.`parameter` SET `ParameterName` = 'Training type selection parameter', `ParameterSlug` = 'kFoldValidation', `ParameterPosition` = '4' WHERE `parameter`.`ParameterID` = 18; 
UPDATE `damis`.`parameter` SET `ParameterName` = 'Training type selection parameter', `ParameterSlug` = 'kFoldValidation', `ParameterPosition` = '4' WHERE `parameter`.`ParameterID` = 27;

UPDATE `damis`.`parameter` SET `ParameterName` = 'Size of training data/Cross validation k', `ParameterSlug` = 'qty', `ParameterPosition` = '3' WHERE `parameter`.`ParameterID` = 19; 
UPDATE `damis`.`parameter` SET `ParameterName` = 'Size of training data/Cross validation k', `ParameterSlug` = 'qty', `ParameterPosition` = '3' WHERE `parameter`.`ParameterID` = 28;
