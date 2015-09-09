<?php

/**
 * Log messages to display later.
 */
class DDLog
{
	/**
	 * Stored messages.
	 * @var array
	 */
	protected $log = array();
	
	/**
	 * Add a message to the log.
	 * @param string $msg Message to add
	 */
	public function add($msg)
	{
		$this->log[] = $msg;
	}
	
	/**
	 * Write the entire log at once.
	 */
	public function write()
	{		
		if(count($this->log)){
			foreach($this->log as $msg){
				DDOutput::error($msg);
			}
		}else{
			DDOutput::line("No errors found.");
		}
	}
}