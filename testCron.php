<?php

use dailySummaryOrder;

$testDaily = new dailySummaryOrder();

$tab = $testDaily->doSQLRequest();

var_dump($tab);