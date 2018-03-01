<?php
namespace JTM\Crypto;
require_once( 'includes/init.php' );
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="refresh" content="60">

	<title>JTM's Crypto Currency Exchange Rates</title>

	<link rel="stylesheet" href="main.css">
	<link rel="icon" href="images/favicon.png">
</head>

<body>
	<?php navigate(); ?>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="script.js"></script>
</body>

</html>





<?php
function navigate( string $page = '' ) {
	if( empty( $page ) && array_key_exists( 'page', $_GET ) ) {
		$page = $_GET['page'];
	}

	switch( $page ) {
		case 'simpletable':
		default:
			echo page_simple_table();
			break;
	}
}

function page_simple_table() {
	$curobjs = array( new Currencies\BTC(), new Currencies\LTC(), new Currencies\BCH(), new Currencies\ETH() );

	//	Queue all the symbols and run as a batch for better performance
	foreach( $curobjs as $Cur ) {
		$Cur->queue();
	}
	Currencies\Currency::run_queue();

	//	Output as a table
	$html = '<h1>Simple USD Exchange Rate Table</h1>';
	$html .= '<table id="simpletable">';
		foreach( $curobjs as $Cur ) {
			$html .= '<tr>';
				$html .= '<th>' . $Cur->get('readable_name') . ' (' . $Cur->get('symbol') . ')</th>';
				$html .= '<td>' . $Cur->get_current_exchange_rate() . '</td>';
				$html .= '</tr>';
		}
	$html .= '</table>';

	return $html;
}