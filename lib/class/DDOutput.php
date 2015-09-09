<?php

/**
 * Output handling.
 */
class DDOutput
{
	/**
	 * Enables or disables debug output (verbose mode).
	 * @var boolean
	 */
	protected static $debug = false;
	
	/**
	 * Enables or disables silent mode (does not output anything).
	 * @var boolean
	 */
	protected static $silent = false;
	
	/**
	 * File handler for fopen and fwrite.
	 * @var resource
	 */
	protected static $handler = null;
	
	/**
	 * Output filename. Defaults to php://stdout.
	 * @var string
	 */
	protected static $output = "php://stdout";
	
	/**
	 * Enable or disable debug output.
	 * @param boolean $debug Enable or disable debug
	 */
	public static function setDebug($debug)
	{
		self::$debug = $debug;
	}
	
	/**
	 * Enable or disable silent mode.
	 * @param boolean $silent Enable or disable silent output
	 */
	public static function setSilent($silent)
	{
		self::$silent = $silent;
	}
	
	/**
	 * Output debug message.
	 * @param string $msg Message to output
	 */
	public static function debug($msg)
	{
		if(self::$debug){
			self::write("DBG [" . date("d/m/Y H:i:s") . "] " . $msg . "\r\n");
		}
	}
	
	/**
	 * Output error message.
	 * @param string $msg Message to output
	 */
	public static function error($msg)
	{
		self::write("ERR [" . date("d/m/Y H:i:s") . "] " . $msg . "\r\n");
	}
	
	/**
	 * Output standard log message.
	 * @param string $msg Message to output
	 */
	public static function line($msg)
	{
		fwrite(self::$handler, $msg . "\r\n");
	}
	
	/**
	 * Write a message to the chosen output.
	 * @param string $msg Message to output
	 */
	protected static function write($msg)
	{
		if(!self::$silent){
			fwrite(self::$handler, $msg);
		}
	}
	
	/**
	 * Initialize output for messages.
	 * @param string $output Filename. Defaults to php://stdout
	 */
	public static function setOutput($output = "php://stdout")
	{
		self::$output = $output;
		
		if(!self::$silent){
			self::$handler = fopen($output, "w+");
		}
	}
}

?>