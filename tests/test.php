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
error_reporting(E_ERROR);
require __DIR__ . '/../vendor/autoload.php';

// // demo express.
// $script_list = [
//     'y=if((x+30)*0.5>100,x+60,x-90)+50',
//     "z=if(((x+30)*0.5)>100,'真值','假值')"
// ];
$caculatorCls = new DiyExpress\Calculator();
// $result = $caculatorCls->verifyFormFormula($script_list);
// var_dump($result);
// die;
// array(1) {
//     ["y"]=>
//     array(2) {
//       ["formula"]=> // 验证合法的公式
//       string(33) "y=if((x+30)*0.5>100,x+60,x-90)+50"
//       ["child"]=> // 变量在child中返回
//       array(2) {
//         [0]=>
//         string(1) "y"
//         [1]=>
//         string(1) "x"
//       }
//     }

// demo calc y
// if given the x value 30, then express str can use replace x.
// $x = 30;
// $script_list = [
//     'y=if((30+30)*0.5>100,30+60,30-90)+50', // 这里的y=-10
// ];

$data = [
    'x'=>171
];
var_dump($data);

// $dirty_express1 =  "y=if((x+30)*0.5>100,x+60,x-90)+50";
// $diy_express1 = "{{y}}=if((({{x}}+30)*0.5)>100,{{x}}+60,{{x}}-90)+50";
// var_dump($diy_express1);
// $res = $caculatorCls->executeFormFormula($diy_express1,$data,'y');
// var_dump(['y'=>$res]);

$dirty_express = "z=if(((x+30)*0.5)>100,'真值','假值')"; // 原本的自定义表达式，在使用计算的时候，需要特殊处理一下，将变量用双花括号包起来，如下：
$diy_express = "{{z}}=if((({{x}}+30)*0.5)>100,'真值','假值')";
var_dump($diy_express);

$res = $caculatorCls->executeFormFormula($diy_express,$data,'z');
var_dump(['z'=>$res]);
die;