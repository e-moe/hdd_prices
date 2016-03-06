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
    echo '<table class="table is-striped is-narrow">';
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
    <title>HDD Prices - Storage comparator</title>
    <link rel="stylesheet" href="css/font-awesome/font-awesome.min.css">
    <link rel="stylesheet" href="css/bulma/bulma.min.css">
</head>
<body>
<header class="header">
    <div class="container">
        <!-- Left side -->
        <div class="header-left">
                    <span class="header-item">
                        <a class="button is-primary is-inverted" href="/">
                            <span class="icon">
                                <i class="fa fa-home"></i>
                            </span>
                            Storage comparator
                        </a>
                    </span>
            <a class="header-item" href="/hdd">
                HDD
            </a>
            <a class="header-item" href="/ssd">
                <strong>SSD</strong>
            </a>
        </div>

        <!-- Hamburger menu (on mobile) -->
            <span class="header-toggle">
                <span></span>
                <span></span>
                <span></span>
            </span>

        <!-- Right side -->
        <div class="header-right header-menu">
            <span class="header-item">
                <a class="button is-primary is-inverted" href="https://github.com/e-moe/hdd_prices">
                    <span class="icon">
                        <i class="fa fa-github"></i>
                    </span>
                    GitHub
                </a>
            </span>
        </div>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="heading">
            <h1 class="title">SSD prices</h1>
            <h2 class="subtitle">
                based on hotline.ua data
            </h2>
        </div>
        <?php drawTable(loadData('../../data/ssd/latest.json')); ?>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="content is-text-centered">
            <p>
                <strong>Storage comparator</strong> by <a href="//labinskiy.org.ua">Nikolay Labinskiy</a>.
                The source code is licensed <a href="http://opensource.org/licenses/mit-license.php">MIT</a>.
                The website content is licensed <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">CC BY-NC-SA 4.0</a>.
            </p>
        </div>
    </div>
</footer>
</body>
</html>