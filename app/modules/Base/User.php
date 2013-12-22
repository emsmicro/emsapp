<?php


class User extends Model
{
	public static function find($username)
	{
		$row = $this->CONN->table('users')
			->where('username', $username)
			->fetch();

		return ($row) ? $row : NULL;
	}
}
