PhpCap
==========================================================================

Author: Andy Arenson, aarenson@iu.edu

Overview
--------

The PhpCap package provides classes that simplify the use
of the REDCap application programming interface (API) using PHP.

For information about REDCap please see http://www.project-redcap.org.
REDCap is a mature, secure web application for building and managing
online surveys and databases.

The REDCap API is a set of web services that allow other programs to
interoperate with REDCap by exporting or importing data using HTTP
Post requests. The PhpCap classes provide higher level methods for
commonly performed tasks such as exporting sets of records defined by
various criteria, importing records, and reporting problems via
email. The PhpCap classes also provide methods for common related
tasks such as checking advanced link authorization, retrieving
parameters sent by a data entry trigger, and checking whether or not a
request was sent by an authorized server.

Architecture
===========================================================================

The `REDCapFactory` class represents a REDCap instance. It is
configured with information that is general to the usage of the API
with any REDCap project in that instance, such as the URL for API
requests. Once configured, a `REDCapFactory` object is used to create
`REDCapProject` objects, which are then configured with information
specific to using the API with that project, such as the API token to
use and which field is the primary key for that project.

The `REDCapFactory` object can also be used to create a
`REDCapDETHandler` object, which would be configured with information
for assuring that data entry trigger requests only come from expected
sources -- particular servers and/or a particular REDCap `project_id`.

`REDCapFactory` and `REDCapProject` objects must be configured with
a Notifier object, which is used by the `REDCapProject` object to send a
notification by some means (typically email) if a problem occurs in
trying to use the REDCap API.

If multiple programs are going to interact via the REDCap API with the
same set of REDCap projects, it is useful to have a single class,
perhaps called "Connection.php", that sets up all of the required
`REDCapFactory`, `REDCapProject`, and `REDCapDETHandler` objects, as
well as providing methods for the business logic of the executables --
methods that take business terms and translate them into PhpCap
methods.

For example, an executable that needs to get records from
REDCap for all of the subjects that are female might call the method
`Connection->get_subjects_female` using the business logic terms
'subjects' and 'female', and then the `get_subjects_female` method would
in turn use a method like `REDCapProject->get_records_by_fields`, which
provides the common functionality of needing to retrieve only those
records that match certain values in certain fields.

The calling structure and inheritance structure would look
similar to:

```php
project-executable.php

   extends ProjectConnection class
```
which uses `REDCapFactory` class to create `REDCapProject` and `REDCapDETHandler` classes.


Files
===========================================================================

* `PhpCap.php` contains the classes:
    1. `REDCapFactory`
    1. `REDCapProject`
    1. `REDCapDETHandler`
    1. `RestCallRequst` - This class is used by the others to send API calls to REDCap

* `SampleNotifier.php` includes an example helper class that's needed
by PhpCap classes for sending notifications via email if there are
errors in using the REDCap API.

* `SampleConnection.php` includes an example class, `SampleConnection`, 
that configures and uses PhpCap classes for a sample project.

* `sample.php` is an example executable that uses `SampleConnection.pm`


Class Methods/Attributes
===========================================================================

###  `REDCapProject` class
Provides a variety of export and import methods. Uses
a `Notifier` object to determine what to do with errors
encountered with the REDCap API
	      
| Method                     | Description |
| :------------------------- | :---------- |
| `get_records_all`          | All records with all fields |
| `get_ids_all`              | The ID for every record |
| `get_partials_all`         | A subset of fields for every record |
| `get_records_by_ids`       | All fields for the specified records |
| `get_partials_by_ids`      | A subset of fields for the specified records |
| `get_records_by_fields`    | All fields for records that match values of some particular fields |
| `get_ids_by_fields`        | The ids for records that match values of some particular fields |
| `get_partials_by_fields`   | A subset of fields that match values of some particular fields |
| `get_records_by_ids_batch` | Uses batching to get all fields for the specified record. Requires `prep_records_by_ids_batch` |
| `get_file_from_project`    | A file from a field |
| `get_lookup_choices`       | All fields that have labels. For each field, an array of categories to labels. |
| `get_fieldnames`           | All fieldnames, including a field for each checkbox choice. |
| `get_project_id`           | The ID of the project |
| `get_survey_url`           | The URL for a survey for a particular participant. |
| `get_survey_return_code`   | The return code for a survey for a particular participant. |
| `import_records`           | Importing records |
| `check_advanced_link_auth` | Check authkey sent by advanced link |


### `REDCapFactory` class 
Models an entire REDCap instance.  Provides a way to set the common configuations for a set of REDCapProject objects once.

| Method                     | Description |
| :------------------------- | :---------- |
| `make_project`             | Returns `REDCapProject` object |
| `make_dethandler`          | Returns `REDCapDETHandler` object |
| `set_notifier`             | Sets `Notifier` object to be used by any created objects |
   
  
### `REDCapDETHandler` class  
Provides methods for handling requests generated by a data entry trigger.

| Method                     | Description |
| :------------------------- | :---------- |
| `get_det_params`           | Retrieves the parameters passed as arguments from the data entry trigger. |
| `check_allowed_servers`    | Assures that only requests from allowed servers will be processed. |
| `check det_id`             | Assures that supplied `project_id` is the `expected project_id`. |


Examples
===========================================================================

```php
// Create a REDCapProject object
$notifier = new Notifier('admin@somewhere.edu');
	    	         
$apifactory = new REDCapFactory('Application name',
                                'http://redcap.somewhere.edu/api',
                                $notifier);

$project = $apifactory->make_project('ABC...','primary_key');


// Check advanced authorization
$username = $project->check_advanced_link_auth($authkey);


// Get the entire record for a particular record_id and
// print the value for the field 'gender'
$results = $project->get_records_by_ids('Unable to get record',
                                        $record_id);
$record = $results[0];
print $record['gender'];
```	
