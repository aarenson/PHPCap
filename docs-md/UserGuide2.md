User Guide 2 - API Overview
=============================================

The three main classes provided by PHPCap for users are:

| Class                                                                                         | Description                                              |
| --------------------------------------------------------------------------------------------- | -------------------------------------------------------- |                                            
| [RedCapProject](https://aarenson.github.io/PHPCap/api/class-IU.PHPCap.RedCapProject.html)     | used to retrieve data from, and modify, a REDCap project |
| [FileUtil](https://aarenson.github.io/PHPCap/api/class-IU.PHPCap.FileUtil.html)               | used to read from, and write to, files                   |
| [PhpCapException](https://aarenson.github.io/PHPCap/api/class-IU.PHPCap.PhpCapException.html) | exception class used by PHPCap when an error occurs      |
 
 Here is a complete example that uses all three of these classes to export the
 records in the project in CSV format to a file:
 ```php
 <?php 

require('PHPCap/autoloader.php'); # change path to file as needed

use IU\PHPCap\RedCapProject;
use IU\PHPCap\FileUtil;
use IU\PHPCap\PhpCapException;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.

$apiToken = '11111111112222222222333333333344';  # replace with your actual API token

$sslVerify = true;

# set the file path and name to the location of your
# CA certificate file
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';

try {
    $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
    
    # Export the records of the project in CSV format
    # and store then in file 'data.csv'
    $records = $project->exportRecords('csv');
    FileUtil::writeStringToFile($records, 'data.csv');
    
} catch (PhpCapException $exception) {
    print $exception->getMessage();
}
?>
 ```
 __Notes:__
 
 The require statement includes the PHPCap autoloader which loads the PHPCap classes
 that are actually used. So there is no need to require or include the individual
 PHPCap classes.
 
 The use statements allow you to refer to the PHPCap classes without having to specify
 their fully qualified names. For example, if you did not have a use statement for
 the FileUtil class, you would need to use:
 ```php
 IU\PHPCap\FileUtil::writeStringToFile($records, 'data.csv');
 ```
 
 Setting $sslVerify to true and specifying a CA certificate file are very important
 for security reasons. These settings enable PHPCap to verify that the REDCap site
 accessed is actually the one specified in the $apiUrl. If this verification is not
 done, it is possible that another site could impersonate your REDCap site and
 read the data you send and receive. 
 For information on how to create a CA certificate file, see [CA Certificate File](CACertificateFile.md)
 
 For writing the file, you could use PHP's file_put_contents method,
 but an advantage of using PHPCap's FileUtil method is that FileUtil is
 set up to throw a PhpCapException if an error occurs, so it can
 make error handling more consistent and easier.
 
 