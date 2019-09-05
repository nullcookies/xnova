<?php

namespace Xnova\Http\Controllers;

/**
 * @author AlexPro
 * @copyright 2008 - 2019 XNova Game Group
 * Telegram: @alexprowars, Skype: alexprowars, Email: alexprowars@gmail.com
 */

use Illuminate\Support\Facades\Request;
use Xnova\Controller;
use Xnova\Exceptions\ErrorException;
use Xnova\Exceptions\PageException;
use Xnova\Exceptions\SuccessException;
use Xnova\Models\Fleet;
use Xnova\Models;

class RocketController extends Controller
{
	private $loadPlanet = true;

	public function index ()
	{
		if (!Request::instance()->isMethod('post'))
			throw new PageException('Ошибка', '/galaxy/');

		$g = (int) Request::post('galaxy', 0);
		$s = (int) Request::post('system', 0);
		$p = (int) Request::post('planet', 0);

		if ($g <= 0 || $s <= 0 || $p <= 0)
			throw new ErrorException('Координаты не определены');

		$count = (int) Request::post('count', 1);
		$destroyType = Request::post('target', 'all');

		$distance = abs($s - $this->planet->system);
		$maxDistance = ($this->user->getTechLevel('impulse_motor') * 5) - 1;

		$targetPlanet = Models\Planets::findByCoords($g, $s, $p, 1);

		if ($this->planet->getBuildLevel('missile_facility') < 4)
			throw new ErrorException('Постройте ракетную шахту');
		elseif ($this->user->getTechLevel('impulse_motor') == 0)
			throw new ErrorException('Необходима технология "Импульсный двигатель"');
		elseif ($distance >= $maxDistance || $g != $this->planet->galaxy)
			throw new ErrorException('Превышена дистанция ракетной атаки');
		elseif (!$targetPlanet)
			throw new ErrorException('Планета не найдена');
		elseif ($count > $this->planet->getUnitCount('interplanetary_misil'))
			throw new ErrorException('У вас нет такого кол-ва ракет');
		elseif ((!is_numeric($destroyType) && $destroyType != "all") OR ($destroyType < 0 && $destroyType > 7 && $destroyType != "all"))
			throw new ErrorException('Не найдена цель');

		if ($destroyType == 'all')
			$destroyType = 0;
		else
			$destroyType = (int) $destroyType;

		/** @var Models\Users $targetUser */
		$targetUser = Models\Users::query()->find($targetPlanet->id_owner, ['id', 'vacation']);

		if (!$targetUser)
			throw new ErrorException('Игрока не существует');

		if ($targetUser->isVacation())
			throw new ErrorException('Игрок в режиме отпуска');

		if ($this->user->isVacation())
			throw new ErrorException('Вы в режиме отпуска');

		$time = 30 + (60 * $distance);

		/** @var Fleet $fleet */
		$fleet = Fleet::query()->create([
			'owner' 			=> $this->user->id,
			'owner_name' 		=> $this->planet->name,
			'mission' 			=> 20,
			'fleet_array' 		=> [['id' => 503, 'count' => $count, 'target' => $destroyType]],
			'start_time' 		=> time() + $time,
			'start_galaxy' 		=> $this->planet->galaxy,
			'start_system' 		=> $this->planet->system,
			'start_planet' 		=> $this->planet->planet,
			'start_type' 		=> 1,
			'end_time' 			=> 0,
			'end_galaxy' 		=> $g,
			'end_system' 		=> $s,
			'end_planet' 		=> $p,
			'end_type' 			=> 1,
			'target_owner' 		=> $targetPlanet->id_owner,
			'target_owner_name' => $targetPlanet->name,
			'create_time' 		=> time(),
			'update_time' 		=> time() + $time,
		]);

		if ($fleet->id > 0)
		{
			$this->planet->setUnit('interplanetary_misil', -$count, true);
			$this->planet->update();
		}

		throw new SuccessException('<b>'.$count.'</b> межпланетные ракеты запущены для атаки удалённой планеты!');
	}
}