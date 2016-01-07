<?php

namespace App\Controllers;

use App\CombatReport;

class RwController extends ApplicationController
{
	public function initialize ()
	{
		parent::initialize();
	}
	
	public function indexAction ()
	{
		if (!$this->request->hasQuery('id'))
			return $this->message('Боевой отчет не найден');

		$raportrow = $this->db->query("SELECT * FROM game_rw WHERE `id` = '" . $this->request->getQuery('id', 'int') . "'")->fetch();
		
		if (!isset($raportrow['id']))
			return $this->message('Данный боевой отчет удалён с сервера', 'Ошибка', '', 0, false);

		$user_list = json_decode($raportrow['id_users'], true);
		
		if (isset($raportrow['id']) && !$this->user->isAdmin() && (!isset($_GET['k']) ||  md5('xnovasuka' . $raportrow['id']) != $_GET['k']))
			$this->message('Не правильный ключ', 'Ошибка', '', 0, false);
		elseif (!in_array($this->user->id, $user_list) && !$this->user->isAdmin())
			$this->message('Вы не можете просматривать этот боевой доклад', 'Ошибка', '', 0, false);
		else
		{
			if ($this->request->isAjax() && $this->auth->isAuthorized())
			{
				$Page = "";
		
				if ($user_list[0] == $this->user->id && $raportrow['no_contact'] == 1 && !$this->user->isAdmin())
					$Page .= "Контакт с вашим флотом потерян.<br>(Ваш флот был уничтожен в первой волне атаки.)";
				else
				{
					$result = json_decode($raportrow['raport'], true);

					$report = new CombatReport($result[0], $result[1], $result[2], $result[3], $result[4], $result[5]);
					$formatted_cr = $report->old();

					$Page .= $formatted_cr['html'];
		
					$Page .= '<script>$(function(){$(\'#raportRaw\').multiAccordion({active: ['.(count($result[0]['rw']) - 1).']})});</script>';
				}
		
				$Page .= "<div class='separator'></div>ID боевого доклада: <a href=\"/log/new/save/" . md5('xnovasuka' . $raportrow['id']) . $raportrow['id'] . "/\"><font color=red>" . md5('xnovasuka' . $raportrow['id']) . $raportrow['id'] . "</font></a>";
		
				if ($this->config->game->get('gameTemplate') == 'main')
				{
					$Page .= '<div class="separator"></div><a data-link="1" target="_blank" href="/rw/'.$_GET['r'].'/'.$_GET['k'].'/">Полная версия боя</a>';
				}

				$this->tag->setTitle('Боевой доклад');
				$this->view->setVar('html', $Page);
				$this->showTopPanel(false);
			}
			else
			{
				$result = json_decode($raportrow['raport'], true);

				$Page = "<html><head><title>Боевой доклад</title>";
				$Page .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/assets/css/report.css\">";
				$Page .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />";
				$Page .= "</head><body><script>function show(id){if(document.getElementById(id).style.display==\"block\")document.getElementById(id).style.display=\"none\"; else document.getElementById(id).style.display=\"block\";}</script>";
				$Page .= "<table width=\"99%\"><tr><td><center>";
		
				if ($user_list[0] == $this->user->id && $raportrow['no_contact'] == 1 && !$this->user->isAdmin())
				{
					$Page .= "Контакт с вашим флотом потерян.<br>(Ваш флот был уничтожен в первой волне атаки.)";
				}
				else
				{
					$report = new CombatReport($result[0], $result[1], $result[2], $result[3], $result[4], $result[5], $result[6]);
					$formatted_cr = $report->report();

					$Page .= $formatted_cr['html'];
				}
		
				$Page .= "</center></td></tr><tr align=center><td>ID боевого доклада: <a href=\"/log/new/save/" . md5('xnovasuka' . $raportrow['id']) . $raportrow['id'] . "/\"><font color=red>" . md5('xnovasuka' . $raportrow['id']) . $raportrow['id'] . "</font></a></td></tr>";
				$Page .= "</table></body></html>";
		
				echo $Page;

				$this->view->disable();
				die();
			}
		}

		return true;
	}
}

?>