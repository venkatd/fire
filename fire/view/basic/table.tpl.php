<?php
$column_indexes = array_flip($columns);
?>
<table>
    <thead>
    <tr>
        <?php foreach ($columns as $column): ?>
        <th>
            <?= $column ?>
        </th>
        <?php endforeach; ?>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $column): ?>
                <td>
                    <?php if (is_array($row) && isset($row[$column])): ?>
                        <?= $row[$column] ?>
                    <?php endif; ?>

                    <?php if (is_object($row) && isset($row->$column)): ?>
                        <?= $row->$column ?>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
    <?php endforeach; ?>
    </tbody>

</table>
