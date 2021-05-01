<?php
/**
 * @File    :   test.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */
require __DIR__ . '/../vendor/autoload.php';

$script_list = [
    'y=if((x+30)*0.5>100,x+60,x=90)+50'
];
$caculatorCls = new DiyExpress\Calculator();
$result = $caculatorCls->verifyFormFormula($script_list);
var_dump($result);
die;