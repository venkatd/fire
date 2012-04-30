<div class="debug_panel collapsed">
    <div class="debug_bar">
        <a class="expand" href="#expand">Expand</a>
        <a class="collapse" href="#collapse">Collapse</a>
    </div>
    <div class="debug_body">
        <?php if (!is_array($tabs)) $tabs = array('main' => $tabs); ?>

        <div class="tab_panel">

            <div class="tabs">
                <?php foreach ($tabs as $k => $tab): ?>
                    <a class="tab" href="#<?= $k ?>"><?= $k ?></a>
                <?php endforeach; ?>
            </div>

            <div class="tab_panes">
                <?php foreach ($tabs as $k => $tab): ?>
                    <div class="pane <?= $k ?>">
                        <?= $tab ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
</div>
