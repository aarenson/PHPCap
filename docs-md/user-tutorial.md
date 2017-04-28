User Tutorial
====================================

* Prerequisites
  * You need to know the URL of your REDCap's API (Application programming interface)
  * You need to have an API token for a project in REDCap. You would typically get this
    by creating a project in REDCap and then requesting an API token.

* Install PHP 5.6+ and make sure that cURL is enabled, for example, in Ubuntu, use the following commands:

    ```shell
    sudo apt install php php-curl
    sudo apt install git
    ```
    
* Create a directory for your project, and cd to that directory:

    ```shell
    mkdir phpcap-project
    cd phpcap-project
    ```
    
* Get PHPCap from GitHub:
    git clone https://github.com/aarenson/PHPCap
    
* Create a file __test.php__ with the following contents:

    ```php
    <?php
    
    require('PHPCap/autoloader.php');  # Adjust the path as necessary
      
    $apiUrl = 'https://redcap.uits.iu.edu/api/';  # replace this URL with your institution's
                                                  # REDCap API URL.
                                                 
    $apiToken = '1234567890A1234567890B1234567890';    # replace with your actual API token
    
    $project = new RedCapProject($apiUrl, $apiToken);
    $projectInfo = $project->exportProjectInfo();
    
    print_r($projectInfo);
    ```    

* Run the test program using the following on the command line:
```
php test.php
```    