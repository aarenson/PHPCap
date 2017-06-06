User Guide 4 - Extending PHPCap
=============================================

If you need additional functionality to what is provided by PHPCap, you
can extend it.

For example, if you wanted to have a method that returns the
title of the project, you could create a class
similar to the following that extended PHPCap's RedCapProject class:
```php
class MyRedCapProject extends IU\PHPCap\RedCapProject
{
    public function exportProjectTitle() {
       $projectInfo = $this->exportProjectInfo();
       $projectTitle = $projectInfo['project_title'];
       return $projectTitle;
    }
}
```
The new class would have all of the methods of RedCapProject as well as the
new method defined above, and you would then use this new class instead of
RedCapProject, for example:
```php
...
$project = new MyRedCapProject($apiUrl, $apiToken);
$projectTitle = $project->exportProjectTitle();
print("Project Title: $projectTitle\n");

$records = $project->exportRecords();
```

The RedCapProject class also has a connection property that gives you direct access to
the REDCap API. So within a method of your class extending RedCapProject, you
could use the following to send data to, and get the results from, your REDCap API:
```php
# Pass data to the REDCap API, and get back the result
$result = $this->connection->call($data);
```
This is useful for accessing methods provided by the REDCap API that
have not been implemented in PHPCap.