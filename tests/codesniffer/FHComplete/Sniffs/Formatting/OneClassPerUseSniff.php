<?php
/**
 * PHP Version 5
 *
 * FHComplete
 */

/**
 * Ensures the use contains only one class.
 *
 */
class FHComplete_Sniffs_Formatting_OneClassPerUseSniff implements PHP_CodeSniffer_Sniff
{

/**
 * Returns an array of tokens this test wants to listen for.
 *
 * @return array
 */
    public function register()
    {
        return array(T_USE);
    }

/**
 * Processes this test, when one of its tokens is encountered.
 *
 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
 * @param integer $stackPtr  The position of the current token in the stack passed in $tokens.
 * @return void
 */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $i = 2; // Ignore use word and whitespace
        $filename = $phpcsFile->getFilename();

        while (in_array($tokens[$stackPtr + $i]['code'], array(T_STRING, T_NS_SEPARATOR, T_WHITESPACE, T_AS))) {
            $i++;
        }

        if ($tokens[$stackPtr + $i]['code'] === T_COMMA) {
            $error = 'Only one class is allowed per use';
            $phpcsFile->addError($error, $stackPtr, 'OneClassPerUse', array());
        }
    }

}
