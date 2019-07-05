<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH . 'third_party/fpdf181/fpdf.php');

class AgendaPDF extends FPDF {

    private $days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public $margin = 0;
    public $width = 0;
    public $height = 0;
    public $dayLineHeight = 5;
    public $slotDuration = [];
    public $slotHeight = 0;
    public $startDay = null;
    public $minTime = '08:30';
    public $maxTime = '17:30';
    var $widths;
    var $aligns;

    function __construct($orientation = "L", $format = "A4", $margin = 0) {
        parent::__construct($orientation, "mm", $format);
        $this->SetFont('Times', '', 12);
        $this->margin = $margin;
    }


    function Agenda($date_deb, $x = 0, $y = 0, $width = 50, $height = 50, $days = 7, $minTime = "08:30", $maxTime= "17:30", $slotDuration = "00:30") {
        $this->AddPage();
        $this->SetXY($x, $y);
        $this->width = $width;
        $this->height = $height;
        $this->startDay = $date_deb;
        $this->minTime = $minTime;
        $this->maxTime = $maxTime;

        $startHour = new DateTime($minTime);
        $endHour = new DateTime($maxTime);

        $this->diff = $endHour->diff($startHour)->format('%h:%i');
        $this->diff = explode(':', $this->diff);
        $this->diff = $this->diff;
        $slotDuration = explode(':', $slotDuration);
        $this->slotDuration = $slotDuration;

        $nbRows = ($this->diff[0] * 60 + $this->diff[1]) / ($this->slotDuration[0] * 60 + $this->slotDuration[1]);
        $this->nbRows = $nbRows;

        $slotHeight = ($this->height - $this->dayLineHeight) / $nbRows;
        $this->slotHeight = $slotHeight;

        $this->Rect($this->GetX(), $this->GetY(), $this->width, $this->height);
        $this->agendaX = $x;
        $this->agendaY = $y;
        $this->GenerateColumns($days);
        $this->GenerateLines($minTime, $maxTime);
    }

    function GenerateColumns($days) {
        $date = new DateTime($this->startDay);
        $hourColumnWitdh = 15;
        $this->hourColumnWitdh = $hourColumnWitdh;
        $offsetDay = 4;
        $dayWidth = ($this->width - $hourColumnWitdh) / $days;
        $this->dayWidth = $dayWidth;
        $this->Line($this->GetX() + $hourColumnWitdh, $this->GetY(), $this->GetX() + $hourColumnWitdh, $this->height + $this->GetY());
		for($i = 1; $i <= $days; $i++) {
            $xLine = $this->GetX() + $dayWidth * $i + $hourColumnWitdh;
            $this->Line($xLine, $this->GetY(), $xLine, $this->height + $this->GetY());
            $this->Text($xLine - $this->GetStringWidth($this->days[$i].' '.$date->format('m/d'))/2 - $dayWidth / 2, $this->GetY() + $offsetDay, $this->days[$i].' '.$date->format('m/d'));
            $date->modify('+1 days');
		}
    }

    function GenerateLines($minTime, $maxTime) {
        $hours = [];
        $offsetHour = 7;
        $hourFontSize = 9;
        $hours[0] = $minTime;
        $timeToAdd = new DateInterval('PT'.$this->slotDuration[0].'H'.$this->slotDuration[1].'M');
        for($i = 1; $i < $this->nbRows; $i++) {
            $prevHour = new DateTime($hours[$i - 1]);
            $hours[$i] =  $prevHour->add($timeToAdd)->format('H:i');
        }

		$this->Line($this->GetX(), $this->GetY() + $this->dayLineHeight, $this->GetX() + $this->width, $this->GetY() + $this->dayLineHeight);
		$this->SetLineWidth(0.05);
		for($y = 0; $y < $this->nbRows; $y++) {
			if($y % 2  == 0) {
				$this->Line($this->GetX(), $this->slotHeight * $y + $this->GetY() + $this->dayLineHeight, $this->GetX() + $this->width, $this->slotHeight * $y + $this->GetY() + $this->dayLineHeight);
				$this->SetFontSize($hourFontSize);
                $this->Text($this->GetX() + $offsetHour, $this->GetY() + $this->dayLineHeight + $this->slotHeight * $y + $hourFontSize / 2, $hours[$y]);
			} else {
				$this->SetDash(2,1);
				$this->Line($this->GetX(), $this->slotHeight * $y + $this->GetY() + $this->dayLineHeight, $this->GetX() + $this->width, $this->slotHeight * $y + $this->GetY() + $this->dayLineHeight);
				$this->SetDash();
			}
		}
		$this->SetLineWidth(0.2);
    }

