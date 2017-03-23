PHPCap Test Setup
=====================================

### Pre-requisties
1. REDCap account - you need to have a REDCap account on your REDCap instance


### Steps
1. Log in to REDCap
2. Import the PHPCap-test.xml REDCap project file
3. Request a token for the project imported in the step above
4. Once you have your token, copy the "config-example.php" file to a file
   named "config.php" and then set the URL in that file to be the
   URL for the API of your REDCap instance, and set the token to be
   the token for your project that was requested in the previous step