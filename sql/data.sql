INSERT INTO `cluster` (`ClusterName`, `ClusterWorkloadHost`, `ClusterDescription`, `ClusterID`) VALUES
('MII Cluster', 'test', '&lt;p&gt;Distributed Computing cluster of Vilnius University Institute of Mathematics and Informatics.&lt;/p&gt;&lt;p&gt;Cluster home:&lt;/p&gt;&lt;p&gt;&lt;a href=&quot;http://cluster.mii.lt/&quot; target=&quot;blank&quot;&gt;http://cluster.mii.lt/&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Cluster workload:&lt;/p&gt;&lt;p&gt;&lt;a href=&quot;http://cluster.mii.lt/ganglia/&quot; target=&quot;blank&quot;&gt;http://cluster.mii.lt/ganglia/&lt;/a&gt;&lt;/p&gt; ', 1),
('MIF VU SK2', 'test', '&lt;p&gt;Supercomputer of Vilnius University Faculty of Mathematics and Informatics.&lt;/p&gt;&lt;p&gt;Cluster home:&lt;/p&gt;&lt;p&gt;&lt;a href=&quot;http://mif.vu.lt/cluster/&quot; target=&quot;blank&quot;&gt;http://mif.vu.lt/cluster/&lt;/a&gt;&lt;/p&gt;&lt;p&gt;Cluster workload:&lt;/p&gt;&lt;p&gt;&lt;a href=&quot;http://k007.mif.vu.lt/ganglia2/&quot; target=&quot;blank&quot;&gt;http://k007.mif.vu.lt/ganglia2/&lt;/a&gt;&lt;/p&gt;', 2);

INSERT INTO `componenttype` (`ComponentType`, `ComponentTypeID`) VALUES
('Upload data', 1),
('Preprocessing', 2),
('Statistical primitives', 3),
('Dimension reduction', 4),
('Classification, grouping', 5),
('View results', 6);

INSERT INTO `parameterconnectiontype` (`ParameterConnectionType`, `ParameterConnectionTypeID`) VALUES
('INPUT_CONNECTION', 1),
('OUTPUT_CONNECTION', 2);

INSERT INTO `component` (`ComponentName`, `ComponentIcon`, `ComponentWSDLRunHost`, `ComponentWSDLCallFunction`, `ComponentDescription`, `ComponentAltDescription`, `ComponentLabelLT`, `ComponentLabelEN`, `ComponentID`, `ClusterID`, `ComponentTypeID`) VALUES
('Upload new file', 'upload-file-ico-1.jpeg', '', '', NULL, NULL, NULL, NULL, 1, 1, 1),
('Upload new file', 'upload-file-ico-1.jpeg', '', '', NULL, NULL, NULL, NULL, 2, 2, 1);


INSERT INTO `parameter` (`ParameterName`, `ParameterIsRequired`, `ParameterDefault`, `ParameterDescription`, `ParameterLabelLT`, `ParameterLabelEN`, `ParameterID`, `ParameterTypeID`, `ParameterConnectionTypeID`, `ComponentID`) VALUES
('', 0, NULL, NULL, NULL, NULL, 1, NULL, 2, 1),
('', 0, NULL, NULL, NULL, NULL, 2, NULL, 2, 2);