User Guide 2 - Exporting Records
=============================================

PHPCap provides the following 3 methods for exporting records:
1. __exportRecords__ - standard method for exporting records. 
2. __exportRecordsAp__ - "array parameter" method for exporting records.
3. __exportReports__ - method that exports the records produced by a report that
                       is defined for the project in REDCap.

exportRecords
---------------------------
The exportRecords method is a standard PHP method that has 12 parameters that can
be set to modify the records that are exported and their format.

The complete documentation for this method can be found in the PHPCap API documentation:
https://aarenson.github.io/PHPCap/api/class-IU.PHPCap.RedCapProject.html

Since this method corresponds very closely to the REDCap API Export Records method, the
REDCap API documentation can also be checked for more information. And the REDCap
API Playground can be used to get a sense of the functionality provided by this method.

All of the parameters for exportRecords have default values assigned. So the following example
can be used to export all records from a project with default formats:
```php
$records = $project->exportRecords();
```
Other examples:
```php
// export all records in CSV format
$records = $project->exportRecords('csv');

// export records with IDs 1001 and 1002 in XML format
// with one record per XML item ('flat')
$records = $project->exportRecords('xml', 'flat', ['1001', '1002']);

// export only the 'age' and 'bmi' fields for all records in
// CSV format (note that null can be used for arguments
// where you want to use the default value)
$records = $project->exportRecords('csv', null, null, ['age', 'bmi']);
```

exportRecordsAp
---------------------------
The exportRecordsAp method supports the same functionality as the exportRecords method,
but with different parameters. The exportRecordsAp method has a single array parameter
where the keys of the array correspond the the parameter names in the exportRecords
method definition, and the value for each key is the argument value. For example, the
following exportRecordsAp method call would export the records from
the project in XML format for events 'enrollment_arm_1' and 'enrollment_arm_2'.
```php
$records = $project->exportRecordsAp(
    ['format' => 'xml', 'events' => ['enrollment_arm_1', 'enrollment_arm_2']]
);
```

As compared with the exportRecords method, exportRecordsAp lets you specify values
only for the parameters where you want non-default values, and you can
specify them in any order.

For example, if you wanted to export the records from your project in CSV format
with data access group information included, you would use something like the following
with the exportRecords method:
```php
$records = $project->exportRecords('csv', null, null, null, null, null,
    null, null, null, null, null, true);
```
In this case, the order of the arguments has to match extactly with the
order of the parameters in the method definition. And since an argument
for the the last parameter ($exportDataAccessGroups) is being provided, arguments for all
parameters before it need to be included.

The same export could be specified with the exportRecordsAp method as follows:
```php 
$records = $project->exportRecordsAp(['format' => 'csv', 'exportDataAccessGroups' => true]);
```
In this case, only the arguments with non-default values need to be specified. And, the order
doesn't matter, so the above export could also be specified as:
```php
$records = $project->exportRecordsAp(['exportDataAccessGroups' => true, 'format' => 'csv']);
```

exportReports
----------------------------
To use the exportReports method, you first need to define one or more reports in REDCap
for the project you are using.

For example, if you had previously defined a report in REDCap that had an ID of 18999,
you could export the records for that report in CSV format using the following:
```php
$records = $project->exportReports('18999', 'csv');
```