    function Save() {
        $this->Output();
    }

    function GetPageMarginedWidth() {
        return $this->GetPageWidth() - $this->margin * 2;
    }

    function GetMargin() {
        return $this->margin;
    }



    function SetDash($black = null, $white = null) {
      if($black!==null)
        $s = sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
      else
        $s='[] 0 d';
      $this->_out($s);
    }

    public function AddEvents($events) {
        $keys = array_keys($events);
        $overlapping = array();
            foreach($keys as $i => $e ) {
                if($i < count($events) - 1) {
                    $next_event = $events[$keys[$i + 1]];
                } else {
                    $next_event = null;
                }
                $e = $events[$e];
                $current_date = array(
                    'day'  => $e['DAY_EVENT'],
                    'start' => $e['START_HOUR_EVENT'],
                    'end'   => $e['END_HOUR_EVENT']
                );
                $next_date = array(
                    'day'  => $next_event['DAY_EVENT'],
                    'start' => $next_event['START_HOUR_EVENT'],
                    'end'   => $next_event['END_HOUR_EVENT']
                );
                if($this->event_compare($current_date, $next_date) > 0) {
                    $overlapping[] = $next_event;
                } else {
                    if(count($overlapping) > 0) {
                        $i = 0;
                        foreach ($overlapping as $e) {
                            $this->AddEvent($e['DURATION'], $e['MINUTES_FROM_START'], $e['DAY_EVENT'], $e['START_HOUR_EVENT'], $e['END_HOUR_EVENT'], $e['TITLE'], $e['CONTENT'], count($overlapping), $i);
                            $i++;
                        }
                        $overlapping = [];
                    } else {
                        $this->AddEvent($e['DURATION'], $e['MINUTES_FROM_START'], $e['DAY_EVENT'], $e['START_HOUR_EVENT'], $e['END_HOUR_EVENT'], $e['TITLE'], $e['CONTENT'], 1, 0);
                    }
                }
        }
    }

    public function AddEvent($lenght, $minFromStart, $day, $startTime, $endTime, $title, $description, $nbSurbook, $currentSurbook) {

        if($startTime >= $this->minTime  && $endTime <= $this->maxTime) {


            $marginEvent = 2;
            $offset_x_event = 1;
            $offset_y_event = 3;

            $one_min = $this->slotHeight / ($this->slotDuration[0] * 60 + $this->slotDuration[1]);

            $this->SetXY($this->agendaX + $this->hourColumnWitdh + $this->dayWidth * ($day - 1) + $marginEvent / 2 + (($this->dayWidth - $marginEvent) / $nbSurbook) * $currentSurbook, $this->agendaY + $one_min * $minFromStart + $this->dayLineHeight);
            $this->SetFontSize(7);
            $this->SetLineWidth(0.4);
            $this->SetFillColor(255, 255, 255);
            $this->Rect($this->GetX(), $this->GetY(), ($this->dayWidth - $marginEvent) / $nbSurbook, $one_min * $lenght, 'FD');
            $this->SetLineWidth(0.2);

            $i = 3.5;



            $description = explode('°', wordwrap($description, 25 / $nbSurbook, '°'));

            $this->Text($this->GetX() + $offset_x_event, $this->GetY() + $offset_y_event, $startTime.' - '.$endTime);
            $this->SetFont('Times', 'B', 7);

            $this->Text($this->GetX() + $offset_x_event, $this->GetY() + $offset_y_event + $i, $title);
            $this->SetFont('Times', '', 7);

            $i += $i;

            foreach($description as $d) {
                $this->Text($this->GetX() + $offset_x_event, $this->GetY() + $offset_y_event + $i + 1, $d);
                $i = $i + 2.5;
            }
        }
    }

    private function event_compare($date1, $date2) {
        $date1_start = new DateTime($date1['start']);
        $date1_end = new DateTime($date1['end']);
        $date2_start = new DateTime($date2['start']);
        $date2_end = new DateTime($date2['end']);

        if ($date1_end >= $date2_start && $date1_start <= $date2_end && $date1['day'] == $date2['day']) {
            return 1;
        } else {
            return 0;
        }
    }
}
?>
