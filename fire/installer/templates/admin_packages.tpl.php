<?php
/**
 * @var $packages string[]
 * @var $installer PackageInstaller
 */
?>

<table>
    <thead>
    <tr>
        <th>Package</th>
        <th>Installed</th>
        <th>Available</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($packages as $package_name): ?>
    <tr>
        <td><?= $package_name ?></td>
        <td><?= $installer->get_installed_version($package_name) ?></td>
        <td><?= $installer->get_available_version($package_name) ?></td>
        <td>
            <?php if ($installer->is_installed($package_name)): ?>
                <?= a("admin/packages/$package_name/update", 'update') ?>
            <?php else: ?>
            <?= a("admin/packages/$package_name/install", 'install') ?>
            <?php endif; ?>
        </td>
    </tr>
        <?php endforeach; ?>
    </tbody>
</table>
