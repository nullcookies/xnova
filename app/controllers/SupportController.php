<?php

namespace App\Controllers;

use App\Helpers;

class SupportController extends ApplicationController
{
	public function initialize ()
	{
		parent::initialize();
	}
	
	public function indexAction ()
	{
		$action = (isset($_GET['action'])) ? $_GET['action'] : '';
		
		switch ($action)
		{
			case 'newticket':
		
				if (empty($_POST['text']) || empty($_POST['subject']))
					$this->message('Не заполнены все поля', 'Ошибка', '?set=support', 3);
		
				$this->db->query("INSERT game_support SET `player_id` = '" . $this->user->id . "', `subject` = '" . Helpers::CheckString($_POST['subject']) . "', `text` = '" . Helpers::CheckString($_POST['text']) . "', `time` = " . time() . ", `status` = '1';");
		
				$ID = $this->db->lastInsertId();
		
				$token = socials::smsGetToken();
				socials::smsSend(SMS_LOGIN, 'Создан новый тикет №' . $ID . ' ('.$this->user->username.')', $token);
		
				$this->message('Задача добавлена', 'Успех', '?set=support', 3);
		
				break;
		
			case 'send':
		
				if (isset($_GET['id']))
				{
					$TicketID = intval($_GET['id']);
		
					if (empty($_POST['text']))
						$this->message('Не заполнены все поля', 'Ошибка', '?set=support', 3);
		
					$ticket = $this->db->query("SELECT id, text, status FROM game_support WHERE `id` = '" . $TicketID . "';")->fetch();
		
					if (isset($ticket['id']))
					{
						$text = $ticket['text'] . '<hr>' . $this->user->username . ' ответил в ' . date("d.m.Y H:i:s", time()) . ':<br>' . Helpers::CheckString($_POST['text']) . '';
		
						$this->db->query("UPDATE game_support SET `text` = '" . addslashes($text) . "',`status` = '3' WHERE `id` = '" . $TicketID . "';");
		
						$this->game->sendMessage(1, false, time(), 4, $this->user->username, 'Поступил ответ на тикет №' . $TicketID);
		
						$this->message('Задача обновлена', 'Успех', '?set=support', 3);
		
						if ($ticket['status'] == 2)
						{
							$token = socials::smsGetToken();
							socials::smsSend(SMS_LOGIN, 'Поступил ответ на тикет №' . $ticket['id'] . ' ('.$this->user->username.')', $token);
						}
					}
				}
		
				break;
		
			default:
		
				$parse = array();
		
				$supports = $this->db->query("SELECT ID, time, text, subject, status FROM game_support WHERE (`player_id` = '" . $this->user->id . "') ORDER BY time DESC;");
		
				$parse['TicketsList'] = array();
		
				while ($ticket = $supports->fetch())
				{
					$parse['TicketsList'][$ticket['ID']] = array
					(
						'status' => $ticket['status'],
						'subject' => $ticket['subject'],
						'date' => $this->game->datezone("d.m.Y H:i:s", $ticket['time']),
						'text' => html_entity_decode($ticket['text'], ENT_NOQUOTES, "CP1251"),
					);
				}
		
				$this->view->pick('support');
				$this->view->setVar('parse', $parse);

				$this->tag->setTitle('Техподдержка');
				$this->showTopPanel(false);
		}
	}
}

?>