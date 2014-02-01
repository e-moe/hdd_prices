<?php

function loadData($filename)
{
    if (!file_exists($filename) || !is_readable($filename)) {
        return null;
    }
    $contents = file_get_contents($filename);
    $hdd = json_decode($contents);
    usort($hdd, function($a, $b) {
        $diff = $a->k->avg - $b->k->avg;
        if (abs($diff) < 0.0001) {
            return 0;
        }
        return $diff < 0 ? -1 : 1;
    });
    return $hdd;
}

function drawTable($hdd)
{
    echo '<table class="table table-condensed table-striped">';
    echo '<thead><tr><th>Title</th><th>Interface</th><th>Capacity (TB)</th><th>Avg Price</th><th>Avg K</th></tr></thead>';
    echo '<tbody>';
    foreach ($hdd as $h): ?>
    <tr>
        <td><a href="<?= $h->url ?>"><?= $h->title ?></a></td>
        <td><?= $h->interface ?></td>
        <td><?= $h->capacity ?></td>
        <td>
            <span title="From <?= $h->price->min ?> to <?= $h->price->max ?>">
                <?= $h->price->avg ?>
            </span>
        </td>
        <td>
            <span title="From <?= $h->k->min ?> to <?= $h->k->max ?>">
                <?= $h->k->avg ?>
            </span>
        </td>
    </tr>
    <?php
    endforeach;
    echo '</tbody>';
    echo '</table>';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HDD Prices</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container">
        <h1>HDD Prices</h1>
        <?php drawTable(loadData('../data/latest.json')); ?>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>