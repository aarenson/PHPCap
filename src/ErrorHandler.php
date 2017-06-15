<?php

namespace IU\PHPCap;

class ErrorHandler implements ErrorHandlerInterface
{
    public function throwException(
        $message,
        $code,
        $connectionErrorNumber = null,
        $httpStatusCode = null,
        $previousException = null
    ) {
        /* -------------------------------
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $trace[0];
        $callingFunction = $caller['function'];
        $callingFile     = $caller['file'];
        $callingLine     = $caller['line'];
        print "File ".$callingFile.", line ".$callingLine.": ".$message."\n";
        #print_r($trace);
        *****/
        
        throw new PhpCapException(
            $message,
            $code,
            $connectionErrorNumber,
            $httpStatusCode,
            $previousException
        );
    }
}
