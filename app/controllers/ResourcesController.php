<?php

namespace App\Controllers;

use App\Helpers;
use App\Sql;

class ResourcesController extends ApplicationController
{
	public function initialize ()
	{
		parent::initialize();

		$this->user->loadPlanet();
	}

	private function buy ($parse)
	{
		global $reslist;

		if ($this->user->banned > 0)
			$this->message("Включен режим отпуска!");

		if ($this->user->credits >= 10)
		{
			if ($this->planet->merchand < time())
			{
				$arFields = array('merchand' => (time() + 172800));

				foreach ($reslist['res'] AS $res)
					$arFields['+'.$res] = $parse['buy_'.$res];

				$this->planet->saveData($arFields);

				$this->db->query('UPDATE game_users SET credits = credits - 10 WHERE id = ' . $this->user->id . ';');
				$this->db->query("INSERT INTO game_log_credits (uid, time, credits, type) VALUES (" . $this->user->id . ", " . time() . ", " . (10 * (-1)) . ", 2)");

				$this->message('Вы успешно купили ' . $parse['buy_metal'] . ' металла, ' . $parse['buy_crystal'] . ' кристалла, ' . $parse['buy_deuterium'] . ' дейтерия', 'Успешная покупка', '?set=resources', 2);
			}
			else
				$this->message('Покупать ресурсы можно только раз в 48 часов', 'Ошибка', '?set=resources', 2);
		}
		else
			$this->message('Для покупки вам необходимо еще ' . (10 - $this->user->credits) . ' кредитов', 'Ошибка', '?set=resources', 2);
	}
	
