<?php

require_once("Services/GEV/Mailing/classes/class.gevWebinarAutoMail.php");

class gevReminderWebinarATAndTMail extends gevWebinarAutoMail {
	const MINUTES_BEFORE_START = 60;

	public function getTitle() {
		return "Erinnerung virtuelle Trainings mit AT&T";
	}
	
	public function _getDescription() {
		return "Erinnerungsemail für virtuelle Training die mit dem Anbieter AT&T abgehandelt werden.";
	}

	public function getScheduledFor() {
		//returns da DateTime Object
		global $ilLog;
		$start_datetime = $this->getCourseStartWithTime();

		if ($start_datetime) {
			$start_datetime->sub(new DateInterval("PT".self::MINUTES_BEFORE_START."M"));
		}
		return $start_datetime;
	}

	public function getRecipientUserIDs() {
		return $this->getCourseUtils()->getParticipants();
	}

	public function getTemplateCategory() {
		return "W02";
	}

	public function getMail($a_recipient) {
		return $this->getMessage($this->getTemplateId(), $a_recipient);
	}

	public function getMessage($a_template_id, $a_recipient) {
		$message = $this->getMessageFromTemplate($a_template_id
												, $a_recipient);

		return array( "from" => $this->getFrom()
					, "to" => $this->getTo($a_recipient)
					, "cc" => $this->getCC($a_recipient)
					, "bcc" => $this->getBCC($a_recipient)
					, "subject" => $message["subject"]?$message["subject"]:""
					, "message_plain" => str_replace("<br />", "\n", $message["plain"])
					, "message_html" => $message["html"]
					, "attachments" => array()
					, "frame_plain" => $this->template_frame->getPlainTextFrame()
					, "frame_html" => $this->template_frame->getHtmlFrame()
					, "image_path" => $this->template_frame->getFileSystemBasePath()."/"
									  .$this->template_frame->getImageName()
					, "image_styles" => $this->template_frame->getImageStyles()
					);
	}
}