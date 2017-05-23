PHPCap
==========================================================================

Developers: [Andy Arenson](https://github.com/aarenson), aarenson@iu.edu; [Jim Mullen](https://github.com/mullen2)

PHPCap is a PHP API (Application Programming Interface) for REDCap.

REDCap is a web application for building and managing online surveys and databases. For information about REDCap, please see http://www.project-redcap.org.


Requirements
--------------------------
To use PHPCap, you need to have:
* A computer with PHP 5.6 or later installed, and PHP needs to have cURL and OpenSSL enabled.
* An account on a REDCap site.
* API token(s) for the project(s) you want to access. API tokens need to be requested within the REDCap system.


How to Get PHPCap
--------------------------
If you have Git installed on your computer, you can use

    git clone https://github.com/aarenson/PHPCap

If you have Subversion, you can use

    svn export https://github.com/aarenson/PHPCap/trunk PHPCap

To download a zip file of PHPCap, you can access this link: <a href="https://github.com/aarenson/PHPCap/archive/master.zip" download="PHPCap.zip">PHPCap.zip</a>

Or, you can access [https://github.com/aarenson/PHPCap](https://github.com/aarenson/PHPCap) in a web browser, and then:    
1. Click on the __Clone or download__ button
2. Click on __Download Zip__
3. Unzip the downloaded file  


Example
--------------------------

```php
<?php
require_once('PHPCap/autoloader.php');

use IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.someplace.edu/api/';
$apiToken  = '273424CC67263B849E41CCD2134F37C3';

$project = new RedCapProject($apiUrl, $apiToken);

# Print the project title
$projectInfo = $project->exportProjectInfo();
print "project title: ".$projectInfo['project_title']."\n";

# Print the first and last names for all records
$records = $project->exportRecords();
foreach ($records as $record) {
    print $record['first_name']." ".$record['last_name']."\n";
}
?>
```


API Documentation
----------------------------
Detailed API documentation for PHPCap can be viewed here:
[PHPCap API](https://aarenson.github.io/PHPCap/api/index.html)


