<?php

function loadData($filename)
{
    if (!file_exists($filename) || !is_readable($filename)) {
        return null;
    }
    $contents = file_get_contents($filename);
    $hdd = json_decode($contents);
    usort($hdd, function($a, $b) {
        $diff = $a->ratio->avg - $b->ratio->avg;
        if (abs($diff) < 0.0001) {
            return 0;
        }
        return $diff < 0 ? -1 : 1;
    });
    return $hdd;
}

function drawTable($hdd)
{
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        if (isset($hdd[$id])) {
            var_dump($hdd[$id]);
            return;
        }
    }
    echo '<table class="table table-condensed table-striped">';
    echo '<thead><tr><th>#</th><th>Title</th><th>Interface</th><th>Capacity (GB)</th><th>Avg Price</th><th>Ratio</th></tr></thead>';
    echo '<tbody>';
    foreach ($hdd as $id => $h): ?>
    <tr>
        <td><a href="?id=<?= $id ?>"><?= $id ?></a></td>
        <td><a href="<?= $h->url ?>"><?= $h->title ?></a></td>
        <td><?= $h->interface ?></td>
        <td><?= $h->capacity ?></td>
        <td>
            <span title="From <?= $h->price->min ?> to <?= $h->price->max ?>">
                <?= $h->price->avg ?>
            </span>
        </td>
        <td>
            <span title="From <?= $h->ratio->min ?> to <?= $h->ratio->max ?>">
                <?= $h->ratio->avg ?>
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
        <link href="/css/bootstrap.min.css" rel="stylesheet">
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <!-- Static navbar -->
            <div class="navbar navbar-default" role="navigation">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="/"><span class="glyphicon glyphicon-align-left"></span> Storage comparator</a>
                    </div>
                    <div class="navbar-collapse collapse">
                        <ul class="nav navbar-nav">
                            <li class="active"><a href="/hdd/">HDD</a></li>
                            <li><a href="/ssd/">SSD</a></li>
                        </ul>
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="http://github.com/e-moe/hdd_prices/"><span class="glyphicon glyphicon-file"></span> Github</a></li>
                            <li><a href="http://www.labinskiy.org.ua/"><span class="glyphicon glyphicon-user"></span> Author</a></li>
                        </ul>
                    </div><!--/.nav-collapse -->
                </div><!--/.container-fluid -->
            </div>
            <div class="page-header">
                <h1>HDD prices <small>based on hotline.ua data</small></h1>
            </div>
            <?php drawTable(loadData('../../data/hdd/latest.json')); ?>
        </div>

        <script src="/js/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
    </body>
</html>