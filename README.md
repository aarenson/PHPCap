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

    svn export https://github.com/aarenson/PHPCap

Or, you can access [https://github.com/aarenson/PHPCap](https://github.com/aarenson/PHPCap) in a web browser, and then:    
1. Click on the __Clone or download__ button
2. Click on __Download Zip__
3. Unzip the downloaded file  

Example
--------------------------

```php
<?php
require_once('RedCapProject.php');

use \IU\PHPCap\RedCapProject;

$apiUrl = 'https://redcap.uits.iu.edu/api/';
$token  = '273424CC67263B849E41CCD2134F37C3';

$project = new RedCapProject($apiUrl, $token);

$projectInfo = $project->exportProjectInfo();
print "project title: ".$projectInfo['project_title']."\n";

?>
```