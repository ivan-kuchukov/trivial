<br>
<div style="background-color:lavender;padding-left:1em; position:fixed;bottom:0;width:100%">
    Debug Panel.
    <a href="<?= $_SERVER['BASE'] ?>/log/database_queries?uid=<?= trivial\controllers\App::getUID() ?>" target="_blank">
        Database queries:
        <?= $debug['database']['queriesCount'] ?? '?' 
        ?><span style="color:red">
        <?= ($debug['database']['errorQueriesCount']!=0) ? 'with ' . $debug['database']['errorQueriesCount'] . ' errors' : ''
        ?></span></a>.
    <a href="<?= $_SERVER['BASE'] ?>/log/errors?uid=<?= trivial\controllers\App::getUID() ?>" target="_blank">
        Errors:
        <span style="color:red">
        <?php $errors = trivial\models\Log::statistics('errorsFile');
            echo is_null($errors) ? '0' : $errors; ?>
        </span>
    </a>
    
</div>