<?php
require_once '../vendor/autoload.php';

use App\Viabill\Viabill;

$viabill = new Viabill();

$index_url = $viabill->helper->getBaseURL();

$contents = '<!DOCTYPE html>
<html lang="en-gb" dir="ltr">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
</head>
<body>
    <div class="container">
    <h3>You order was cancelled!</h3>
    <div class="p-3 mb-2 bg-danger text-white">In the real world, this page will be an actual page from your e-commerce platform.</div> 
    <a href="'.$index_url.'">Go back to main menu to try something difference</a>
</body>
</html>';

echo $contents;
