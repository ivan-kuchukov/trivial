<div id="trivial-debug-panel">
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
        <?php $errors = trivial\models\Log::addedCount('errorsFile');
            echo is_null($errors) ? '0' : $errors; ?>
        </span>
    </a>
    <span id="trivial-debug-panel-close" 
          style="float:right;cursor:pointer;margin-right:0.5em;display:none;" 
          onclick="document.getElementById('trivial-debug-panel').style.display='none'">X</span> 
</div>
</div>
<script>document.getElementById('trivial-debug-panel-close').style.display='block'</script>