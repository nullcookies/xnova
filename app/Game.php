<?php

namespace Xnova;

/**
 * @author AlexPro
 * @copyright 2008 - 2018 XNova Game Group
 * Telegram: @alexprowars, Skype: alexprowars, Email: alexprowars@gmail.com
 */

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Xnova\Models\Moneys;
use Xnova\Models\Users;

class Game
{
	public static function datezone ($format, $time = 0)
	{
		if ($time == 0)
			$time = time();

		if (Auth::check() && !is_null(Auth::user()->getUserOption('timezone')))
			$time += Auth::user()->getUserOption('timezone') * 1800;

		return date($format, $time);
	}

	public static function getSpeed ($type = '')
	{
		if ($type == 'fleet')
			return (int) Config::get('game.fleet_speed', 2500) / 2500;
		if ($type == 'mine')
			return (int) Config::get('game.resource_multiplier', 1);
		if ($type == 'build')
			return round((int) Config::get('game.game_speed', 2500) / 2500, 1);

		return 1;
	}

	public static function checkReferLink ()
	{
		if (Session::has('uid'))
			return;

		$id = (int) Request::server('QUERY_STRING', 0);

		if (!$id)
			return;

		$user = Users::find($id);

		if (!$user)
			return;

		$ip = sprintf("%u", ip2long(Request::ip()));

		$res = DB::selectOne("SELECT `id` FROM moneys where `ip` = '" . $ip . "' AND `time` > '" . (time() - 86400) . "'");

		if ($res)
			return;

		Moneys::query()->create([
			'user_id'		=> $user->id,
			'time'			=> time(),
			'ip'			=> $ip,
			'referer'		=> Request::server('HTTP_REFERER'),
			'user_agent'	=> Request::server('HTTP_USER_AGENT'),
		]);

		$user->links++;
		$user->refers++;
		$user->update();

		Session::put('ref', $user->id);
	}
}