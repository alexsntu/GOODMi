<?php
/***************************************************************************
*                                                                          *
*   © 2012 ООО "Эком Системы"                                              *
*                                                                          *
* Это коммерческое программное обеспечение. Только пользователи, которые   *
* приобрели действующую лицензию и согласились с условиями лицензионного   *
* соглашения, могут устанавливать и использовать эту программу.            *
*                                                                          *
****************************************************************************
* ПОЖАЛУЙСТА, ВНИМАТЕЛЬНО ПРОЧТИТЕ ПОЛНЫЙ ТЕКСТ ЛИЦЕНЗИОННОГО СОГЛАШЕНИЯ   *
* В ФАЙЛЕ "copyright.txt", ПРЕДОСТАВЛЕННОМ ВМЕСТЕ С ЭТИМ ДИСТРИБУТИВОМ.    *
***************************************************************************/

use Tygh\BlockManager\Block;
use Tygh\BlockManager\RenderManager;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'render') {
    if (empty($_REQUEST['object_key'])) {
        exit;
    }

    $object_key = $_REQUEST['object_key'];
    $object_key = fn_decrypt_text($object_key);
    list($block_id, $snapping_id) = explode(':', (string) $object_key) + [1 => 0]; // GOODMi 2026-10-23: битый ключ (боты) – Warning в PHP 8.3
    $block_id = (int) $block_id;
    $snapping_id = (int) $snapping_id;

    $block = Block::instance()->getById($block_id, $snapping_id);
    if ($block) {
        $block = array_merge([
            'grid_id' => 0,
            'order'   => 0,
        ], $block);

        if (!empty($_REQUEST['redirect_url'])) {
            /** @var \Tygh\SmartyEngine\Core $smarty */
            $smarty = Tygh::$app['view'];
            $smarty->assign('redirect_url', $_REQUEST['redirect_url']);
        }

        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        $ajax->assign('block_content', RenderManager::renderBlock($block));
    }

    exit;
}
