<?php
namespace App;

use Phalcon\Db\Adapter\Pdo\Mysql;

class Database extends Mysql
{
	public function extractResult ($result, $field = false)
	{
		$data = array();

		if (!$field)
		{
			while ($res = $result->fetch())
				$data[] = $res;
		}
		else
		{
			while ($res = $result->fetch())
				$data[$res[$field]] = $res;
		}

		return $data;
	}
}

?>