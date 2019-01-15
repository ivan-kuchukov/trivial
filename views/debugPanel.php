<?php ?>
<div style="background-color:lavender;padding-left:1em">
    Debug Panel. Database queries count:
    <a href="<?= $_SERVER['BASE'] ?>/log/database_queries?uid=<?= controllers\App::getUID() ?>" target="_blank" style="color: lime"><?php 
        echo $debug['database']['queriesCount'] ?? '?' 
    ?></a>
    errors:
    <a href="<?= $_SERVER['BASE'] ?>/log/database_errors?uid=<?= controllers\App::getUID() ?>" target="_blank" style="color: red"><?php 
        echo $debug['database']['errorQueriesCount'] ?? '?' 
    ?></a>
</div>