CA Certificate File
=====================================================================

The CA (Certificate Authority) certificate file is needed so that PHPCap can verify that
the REDCap instance it is connecting to is actually the instance that
was specified.

In PHPCap, it is possible to set SSL verification to false, so that
a CA certificate file is not required, however, this is insecure, and
is not recommended. At most, setting SSL verification to false should
only be used for initial testing with non-critical data.

It is possible that your system may already be set up to use a correct CA certificate file.
This can be tested by trying to access a project will SSL verification set to true, but with
no CA certificate file specified, for example:

    $sslVerify = true;
    $project = RedCapProject($apiUrl, $apiToken, $sslVerify);
    $project->exportProjectInfo();

If this works, then that would indicate that your system is already set up with a CA certificate
file. If it fails, and you get an error message about a security certificate, such as

> SSL certificate problem: self signed certificate in certificate chain

then your system is not already set up.



