# Damis.midas.lt Update Tasks for Developer

| No. | Task Description | Expected Result |
| :--- | :--- | :--- |
| **1** | Create a working environment on a local computer and transfer the entire damis.midas.lt infrastructure, source code, and data. Test its operation locally. | A prepared working environment where the damis.midas.lt update will subsequently be executed and tested. |
| **2** | Update the user interface software of the local damis.midas.lt version: PHP, Symfony, jQuery, and their components. The software must be updated to standard, secure versions supported on Ubuntu 26.04. Test the operation of these components. <br><br>*Example updates:* PHP 8.4 to 8.5, Symfony 6.4 (LTS) to 7.4 (LTS), MySQL 8.0 to MySQL 8.4 (or 9.7), recompile Damis_service, etc. | Local damis.midas.lt user interface software updated to the latest secure version. |
| **3** | Update the user interface software of the local damis.midas.lt version: PHP, Symfony, jQuery, and their components. The software must be updated to standard, secure versions supported on Ubuntu 26.04. Test the operation of these components. | Local damis.midas.lt user interface software updated to the latest secure version. |
| **4** | Fix bugs identified previously and during the update process (e.g., the "Forgot password" functionality not working). | *(Not explicitly stated in source, but implied: Identified bugs are resolved)* |
| **5** | Install and configure the Awstats package. | Awstats installed for viewing statistics. |
| **6** | Deploy a service to verify that the updated code passes the tests. | Helper service deployed for updating code on GitHub. |
| **7** | Update damis.midas.lt Docker containers with the newly updated software. | Docker containers updated. |
| **8** | Transfer the updated damis.midas.lt version to the updated damis.midas.lt infrastructure. Update the deployment/transfer scripts required for this. Test the integration with the midas.lt infrastructure, as well as the entire user interface and data processing algorithms. | Fully updated damis.midas.lt version successfully running on the midas.lt infrastructure. |
| **9** | Prepare documentation of the implemented changes, specifying which source code versions are used. Update the Damis UI source code at [https://github.com/InScience/DAMIS-GUI](https://github.com/InScience/DAMIS-GUI). Upload the updated DamisService code to GitHub. | Documentation of the executed changes is prepared. |
```eof
