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