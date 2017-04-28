User Tutorial
====================================

Prerequisites
----------------------
  * __Your REDCap API URL.__ You need to know the URL of your REDCap's API (Application programming interface)
  * __REDCap Project with API Token.__ You need to have an API token for a project in REDCap. You would typically get this
    by creating a project in REDCap and then requesting an API token.
  * __PHP 5.6+ with cURL and OpenSSL.__You need to have a computer (with Internet access) that has PHP version 5.6 or greater installed. And you need to have the following PHP modules enabled:
    * cURL
    * OpenSSL

Example PHP Setup on Ubuntu 16:
    
    sudo apt-get install php php-curl
    
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

Or go to this page and click on the "Clone of download" button, and then click on "Download ZIP":

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
        test.php

Enter the following into the __test.php__ file, modifying the API URL and token to match those for your REDCap project:

```php
<?php

require('PHPCap/autoloader.php');  # Adjust the path as necessary

use \IU\PHPCap\RedCapProject;

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