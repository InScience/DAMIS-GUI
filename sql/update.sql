-- File for templorary updates

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
