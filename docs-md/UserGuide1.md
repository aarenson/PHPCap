User Guide 1 - Getting Started
====================================

Prerequisites
----------------------
  * __Your REDCap API URL.__ You need to know the URL of your REDCap's API (Application Programming Interface)
  * __REDCap Project with API Token.__ You need to have an API token for a project in REDCap. You would typically get this
    by creating a project in REDCap and then requesting an API token.
  * __PHP 5.6+ with cURL and OpenSSL.__You need to have a computer (with Internet access) that has PHP version 5.6 or greater installed. And you need to have the following PHP modules enabled:
    * cURL
    * OpenSSL

Example PHP Setup on Ubuntu 16:
    
    sudo apt-get install php php-curl

Information on installing PHP on Windows: http://php.net/manual/en/install.windows.php
    
Creating a PHPCap Project
------------------------------

### Create a project directory.

Create a directory for your project, and cd to that directory:

    mkdir phpcap-project
    cd phpcap-project

### Get PHPCap
  
Get PHPCap from GitHub. If you have Git installed, you can use the following:

    git clone https://github.com/aarenson/PHPCap 

If you don't have Git installed, you can get a Zip file of PHPCap by clicking on this link:

https://github.com/aarenson/PHPCap/archive/master.zip

Or go to this page and click on the "Clone or download" button, and then click on "Download ZIP":

https://github.com/aarenson/PHPCap/

Then unzip the file to your project directory.
    
You should now have the following directory structure:

    phpcap-project/
        PHPCap/
            docs/
            src/
            ...
            
### Create your first test program.

Create a file __test.php__ in your project directory:

    phpcap-project/
        PHPCap/
            docs/
            src/
            ...
            autoloader.php
            ...
        test.php

Enter the following into the __test.php__ file, modifying the API URL and token to match those for your REDCap project:

```php
<?php

require('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '1234567890A1234567890B1234567890';    # replace with your actual API token

$project = new RedCapProject($apiUrl, $apiToken);
$projectInfo = $project->exportProjectInfo();

print_r($projectInfo);
```    

Run the test program using the following command in your project directory:

    php test.php
    
You should see output generated with information about your project.
It should look similar to the following, although some of the values will
probably be different:

```php
Array
(
    [project_id] => 9639
    [project_title] => PHPCap Basic Demography Test
    [creation_time] => 2017-03-31 13:40:53
    [production_time] => 
    [in_production] => 0
    [project_language] => English
    [purpose] => 1
    [purpose_other] => PHPCap testing
    [project_notes] => 
    [custom_record_label] => 
    [secondary_unique_field] => 
    [is_longitudinal] => 0
    [surveys_enabled] => 1
    [scheduling_enabled] => 0
    [record_autonumbering_enabled] => 0
    [randomization_enabled] => 0
    [ddp_enabled] => 0
    [project_irb_number] => 
    [project_grant_number] => 
    [project_pi_firstname] => 
    [project_pi_lastname] => 
    [display_today_now_button] => 1
)
```

### Making your test program secure.

The program above is not secure, because it does not use SSL verification to verify that the
REDCap site accessed is the one actually intended. To make the program more secure, it
should use SSL verification. 

To do this you need to add the SSL verify flag and set it to true:

```php
...
$sslVerify = true;
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify);
...
```

But unless your system has already been set up to verify connections, you will need to create a
certificate file
for this, and add it also. Information on creating the file
can be found here: [CA Certificate file](CACertificateFile.md)

Assuming the file was created with the name 'USERTrustRSACertificationAuthority.crt' and is in
you top-level project directory, the project creation would now be modified to the following:

```php
...
$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
...
```

So, at this point, your project directory should look as follows:

    phpcap-project/
        PHPCap/
            docs/
            src/
            ...
            autoloader.php
            ...
        test.php
        USERTrustRSACertificationAuthority.crt
        
And your test program should look similar to the following:


```php
<?php

require('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '1234567890A1234567890B1234567890';    # replace with your actual API token

$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
$project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
$projectInfo = $project->exportProjectInfo();

print_r($projectInfo);
```    

If everything is working correctly, the test program should (still) output information about your project.

### Checking for errors.

In general, when an error occurs in PHPCap, it throws a PhpCapException.
These exceptions can be checked and handled using "try" and "catch". For example,
to handle exceptions in the sample program, it could be modified as follows:
```php
<?php

require('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

$apiUrl = 'https://redcap.xxxxx.edu/api/';  # replace this URL with your institution's
                                            # REDCap API URL.
                                                 
$apiToken = '1234567890A1234567890B1234567890';    # replace with your actual API token

$sslVerify = true;
$caCertificateFile = 'USERTrustRSACertificationAuthority.crt';
try {
    $project = new RedCapProject($apiUrl, $apiToken, $sslVerify, $caCertificateFile);
    $projectInfo = $project->exportProjectInfo();
    print_r($projectInfo);
} catch (PhpCapException $exception) {
    print "The following error occurred: {$exception->getMessage()}\n";
    print "Here is a stack trace:\n";
    print $exception->getTraceAsString()."\n";
}


```    
Note that in addition to the "try" and "catch" that were added, an additional use statement was
added for the PhpCapException class: 
```php
use IU\PHPCap\PhpCapException;
```