	public function show ()
	{
		global $reslist, $resource, $ProdGrid;

		if ($this->planet->planet_type == 3 || $this->planet->planet_type == 5)
		{
			foreach ($reslist['res'] AS $res)
				core::setConfig($res.'_basic_income', 0);
		}

		$CurrentUser['energy_tech'] = $this->user->energy_tech;
		$ValidList['percent'] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
		
		if (isset($_GET['production_full']) || isset($_GET['production_empty']))
		{
			if ($this->user->banned > 0)
				$this->message("Включен режим отпуска!");

			$planets = $this->db->query("SELECT * FROM game_planets WHERE `id_owner` = '" . $this->user->id . "'");
		
			$pl_class = new planet();
			$pl_class->load_user_info(user::get());

			while ($planet = $planets->fetch())
			{
				$pl_class->load_from_array($planet);
				$pl_class->PlanetResourceUpdate();
			}

			$production = (isset($_GET['production_full'])) ? 10 : 0;

			$arFields = array
			(
				$resource[4].'_porcent' 	=> $production,
				$resource[12].'_porcent' 	=> $production,
				$resource[212].'_porcent' 	=> $production
			);

			foreach ($reslist['res'] AS $res)
			{
				$this->planet->data[$res.'_mine_porcent'] = $production;
				$arFields[$res.'_mine_porcent'] = $production;
			}

			Sql::build()->update('game_planets')->set($arFields)->where('id_owner', '=', $this->user->id)->execute();

			$this->planet->data[$resource[4].'_porcent'] 		= $production;
			$this->planet->data[$resource[12].'_porcent'] 	= $production;
			$this->planet->data[$resource[212].'_porcent']	= $production;
		
			$this->planet->PlanetResourceUpdate(time(), true);
		}
		
		if ($_POST)
		{
			if ($this->user->banned > 0)
				$this->message("Включен режим отпуска!");

			$arFields = array();

			foreach ($_POST as $Field => $Value)
			{
				if (isset($this->planet->data[$Field.'_porcent']) && in_array($Value, $ValidList['percent']))
				{
					$arFields[$Field.'_porcent'] = $Value;

					$this->planet->data[$Field.'_porcent'] = $Value;
				}
			}

			if (count($arFields))
				$this->planet->saveData($arFields);

			$this->planet->PlanetResourceUpdate(time(), true);
		}
		
		$parse = array();

		$production_level = $this->planet->production_level;

		$parse['bonus_h'] = ($this->user->bonusValue('storage') - 1) * 100;

		$parse['resource_row'] = array();

		foreach ($reslist['prod'] as $ProdID)
		{
			if ($this->planet->data[$resource[$ProdID]] > 0 && isset($ProdGrid[$ProdID]))
			{
				$BuildLevelFactor = $this->planet->data[$resource[$ProdID] . "_porcent"];
				$BuildLevel = $this->planet->data[$resource[$ProdID]];

				$result = $this->planet->getProductionLevel($ProdID, $BuildLevel, $BuildLevelFactor);

				foreach ($reslist['res'] AS $res)
				{
					$$res = $result[$res];
					$$res = round($$res * 0.01 * $production_level);
				}

				$energy = $result['energy'];

				$CurrRow = array();
		        $CurrRow['id'] = $ProdID;
				$CurrRow['name'] = $resource[$ProdID];
				$CurrRow['porcent'] = $this->planet->data[$resource[$ProdID] . "_porcent"];

				$CurrRow['bonus'] = ($ProdID == 4 || $ProdID == 12 || $ProdID == 212) ? (($ProdID == 212) ? $this->user->bonusValue('solar') : $this->user->bonusValue('energy')) : (($ProdID == 1) ? $this->user->bonusValue('metal') : (($ProdID == 2) ? $this->user->bonusValue('crystal') : (($ProdID == 3) ? $this->user->bonusValue('deuterium') : 0)));

				if ($ProdID == 4)
					$CurrRow['bonus'] += $this->user->energy_tech / 100;

				$CurrRow['bonus'] = ($CurrRow['bonus'] - 1) * 100;

				$CurrRow['level_type'] = $this->planet->data[$resource[$ProdID]];

				foreach ($reslist['res'] AS $res)
				{
					$CurrRow[$res.'_type'] = $$res;
				}

				$CurrRow['energy_type'] = $energy;

				$parse['resource_row'][] = $CurrRow;
			}
		}

		foreach ($reslist['res'] AS $res)
		{
			$parse[$res.'_basic_income'] = core::getConfig($res.'_basic_income', 0) * core::getConfig('resource_multiplier', 1);

			$parse[$res.'_max'] = '<font color="#' . (($this->planet->data[$res.'_max'] < $this->planet->data[$res]) ? 'ff00' : '00ff') . '00">';
			$parse[$res.'_max'] .= Helpers::pretty_number($this->planet->data[$res.'_max'] / 1000) . " k</font>";

			$parse[$res.'_total'] = $this->planet->data[$res.'_perhour'] + $parse[$res.'_basic_income'];
			$parse[$res.'_storage'] = floor($this->planet->data[$res] / $this->planet->data[$res.'_max'] * 100);
			$parse[$res.'_storage_bar'] = floor(($this->planet->data[$res] / $this->planet->data[$res.'_max']) * 100);

			if ($parse[$res.'_storage_bar'] >= 100)
				$parse[$res.'_storage_barcolor'] = '#C00000';
			elseif ($parse[$res.'_storage_bar'] >= 80)
				$parse[$res.'_storage_barcolor'] = '#C0C000';
			else
				$parse[$res.'_storage_barcolor'] = '#00C000';

			$parse['buy_'.$res] = $parse[$res.'_total'] * 8;
		}

		if (isset($_GET['buy']) && $this->planet->id > 0 && $this->planet->planet_type == 1)
		{
			$this->buy($parse);
		}

		foreach ($reslist['res'] AS $res)
			$parse['buy_'.$res] = Helpers::colorNumber(Helpers::pretty_number($parse['buy_'.$res]));

		$parse['energy_basic_income'] = core::getConfig('energy_basic_income');

		$parse['energy_total'] = Helpers::colorNumber(Helpers::pretty_number(floor(($this->planet->energy_max + $parse['energy_basic_income']) + $this->planet->energy_used)));
		$parse['energy_max'] = Helpers::pretty_number(floor($this->planet->energy_max));

		$parse['merchand'] = $this->planet->merchand;

		$parse['production_level_bar'] = $production_level;
		$parse['production_level'] = "{$production_level}%";
		$parse['production_level_barcolor'] = '#00ff00';
		$parse['name'] = $this->planet->name;
		
		$parse['et'] = $this->user->energy_tech;
		
		$this->view->pick('resources');
		$this->view->setVar('parse', $parse);

		$this->tag->setTitle('Сырьё');
	}
}

?>