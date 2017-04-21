Developer README
==========================

This README is for people interested in developing PHPCap.

Setup
--------------------------------------------------------
1. Install PHP 5.6 or greater with cURL and OpenSSL enabled.
1. Install Git. The code for PHPCap is stored in GitHub, and Git is required to be able to download it for development.
   See: [https://git-scm.com/downloads](https://git-scm.com/downloads)
2. Get PHPCap:
     
    ```shell
    git clone https://github.com/aarenson/PHPCap
    ```
    
3. Get Composer. Composer is needed to download the development depedencies needed for PHPCap.
   See: [https://getcomposer.org/download/](https://getcomposer.org/download/).
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
  
      



Usage
-----------------------------------------

### Automated Tests
Before the tests can be run, setup and configuration needs to be completed: see [../tests/README.md](../tests/README.md). 

To run the automated tests, execute the following command in the top-level directory of your downloaded version of PHPCap:

    ./vendor/bin/phpunit
    
Note: PHPUnit uses the **phpunit.xml** configuration file in the root directory of PHPCap.


### Coding Standard Compliance
PHPCap follows the PSR-1 and PSR-2 coding standards. See:
* [http://www.php-fig.org/psr/psr-1/](http://www.php-fig.org/psr/psr-1/)
* [http://www.php-fig.org/psr/psr-2/](http://www.php-fig.org/psr/psr-2/)

To check for compliance, execute the following command in the root directory of the PHPCap:

    ./vendor/bin/phpcs --standard=PSR1,PSR2 src

PHPCap also follows the PSR-4 (Autoloader) standard, see: [http://www.php-fig.org/psr/psr-4/](http://www.php-fig.org/psr/psr-4/)

### API Document Generation
To generate the API documentation (stored in **./docs/api**), execute the following command in PHPCap's root directory:

    ./vendor/bin/apigen generate
    
Note: ApiGen uses the **apigen.neon** configuration file in the root directory of PHPCap.

The API documentation is stored in Git to eliminate the need for non-developer users to install Composer and the developer depedencies.



