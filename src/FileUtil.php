<?php
/**
 * This file contains file utilities.
 */
namespace IU\PHPCap;

/**
 * File utility class for dealing with files.
 */
class FileUtil
{

    /**
     * Reads the contents of the specified file and returns it as a string.
     *
     * @param string $filename the name of the file that is to be read.
     * @throws PhpCapException if an error occurs while trying to read the file.
     * @return string the contents of the specified file.
     */
    public static function fileToString($filename)
    {
        if (!file_exists($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" could not be found.',
                PhpCapException::INPUT_FILE_NOT_FOUND
            );
        } elseif (!is_readable($filename)) {
            throw new PhpCapException(
                'The input file "'.$filename.'" was unreadable.',
                PhpCapException::INPUT_FILE_UNREADABLE
            );
        }
        
        $contents = file_get_contents($filename);

        if ($contents === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                throw new PhpCapException(
                    'An error occurred in input file "'.$filename.'": '.$errorMessage,
                    PhpCapException::INPUT_FILE_ERROR
                );
            } else {
                throw new PhpCapException(
                    'An error occurred in input file "'.$filename.'"',
                    PhpCapException::INPUT_FILE_ERROR
                );
            }
        }
        
        return $contents;
    }
 
    /**
     * Writes the specified string to the specified file.
     *
     * @param string $string the string to write to the file.
     * @param string $filename the name of the file to write the string.
     * @param boolean $append if true, the file is appended if it already exists. If false,
     *        the file is created if it doesn't exist, and overwritten if it does.
     * @throws PhpCapException if an error occurs.
     * @return mixed false on failure, and the number of bytes written on success.
     */
    public static function writeStringToFile($string, $filename, $append = false)
    {
        $result = false;
        if ($append === true) {
            $result = file_put_contents($filename, $string, FILE_APPEND);
        } else {
            $result = file_put_contents($filename, $string);
        }
        
        if ($result === false) {
            $error = error_get_last();
            $errorMessage = null;
            if ($error != null && array_key_exists('message', $error)) {
                $errorMessage = $error['message'];
            }
            
            if (isset($errorMessage)) {
                throw new PhpCapException(
                    'An error occurred in output file "'.$filename.'": '.$errorMessage,
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            } else {
                throw new PhpCapException(
                    'An error occurred in output file "'.$filename.'"',
                    PhpCapException::OUTPUT_FILE_ERROR
                );
            }
        }
            
        return $result;
    }
    
    /**
     * Appends the specified string to the specified file.
     *
     * @param string $string the string to append.
     * @param string $filename the name of the file that is appended.
     * @return mixed false on failure, and the number of bytes appended on success.
     */
    public static function appendStringToFile($string, $filename)
    {
        $result = self::writeStringToFile($string, $filename, true);
        return $result;
    }
}
