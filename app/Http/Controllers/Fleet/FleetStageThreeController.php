<?php

namespace Xnova\Http\Controllers\Fleet;

/**
 * @author AlexPro
 * @copyright 2008 - 2018 XNova Game Group
 * Telegram: @alexprowars, Skype: alexprowars, Email: alexprowars@gmail.com
 */

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Xnova\Controller;
use Xnova\Exceptions\ErrorException;
use Xnova\Exceptions\PageException;
use Xnova\Fleet;
use Xnova\Format;
use Xnova\Game;
use Xnova\Models;
use Xnova\Planet;
use Xnova\Vars;

class FleetStageThreeController extends Controller
{
	public function index ()
	{
		if ($this->user->vacation > 0)
			throw new PageException("Нет доступа!");

		$galaxy = (int) Request::post('galaxy', 0);
		$system = (int) Request::post('system', 0);
		$planet = (int) Request::post('planet', 0);
		$planet_type = (int) Request::post('planet_type', 0);

		$fleetMission = (int) Request::post('mission', 0);
		$expTime = (int) Request::post('expeditiontime', 0);

		$fleetarray = json_decode(base64_decode(str_rot13(Request::post('fleet', ''))), true);

		if (!$fleetMission)
			throw new ErrorException("<span class=\"error\"><b>Не выбрана миссия!</b></span>");

		if (($fleetMission == 1 || $fleetMission == 6 || $fleetMission == 9 || $fleetMission == 2) && Config::get('game.disableAttacks', 0) > 0 && time() < Config::get('game.disableAttacks', 0))
			throw new PageException("<span class=\"error\"><b>Посылать флот в атаку временно запрещено.<br>Дата включения атак " . Game::datezone("d.m.Y H ч. i мин.", Config::get('game.disableAttacks', 0)) . "</b></span>", '/fleet/');

		$allianceId = (int) Request::post('alliance', 0);

		$fleet_group_mr = 0;

		if ($allianceId > 0)
		{
			if ($fleetMission == 2)
			{
				$aks_tr = DB::table('aks')
					->select('aks.*')
					->join('aks_user', 'aks_user.aks_id', '=', 'aks.id')
					->where('aks_user.user_id', $this->user->id)
					->where('aks_user.aks_id', $allianceId)
					->first();

				if ($aks_tr)
				{
					if ($aks_tr->galaxy == $galaxy && $aks_tr->system == $system && $aks_tr->planet == $planet && $aks_tr->planet_type == $planet_type)
						$fleet_group_mr = $allianceId;
				}
			}
		}

		if (($allianceId == 0 || $fleet_group_mr == 0) && ($fleetMission == 2))
			$fleetMission = 1;

		$protection = (int) Config::get('game.noobprotection') > 0;

		if (!is_array($fleetarray))
			throw new PageException("<span class=\"error\"><b>Ошибка в передаче параметров!</b></span>", "/fleet/");

		foreach ($fleetarray as $Ship => $Count)
		{
			if ($Count > $this->planet->getUnitCount($Ship))
				throw new PageException("<span class=\"error\"><b>Недостаточно флота для отправки на планете!</b></span>", "/fleet/");
		}

		if ($planet_type != 1 && $planet_type != 2 && $planet_type != 3 && $planet_type != 5)
			throw new PageException("<span class=\"error\"><b>Неизвестный тип планеты!</b></span>", "/fleet/");

		if ($this->planet->galaxy == $galaxy && $this->planet->system == $system && $this->planet->planet == $planet && $this->planet->planet_type == $planet_type)
			throw new PageException("<span class=\"error\"><b>Невозможно отправить флот на эту же планету!</b></span>", "/fleet/");

		/** @var Planet $targetPlanet */
		$targetPlanet = Models\Planets::query()
			->where('galaxy', $galaxy)
			->where('system', $system)
			->where('planet', $planet)
			->where(function (Builder $query) use ($fleetMission, $planet_type)
			{
				if ($fleetMission == 8)
				{
					$query->where('planet_type', 1)
						->orWhere('planet_type', 5);
				}
				else
					$query->where('planet_type', $planet_type);
			})->first();

		if ($fleetMission != 15)
		{
			if (!$targetPlanet && $fleetMission != 7 && $fleetMission != 10)
				throw new PageException("<span class=\"error\"><b>Данной планеты не существует!</b> - [".$galaxy.":".$system.":".$planet."]</span>", "/fleet/");
			elseif ($fleetMission == 9 && !$targetPlanet)
				throw new PageException("<span class=\"error\"><b>Данной планеты не существует!</b> - [".$galaxy.":".$system.":".$planet."]</span>", "/fleet/");
			elseif (!$targetPlanet && $fleetMission == 7 && $planet_type != 1)
				throw new PageException("<span class=\"error\"><b>Колонизировать можно только планету!</b></span>", "/fleet/");
		}
		else
		{
			if ($this->user->getTechLevel('expedition') >= 1)
			{
				$ExpeditionEnCours = Models\Fleet::query()
					->where('owner', $this->user->id)
					->where('mission', 15)
					->count();

				$MaxExpedition = 1 + floor($this->user->getTechLevel('expedition') / 3);
			}
			else
			{
				$MaxExpedition = 0;
				$ExpeditionEnCours = 0;
			}

			if ($this->user->getTechLevel('expedition') == 0)
				throw new PageException("<span class=\"error\"><b>Вами не изучена \"Экспедиционная технология\"!</b></span>", "/fleet/");
			elseif ($ExpeditionEnCours >= $MaxExpedition)
				throw new PageException("<span class=\"error\"><b>Вы уже отправили максимальное количество экспедиций!</b></span>", "/fleet/");

			if ($expTime <= 0 || $expTime > (round($this->user->getTechLevel('expedition') / 2) + 1))
				throw new PageException("<span class=\"error\"><b>Вы не можете столько времени летать в экспедиции!</b></span>", "/fleet/");
		}

		if (!$targetPlanet)
		{
			$YourPlanet = false;
			$UsedPlanet = false;
		}
		elseif ($targetPlanet->id_owner == $this->user->id || ($this->user->ally_id > 0 && $targetPlanet->id_ally == $this->user->ally_id))
		{
			$YourPlanet = true;
			$UsedPlanet = true;
		}
		else
		{
			$YourPlanet = false;
			$UsedPlanet = true;
		}

		if ($fleetMission == 4 && ($targetPlanet->id_owner == 1 || $this->user->isAdmin()))
			$YourPlanet = true;

		$missiontype = Fleet::getFleetMissions($fleetarray, [$galaxy, $system, $planet, $planet_type], $YourPlanet, $UsedPlanet, ($fleet_group_mr > 0));

		if (!in_array($fleetMission, $missiontype))
			throw new ErrorException("<span class=\"error\"><b>Миссия неизвестна!</b></span>");

		if ($fleetMission == 8 && $targetPlanet->debris_metal == 0 && $targetPlanet->debris_crystal == 0)
		{
			if ($targetPlanet->debris_metal == 0 && $targetPlanet->debris_crystal == 0)
				throw new PageException("<span class=\"error\"><b>Нет обломков для сбора.</b></span>", "/fleet/");
		}

		if ($targetPlanet)
		{
			$targerUser = Models\Users::query()->find($targetPlanet->id_owner);

			if (!$targerUser)
				throw new PageException("<span class=\"error\"><b>Неизвестная ошибка #FLTNFU".$targetPlanet->id_owner."</b></span>", "/fleet/");
		}
		else
			$targerUser = $this->user->toArray();

		if (($targerUser['authlevel'] > 0 && $this->user->authlevel == 0) && ($fleetMission != 4 && $fleetMission != 3))
			throw new PageException("<span class=\"error\"><b>На этого игрока запрещено нападать</b></span>", "/fleet/");

		$diplomacy = false;

		if ($this->user->ally_id != 0 && $targerUser['ally_id'] != 0 && $fleetMission == 1)
		{
			$diplomacy = Models\AllianceDiplomacy::query()
				->where('a_id', $targerUser['ally_id'])
				->where('d_id', $this->user->ally_id)
				->where('status', 1)
				->where('type', '<', 3)
				->first();

			if ($diplomacy)
				throw new PageException("<span class=\"error\"><b>Заключён мир или перемирие с альянсом атакуемого игрока.</b></span>", "/fleet/");
		}

		if ($protection && $targetPlanet && in_array($fleetMission, [1, 2, 5, 6, 9]) && $this->user->authlevel < 2)
		{
			$protectionPoints = (int) Config::get('game.noobprotectionPoints');
			$protectionFactor = (int) Config::get('game.noobprotectionFactor');

			if ($protectionPoints <= 0)
				$protection = false;

			if ($targerUser['onlinetime'] < (time() - 86400 * 7) || $targerUser['banned'] > 0)
				$protection = false;

			if ($fleetMission == 5 && $targerUser['ally_id'] == $this->user->ally_id)
				$protection = false;

			if ($protection)
			{
				$MyPoints = Models\Statpoints::query()
					->select('total_points')
					->where('stat_type', 1)
					->where('stat_code', 1)
					->where('id_owner', $this->user->id)
					->value('total_points') ?? 0;

				$HePoints = Models\Statpoints::query()
					->select('total_points')
					->where('stat_type', 1)
					->where('stat_code', 1)
					->where('id_owner', $targerUser['id'])
					->value('total_points') ?? 0;

				if ($HePoints < $protectionPoints)
					throw new PageException("<span class=\"success\"><b>Игрок находится под защитой новичков!</b></span>", "/fleet/");

				if ($protectionFactor && $MyPoints > $HePoints * $protectionFactor)
					throw new PageException("<span class=\"success\"><b>Этот игрок слишком слабый для вас!</b></span>", "/fleet/");
			}
		}

		if ($targerUser['vacation'] > 0 && $fleetMission != 8 && !$this->user->isAdmin())
			throw new PageException("<span class=\"success\"><b>Игрок в режиме отпуска!</b></span>", "/fleet/");

		$flyingFleets = Models\Fleet::query()->where('owner', $this->user->id)->count();

		$maxFleets = $this->user->getTechLevel('computer') + 1;

		if ($this->user->rpg_admiral > time())
			$maxFleets += 2;

		if ($maxFleets <= $flyingFleets)
			throw new PageException("Все слоты флота заняты. Изучите компьютерную технологию для увеличения кол-ва летящего флота.", "/fleet/");

		$resources = Request::post('resource');
		$resources = array_map('intval', $resources);

		if (array_sum($resources) < 1 && $fleetMission == 3)
			throw new ErrorException("<span class=\"success\"><b>Нет сырья для транспорта!</b></span>");

		if ($fleetMission != 15)
		{
			if (!$targetPlanet && $fleetMission < 7)
				throw new ErrorException("<span class=\"error\"><b>Планеты не существует!</b></span>");

			if ($targetPlanet && ($fleetMission == 7 || $fleetMission == 10))
				throw new ErrorException("<span class=\"error\"><b>Место занято</b></span>");

			if ($targetPlanet && $targetPlanet->getBuildLevel('ally_deposit') == 0 && $targerUser['id'] != $this->user->id && $fleetMission == 5)
				throw new ErrorException("<span class=\"error\"><b>На планете нет склада альянса!</b></span>");

			if ($fleetMission == 5)
			{
				$friend = Models\Buddy::query()
					->where(function (Builder $query) use ($targerUser) {
						$query->where(function (Builder $query) use ($targerUser) {
							$query->where('sender', $this->user->id)
								->where('owner', $targerUser['id']);
						})
						->orWhere(function (Builder $query) use ($targerUser) {
							$query->where('owner', $this->user->id)
								->where('sender', $targerUser['id']);
						});
					})
					->where('active', 1)->first();

				if ($targerUser['ally_id'] != $this->user->ally_id && !$friend && (!$diplomacy || ($diplomacy && $diplomacy['type'] != 2)))
					throw new ErrorException("<span class=\"error\"><b>Нельзя охранять вражеские планеты!</b></span>");
			}

			if ($targetPlanet && $targetPlanet->id_owner == $this->user->id && ($fleetMission == 1 || $fleetMission == 2))
				throw new ErrorException("<span class=\"error\"><b>Невозможно атаковать самого себя!</b></span>");

			if ($targetPlanet && $targetPlanet->id_owner == $this->user->id && $fleetMission == 6)
				throw new ErrorException("<span class=\"error\"><b>Невозможно шпионить самого себя!</b></span>");

			if (!$YourPlanet && $fleetMission == 4)
				throw new ErrorException("<span class=\"error\"><b>Выполнение данной миссии невозможно!</b></span>");
		}

		$speedPossible = [10, 9, 8, 7, 6, 5, 4, 3, 2, 1];

		$maxFleetSpeed 		= min(Fleet::GetFleetMaxSpeed($fleetarray, 0, $this->user));
		$fleetSpeedFactor 	= Request::post('speed', 10);
		$gameFleetSpeed 	= Game::getSpeed('fleet');

		if (!in_array($fleetSpeedFactor, $speedPossible))
			throw new PageException("<span class=\"error\"><b>Читеришь со скоростью?</b></span>", '/fleet/');

		if (!$planet_type)
			throw new PageException("<span class=\"error\"><b>Ошибочный тип планеты!</b></span>", '/fleet/');

		$errorlist = "";

		if (!$galaxy || $galaxy > Config::get('game.maxGalaxyInWorld') || $galaxy < 1)
			$errorlist .= __('fleet.fl_limit_galaxy');

		if (!$system || $system > Config::get('game.maxSystemInGalaxy') || $system < 1)
			$errorlist .= __('fleet.fl_limit_system');

		if (!$planet || $planet > (Config::get('game.maxPlanetInSystem') + 1) || $planet < 1)
			$errorlist .= __('fleet.fl_limit_planet');

		if ($errorlist != '')
			throw new PageException("<span class=\"error\">" . $errorlist . "</span>", '/fleet/');

		if (!isset($fleetarray))
			throw new PageException("<span class=\"error\"><b>" . __('fleet.fl_no_fleetarray') . "</b></span>", '/fleet/');

		$fleet = new Models\Fleet();

		$distance 		= Fleet::GetTargetDistance($this->planet->galaxy, $galaxy, $this->planet->system, $system, $this->planet->planet, $planet);
		$duration 		= Fleet::GetMissionDuration($fleetSpeedFactor, $maxFleetSpeed, $distance, $gameFleetSpeed);
		$consumption 	= Fleet::GetFleetConsumption($fleetarray, $gameFleetSpeed, $duration, $distance, $this->user);

		$fleet_group_time = 0;

		if ($fleet_group_mr > 0)
		{
			// Вычисляем время самого медленного флота в совместной атаке
			$flet = Models\Fleet::query()->where('group_id', $fleet_group_mr)->get(['id', 'start_time', 'end_time']);

			$fleet_group_time = $duration + time();
			$arrr = [];

			/** @var Models\Fleet $flt */
			foreach ($flet as $i => $flt)
			{
				if ($flt->start_time > $fleet_group_time)
					$fleet_group_time = $flt->start_time;

				$arrr[$i]['id'] = $flt->id;
				$arrr[$i]['start'] = $flt->start_time;
				$arrr[$i]['end'] = $flt->end_time;
			}
		}

		if ($fleet_group_mr > 0)
			$fleet->start_time = $fleet_group_time;
		else
			$fleet->start_time = $duration + time();

		if ($fleetMission == 15)
		{
			$StayDuration = $expTime * 3600;
			$StayTime = $fleet->start_time + $StayDuration;
		}
		else
		{
			$StayDuration = 0;
			$StayTime = 0;
		}

		$FleetStorage = 0;
		$fleet_array = [];

		foreach ($fleetarray as $Ship => $Count)
		{
			$fleetData = Vars::getUnitData($Ship);

			$Count = (int) $Count;
			$FleetStorage += $fleetData['capacity'] * $Count;

			$fleet_array[] = [
				'id' => (int) $Ship,
				'count' => $Count,
			];

			$this->planet->setUnit($Ship, -$Count, true);
		}

		$FleetStorage -= $consumption;
		$StorageNeeded = 0;

		if ($resources['metal'] < 1)
			$TransMetal = 0;
		else
		{
			$TransMetal = $resources['metal'];
			$StorageNeeded += $TransMetal;
		}

		if ($resources['crystal'] < 1)
			$TransCrystal = 0;
		else
		{
			$TransCrystal = $resources['crystal'];
			$StorageNeeded += $TransCrystal;
		}

		if ($resources['deuterium'] < 1)
			$TransDeuterium = 0;
		else
		{
			$TransDeuterium = $resources['deuterium'];
			$StorageNeeded += $TransDeuterium;
		}

		$TotalFleetCons = 0;

		if ($fleetMission == 5)
		{
			$holdTime = (int) Request::post('holdingtime', 0);

			if (!in_array($holdTime, [0, 1, 2, 4, 8, 16, 32]))
				$holdTime = 0;

			$FleetStayConsumption = Fleet::GetFleetStay($fleetarray);

			if ($this->user->rpg_meta > time())
				$FleetStayConsumption = ceil($FleetStayConsumption * 0.9);

			$FleetStayAll = $FleetStayConsumption * $holdTime;

			if ($FleetStayAll >= ($this->planet->deuterium - $TransDeuterium))
				$TotalFleetCons = $this->planet->deuterium - $TransDeuterium;
			else
				$TotalFleetCons = $FleetStayAll;

			if ($FleetStorage < $TotalFleetCons)
				$TotalFleetCons = $FleetStorage;

			$FleetStayTime = round(($TotalFleetCons / $FleetStayConsumption) * 3600);

			$StayDuration = $FleetStayTime;
			$StayTime = $fleet->start_time + $FleetStayTime;
		}

		if ($fleet_group_mr > 0)
			$fleet->end_time = $StayDuration + $duration + $fleet_group_time;
		else
			$fleet->end_time = $StayDuration + (2 * $duration) + time();

		$StockMetal 	= $this->planet->metal;
		$StockCrystal 	= $this->planet->crystal;
		$StockDeuterium = $this->planet->deuterium - ($consumption + $TotalFleetCons);

		$StockOk = ($StockMetal >= $TransMetal && $StockCrystal >= $TransCrystal && $StockDeuterium >= $TransDeuterium);

		if (!$StockOk && (!$targetPlanet || $targetPlanet->id_owner != 1))
			throw new ErrorException("<span class=\"error\"><b>" . __('fleet.fl_noressources') . Format::number($consumption) . "</b></span>");

		if ($StorageNeeded > $FleetStorage && !$this->user->isAdmin())
			throw new ErrorException("<span class=\"error\"><b>" . __('fleet.fl_nostoragespa') . Format::number($StorageNeeded - $FleetStorage) . "</b></span>");

		// Баш контроль
		if ($fleetMission == 1)
		{
			$night_time = mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));

