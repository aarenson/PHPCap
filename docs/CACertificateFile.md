CA Certificate File
=====================================================================

The CA (Certificate Authority) certificate file is needed so that PHPCap can verify that
the REDCap instance it is connecting to is actually the instance that
was specified.

In PHPCap, it is possible to set SSL verification to false, so that
a CA certificate file is not required, however, this is insecure, and
is not recommended. At most, setting SSL verification to false should
only be used for initial testing with non-critical data.






