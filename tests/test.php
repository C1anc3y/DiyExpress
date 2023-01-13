<?php

error_reporting(E_ERROR);
require __DIR__ . '/../vendor/autoload.php';

// // demo express.
// $script_list = [
//     'y=if((x+30)*0.5>100,x+60,x-90)+50',
//     "z=if(((x+30)*0.5)>100,'真值','假值')"
// ];
$calculatorCls = new DiyExpress\Calculator();
// $result = $calculatorCls->verifyFormFormula($script_list);
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

// $test_str = "月薪奖惩比例=if(收入指标达成率>=105,10,if(收入指标达成率>=95,5,if(收入指标达成率>=85,0,if(收入指标达成率>=70,0-5,0-10)))) + if(安装率>=80,0,0-2) + 月薪特殊奖惩比例";

// $test_list = [
//     $test_str,
// ];
// $calculatorCls = new DiyExpress\Calculator();
// $result = $calculatorCls->verifyFormFormula($test_list);
// var_dump($result);

$data = [
    '到款' => 171
];
var_dump($data);
$dirty_express = "销售评级=if(((到款+30)*0.5)>100,优秀,一般)"; // 原本的自定义表达式，在使用计算的时候，需要特殊处理一下，将变量用双花括号包起来，如下：
$diy_express = "{{销售评级}}=if((({{到款}}+30)*0.5)>1000,优秀,一般)+10";
var_dump($diy_express);

try {
    $res = $calculatorCls->executeFormFormula($diy_express, $data, '销售评级');
} catch (Exception $e) {
    $res = [];
}
var_dump(['销售评级' => $res]);
die;