			$log = DB::selectOne("SELECT id, kolvo FROM logs WHERE s_id = '".$this->user->id."' AND mission = 1 AND e_galaxy = " . $targetPlanet->galaxy . " AND e_system = " . $targetPlanet->system . " AND e_planet = " . $targetPlanet->planet . " AND time > " . $night_time . "");

			if (!$this->user->isAdmin() && isset($log['kolvo']) && $log['kolvo'] > 2 && (($diplomacy && $diplomacy['type'] != 3) || !$diplomacy))
				throw new PageException("<span class=\"error\"><b>Баш-контроль. Лимит ваших нападений на планету исчерпан.</b></span>", "/fleet/");

			if (isset($log['kolvo']))
				DB::table('logs')->where('id', $log['id'])->increment('kolvo');
			else
			{
				DB::table('logs')->insert([
					'mission' => 1,
					'time' => time(),
					'kolvo' => 1,
					's_id' => $this->user->id,
					's_galaxy' => $this->planet->galaxy,
					's_system' => $this->planet->system,
					's_planet' => $this->planet->planet,
					'e_id' => $targetPlanet->id_owner,
					'e_galaxy' => $targetPlanet->galaxy,
					'e_system' => $targetPlanet->system,
					'e_planet' => $targetPlanet->planet,
				]);
			}
		}
		//

		// Увод флота
		//$fleets_num = $this->db->query("SELECT id FROM fleets WHERE mission = '1' AND end_galaxy = ".$this->planet->data['galaxy']." AND end_system = ".$this->planet->data['system']." AND end_planet = ".$this->planet->data['planet']." AND end_type = ".$this->planet->data['planet_type']." AND start_time < ".(time() + 5)."");

		//if (db::num_rows($fleets_num) > 0)
		//		message ("<span class=\"error\"><b>Ваш флот не может взлететь из-за находящегося поблизости от орбиты планеты атакующего флота.</b></span>", 'Ошибка', "fleet." . $phpEx, 2);
		//

		if ($fleet_group_mr > 0 && $fleet_group_time > 0 && isset($arrr))
		{
			foreach ($arrr AS $id => $row)
			{
				$end = $fleet_group_time + $row['end'] - $row['start'];

				Models\Fleet::query()
					->where('id', $row['id'])
					->update([
						'start_time' => $fleet_group_time,
						'end_time' => $end,
						'update_time' => $fleet_group_time
					]);
			}
		}

		/*if (($fleetMission == 1 || $fleetMission == 2 || $fleetMission == 3) && $targerUser['id'] != $this->user->id && !$this->user->isAdmin())
		{
			$check = $this->db->fetchColumn("SELECT COUNT(*) as num FROM log_ip WHERE id = ".$targerUser['id']." AND time > ".(time() - 86400 * 3)." AND ip IN (SELECT ip FROM game_log_ip WHERE id = ".$this->user->id." AND time > ".(time() - 86400 * 3).")");

			if ($check > 0 || $targerUser['ip'] == $this->user->ip)
				throw new RedirectException("<span class=\"error\"><b>Вы не можете посылать флот с миссией \"Транспорт\" и \"Атака\" к игрокам, с которыми были пересечения по IP адресу.</b></span>", 'Ошибка', "/fleet/", 5);
		}*/

		if ($fleetMission == 3 && $targerUser['id'] != $this->user->id && !$this->user->isAdmin())
		{
			if ($targerUser['onlinetime'] < (time() - 86400 * 7))
				throw new ErrorException("<span class=\"error\"><b>Вы не можете посылать флот с миссией \"Транспорт\" к неактивному игроку.</b></span>");

			$cnt = DB::table('log_transfers')
				->where('user_id', $this->user->id)
				->where('target_id', $targerUser['id'])
				->where('time', '>', time() - 86400 * 7)
				->count();

			if ($cnt >= 3)
				throw new ErrorException("<span class=\"error\"><b>Вы не можете посылать флот с миссией \"Транспорт\" другому игроку чаще 3х раз в неделю.</b></span>");

			$cnt = DB::table('log_transfers')
				->where('user_id', $this->user->id)
				->where('target_id', $targerUser['id'])
				->where('time', '>', time() - 86400 * 1)
				->count();

			if ($cnt > 0)
				throw new ErrorException("<span class=\"error\"><b>Вы не можете посылать флот с миссией \"Транспорт\" другому игроку чаще одного раза в день.</b></span>");

			//$equiv = $TransMetal + $TransCrystal * 2 + $TransDeuterium * 4;

			//if ($equiv > 15000000)
			//	throw new RedirectException("<span class=\"error\"><b>Вы не можете посылать флот с миссией \"Транспорт\" другому игроку с количеством ресурсов большим чем 15кк в эквиваленте металла.</b></span>", 'Ошибка', "/fleet/", 5);

			DB::table('log_transfers')->insert([
				'time' => time(),
				'user_id' => $this->user->id,
				'data' => json_encode([
					'planet' => [
						'galaxy' => $this->planet->galaxy,
						'system' => $this->planet->system,
						'planet' => $this->planet->planet,
						'type' => $this->planet->planet_type,
					],
					'target' => [
						'galaxy' => $galaxy,
						'system' => $system,
						'planet' => $planet,
						'type' => $planet_type,
					],
					'fleet' => $fleet_array,
					'resources' => [
						'metal' => $TransMetal,
						'crystal' => $TransCrystal,
						'deuterium' => $TransDeuterium,
					],
				]),
				'target_id' => $targetPlanet->id_owner,
			]);

			$str_error = "Информация о передаче ресурсов добавлена в журнал оператора.<br>";
		}

		if (false && $targetPlanet && $targetPlanet->id_owner == 1)
		{
			$fleet->start_time = time() + 30;
			$fleet->end_time = time() + 60;

			$consumption = 0;
		}

		if (false && $this->user->isAdmin() && $fleetMission != 6)
		{
			$fleet->start_time 	= time() + 15;
			$fleet->end_time 	= time() + 30;

			if ($StayTime)
				$StayTime = $fleet->start_time + 5;

			$consumption = 0;
		}

		$tutorial = DB::selectOne("SELECT id, quest_id FROM users_quests WHERE user_id = ".$this->user->getId()." AND finish = '0' AND stage = 0");

		if ($tutorial)
		{
			$quest = __('tutorial.tutorial', $tutorial->quest_id);

			foreach ($quest['TASK'] AS $taskKey => $taskVal)
			{
				if ($taskKey == 'FLEET_MISSION' && $taskVal == $fleetMission)
					Models\UsersQuest::query()->where('id', $tutorial->id)->update(['stage' => 1]);
			}
		}

		if ($fleetMission == 1)
		{
			$raunds = Request::post('raunds', 6);
			$raunds = max(min(10, $raunds), 6);
		}
		else
			$raunds = 0;

		$fleet->fill([
			'owner' 				=> $this->user->id,
			'owner_name' 			=> $this->planet->name,
			'mission' 				=> $fleetMission,
			'fleet_array' 			=> $fleet_array,
			'start_galaxy' 			=> $this->planet->galaxy,
			'start_system' 			=> $this->planet->system,
			'start_planet' 			=> $this->planet->planet,
			'start_type' 			=> $this->planet->planet_type,
			'end_stay' 				=> $StayTime,
			'end_galaxy' 			=> $galaxy,
			'end_system' 			=> $system,
			'end_planet' 			=> $planet,
			'end_type' 				=> $planet_type,
			'resource_metal' 		=> $TransMetal,
			'resource_crystal' 		=> $TransCrystal,
			'resource_deuterium' 	=> $TransDeuterium,
			'target_owner' 			=> ($targetPlanet ? $targetPlanet->id_owner : 0),
			'target_owner_name' 	=> ($targetPlanet ? $targetPlanet->name : ''),
			'group_id' 				=> $fleet_group_mr,
			'raunds' 				=> $raunds,
			'create_time' 			=> time(),
			'update_time' 			=> $fleet->start_time,
		]);
		$fleet->save();

		$this->planet->metal 		-= $TransMetal;
		$this->planet->crystal 		-= $TransCrystal;
		$this->planet->deuterium 	-= $TransDeuterium + $consumption + $TotalFleetCons;

		$this->planet->update();

		$html  = "<table class=\"table\">";
		$html .= "<tr>";
		$html .= "<td class=\"c\" colspan=\"2\"><span class=\"success\">" . ((isset($str_error)) ? $str_error : __('fleet.fl_fleet_send')) . "</span></td>";
		$html .= "</tr><tr>";
		$html .= "<th width='50%'>" . __('fleet.fl_mission') . "</th>";
		$html .= "<th>" . __('main.type_mission.'.$fleetMission) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_dist') . "</th>";
		$html .= "<th>" . Format::number($distance) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_speed') . "</th>";
		$html .= "<th>" . Format::number($maxFleetSpeed) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_deute_need') . "</th>";
		$html .= "<th>" . Format::number($consumption) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_from') . "</th>";
		$html .= "<th>" . $this->planet->galaxy . ":" . $this->planet->system . ":" . $this->planet->planet . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_dest') . "</th>";
		$html .= "<th>" . $galaxy . ":" . $system . ":" . $planet . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_time_go') . "</th>";
		$html .= "<th>" . Game::datezone("d H:i:s", $fleet->start_time) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<th>" . __('fleet.fl_time_back') . "</th>";
		$html .= "<th>" . Game::datezone("d H:i:s", $fleet->end_time) . "</th>";
		$html .= "</tr><tr>";
		$html .= "<td class=\"c\" colspan=\"2\">" . __('fleet.fl_title') . "</td>";

		foreach ($fleetarray as $Ship => $Count)
		{
			$html .= "</tr><tr>";
			$html .= "<th>" . __('main.tech.'.$Ship) . "</th>";
			$html .= "<th>" . Format::number($Count) . "</th>";
		}

		$html .= "</tr></table>";

		throw new PageException($html);
	}
}