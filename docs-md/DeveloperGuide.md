Developer Guide
===================================================

This guide is for people interested in developing PHPCap (i.e., actually making changes to the PHPCap code).

Setup
--------------------------------------------------------
1. Install PHP 5.6 or greater with the following extensions:
  * cURL
  * DOM/XML
  * mbstring
  * OpenSSL
1. Install Git. The code for PHPCap is stored in GitHub, and Git is required to be able to download it for development.
   See: https://git-scm.com/downloads
2. Get PHPCap:
     
    ```shell
    git clone https://github.com/aarenson/PHPCap
    ```
    
3. Get Composer. Composer is needed to download the development depedencies for PHPCap.
   See: https://getcomposer.org/download/.
   You can either install the composer.phar file to the root directory of PHPCap (the ..gitignore 
   file is set to ignore this file), or install it globally at the system or account level.
4. Install PHPCap's development dependencies:

    ```shell
    # If you installed the composer.phar file in PHPCap's root directory:
    php composer.phar install
    
    # If you installed composer globally:
    composer install
    
    # The dependencies should be installed into a "vendor" directory
    # (which will be ignored by Git).    
    ```

Example Setup on Ubuntu 16
-----------------------------------------
To set up PHPCap on Ubuntu 16, execute the following commands:
    
```shell
sudo apt-get install php php-curl php-xml php-mbstring
sudo apt-get install git
git clone https://github.com/aarenson/PHPCap
sudo apt-get install composer
cd PHPCap
composer install
```

Usage
-----------------------------------------

### Automated Tests
Before the tests can be run, setup and configuration needs to be completed:
1. Log in to your REDCap site.
2. Import the test REDCap project files in directory __tests/projects/__.
3. In REDCap, request API tokens for the projects imported in the step above.
4. Once you have your tokens, copy the "config-example.ini" file to a file
   named "config.ini" and then set the URL in that file to be the
   URL for the API of your REDCap instance, and set the tokens to be
   the tokens requested in the previous step.
   
Note: the .gitignore file in PHPCap is set to ignore the __tests/config.ini__ file, so that your
personal API tokens will not be committed to Git. 

To run the automated tests, execute the following command in the top-level directory of your downloaded version of PHPCap:

    ./vendor/bin/phpunit
    
Note: PHPUnit uses the **phpunit.xml** configuration file in the root directory of PHPCap.

### Local Tests
The directory __tests/local/__ has been set up so that all files in it, except for the README file, will be ignored by Git.
This directory is intended as a place for developers to places tests for changes they are working on.

### Coding Standard Compliance
PHPCap follows the PSR-1 and PSR-2 coding standards. See:
* http://www.php-fig.org/psr/psr-1/
* http://www.php-fig.org/psr/psr-2/

To check for compliance, execute the following command in the root directory of the PHPCap:

    ./vendor/bin/phpcs --standard=PSR1,PSR2 src

Note that if you are working on Windows and have the git property __core.autocrlf__ set to true, you may see errors similar to the following:

    ----------------------------------------------------------------------
    FOUND 1 ERROR AFFECTING 1 LINE
    ----------------------------------------------------------------------
    1 | ERROR | [x] End of line character is invalid; expected "\n" but
      |       |     found "\r\n"
    ----------------------------------------------------------------------
    PHPCBF CAN FIX THE 1 MARKED SNIFF VIOLATIONS AUTOMATICALLY
    ----------------------------------------------------------------------
These errors are not important, because the issue should be fixed when you commit your code.

PHPCap also follows the PSR-4 (Autoloader) standard, see: http://www.php-fig.org/psr/psr-4/


### Documentation

Documentation consists of the following:
* Top-level README.md file
* Mardown documents that have been manually created in the __docs-md/__ directory
* HTML API documentation generated from the PHPDoc comments in the code, which are stored in the __docs/api/__ directory
* HTML versions of the Markdown documentation in the docs-md/ directory, which are generated programmatically, stored in the __docs/__ directory, and use the same style as the API documentation.


#### API Document Generation
To generate the API documentation (stored in **./docs/api**), execute the following command in PHPCap's root directory:

    ./vendor/bin/apigen generate
    
Note: ApiGen uses the **apigen.neon** configuration file in the root directory of PHPCap.

The API documentation is stored in Git to eliminate the need for non-developer users to install Composer and the developer dependencies.

#### HTML Document Generation
To generate an HTML version for the Markdown documents in the __docs-md/__ directory, execute the following command in PHPCap's root directory:

    php generate-html-docs.php

