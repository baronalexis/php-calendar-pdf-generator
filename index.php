<?php
    require('./AgendaPDF.php');
    
  	$events = array(
			"event1" => array(
				"DAY_EVENT" => 1,
				"START_HOUR_EVENT" => "08:00",
				"END_HOUR_EVENT" => "10:00",
				"DURATION" => 120,
				"MINUTES_FROM_START" => 0,
				"TITLE" => "Test1",
				"CONTENT" => "Lorem Ipsum"
			),
			"event2" => array(
				"DAY_EVENT" => 2,
				"START_HOUR_EVENT" => "10:00",
				"END_HOUR_EVENT" => "12:00",
				"DURATION" => 120,
				"MINUTES_FROM_START" => 120,
				"TITLE" => "Test2",
				"CONTENT" => "Lorem Ipsum"
			),
			"event3" => array(
				"DAY_EVENT" => 3,
				"START_HOUR_EVENT" => "08:00",
				"END_HOUR_EVENT" => "10:00",
				"DURATION" => 120,
				"MINUTES_FROM_START" => 0,
				"TITLE" => "Test3",
				"CONTENT" => "Lorem Ipsum"
			),
			"event4" => array(
				"DAY_EVENT" => 4,
				"START_HOUR_EVENT" => "16:30",
				"END_HOUR_EVENT" => "18:00",
				"DURATION" => 90,
				"MINUTES_FROM_START" => 510,
				"TITLE" => "Test4",
				"CONTENT" => "Lorem Ipsum"
			),
		);

		$start = date('Y-m-d');

		$pdf = new AgendaPDF2("L", "A4", 7);
		$width = $pdf->GetPageMarginedWidth();
		$pdf->Agenda($start, 5, 5, $width, 165, 7, '08:00', '18:00', '00:30');
		$pdf->AddEvents($events);
		$pdf->Save();

?>
