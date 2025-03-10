<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 0.1
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 * @var \DebugKit\View\AjaxView $this
 * @var array $results
 */

use Cake\Core\Configure;
use Cake\Error\Debugger;
use function Cake\Core\h;
$resourceText = function ($resource) {
    if (!$resource) {
        return '';
    }
    try {
        $url = [
            ...$resource,
            ...$resource['pass'] ?? [],
            '?' => $resource['query'] ?? [],
        ];
        $url['_ext'] = $url['extension'];
        unset($url['pass'], $url['role'], $url['query']);
        $url = $this->Url->build($url);
        $html = sprintf('<p style="margin-top: 5px;">Url: <a href="%s" target="_blank">%s</a></p>', $url, $url);
    } catch (Throwable) {
        $html = '<p style="margin-top: 5px;">Can\' create url</p>';
    }

    $html = $this->Toolbar->dumpNode(Debugger::exportVarAsNodes($resource)) . $html;

    return $html;
}
?>
<style>
    .permission-panel .permission-failed {
        background: #ffead5;
    }
    .permission-panel .is-active {
        background-color: var(--routes-btn-active-bg);
        color: var(--routes-btn-active-text);
        border-color: var(--routes-btn-active-border);
        box-shadow: 0 2px 0 var(--routes-btn-active-border);
    }
</style>
<div class="permission-panel">
    <?php
    $msg = 'This table shows all permissions results from CollectionPolicy (CakeDC/Auth plugin)';
    printf('<p class="c-flash c-flash--info">%s</p>', $msg);
    ?>
    <?php if (!Configure::read('CakeDC/Auth.DebugKit.PermissionPanel.enabled')) :?>
        <p class="c-flash c-flash--info">Permissions are not being collected, please update the config:
        <br /><br />
        <code>Configure::write('CakeDC/Auth.DebugKit.PermissionPanel.enabled', true);</code>
        </p>
    <?php endif;?>
    <section>
        <button type="button" data-name="all" class="permission-btn-filter o-button is-active" onclick="PermissionPanel.displayAll()">
            All
        </button>
        <button type="button" data-name="only-allowed" class="permission-btn-filter o-button" onclick="PermissionPanel.displayOnlyAllowed()">
            Only Allowed
        </button>
        <button type="button" data-name="only-not-allowed" class="permission-btn-filter o-button" onclick="PermissionPanel.displayOnlyNotAllowed()">
            Only Not Allowed
        </button>
        <h4>Total shown: <span id="totalShownNumber"><?= count($results)?></span></h4>
        <table>
            <thead>
            <tr>
                <th>Type</th>
                <th>Allowed</th>
                <th>Resource</th>
                <th>Permission</th>
                <th>Reason</th>
            </tr>
            </thead>
            <tbody id="permissionResultsData">
            <?php foreach ($results as $resultItem) : ?>
                <tr data-allowed="<?= (int)$resultItem['status']?>" class="<?= $resultItem['status'] ? '' : 'permission-failed'?>">
                    <td><?= h($resultItem['type']) ?></td>
                    <td><?= $resultItem['status'] ? 'Yes' : 'No' ?></td>
                    <td><?= $resourceText($resultItem['resource']) ?></td>
                    <td><?= $this->Toolbar->dumpNode(Debugger::exportVarAsNodes($resultItem['permission'])) ?></td>
                    <td>
                        <?php if (!$resultItem['status']) :?>
                            <p><?= h($resultItem['reason']) ?></p>
                        <?php endif;?>
                        <?php if (!$resultItem['status'] && isset($resultItem['logReason'])) :?>
                            Log Reason: <?= h($resultItem['logReason']) ?>
                        <?php endif;?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
<script>
    var PermissionPanel = (function() {
        function updateSelectedFilter (selectedName) {
            document.querySelectorAll(".permission-panel .permission-btn-filter").forEach(function(item) {
                item.classList.remove('is-active');
                if (item.dataset.name === selectedName) {
                    item.classList.add('is-active');
                }
            });
        }
        function displayHideItems (check) {
            var totalShown = 0;
            document.querySelectorAll("#permissionResultsData > tr").forEach(function (item) {
                if (typeof item.dataset === 'undefined') {
                    return;
                }
                let allowed = item.dataset.allowed || '0';
                let show = check(allowed);
                if (show) {
                    totalShown++;
                }
                item.style.display = show ? 'table-row' : 'none';
            });
            document.getElementById('totalShownNumber').innerText = '' + totalShown;
        }
        return {
            displayAll: function() {
                displayHideItems(function() {
                    return true;
                })
                updateSelectedFilter('all');
            },
            displayOnlyAllowed() {
                displayHideItems(function(allowed) {
                    return allowed === '1';
                })
                updateSelectedFilter('only-allowed');
            },
            displayOnlyNotAllowed() {
                displayHideItems(function(allowed) {
                    return allowed === '0';
                })
                updateSelectedFilter('only-not-allowed');
            }
        };
    })();
</script>
