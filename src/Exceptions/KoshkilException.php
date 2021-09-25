<?php
namespace Koshkil\Core\Exceptions;

class KoshkilException {
	public $details = '';

	public function __construct($msg, $errno=0, $details='') {
		$this->details = $details;
		parent::__construct($msg, $errno);
	}

	static public function getErrorMessage($e,$advanced=false) {
		$msg = "\n".'<exception><pre style="border-left:1px solid #ccc;padding-left:10px;margin:5px 0px">';
		$msg .= $e->getMessage();
    	return $msg.'</pre></exception>';
	}
}
