<?php

$config = parse_ini_file("./config.ini");


function getTerm()
{
    global $config;
    $token = $config['helperToken'];
    $urlPattern = "https://pkuhelper.pku.edu.cn/api_xmcp/isop/scores?user_token=";
    $dataStr = file_get_contents($urlPattern . $token);
//    $dataStr = file_get_contents('./tmp.json');
    $data = json_decode($dataStr, true);
    $scoreData = $data['cjxx'];
    $termData = [];
    foreach ($scoreData as $scoreDatum) {
        if ($scoreDatum['xnd'] == "19-20" and $scoreDatum['xq'] == "2") {
            $termData[] = $scoreDatum;
        }
    }
    return $termData;
}

function sendMessage($title, $content = '')
{
    global $config;
    $key = $config['ScToken'];
    $postData = http_build_query(array('text' => $title, 'desp' => $content));
    $opts = array('http' => array('method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => $postData));
    $context = stream_context_create($opts);
    $result = json_decode(file_get_contents('https://sc.ftqq.com/' . $key . '.send', false, $context), true);
    echo "[MSG]发送信息,标题为: \"" . $title . "\"\n";
    echo "[MSG]发送状态: " . $result['errmsg'] . "\n";
}

$userData = null;

function init()
{
    global $userData;
    global $config;
    $termData = getTerm();
    $userData = json_decode(file_get_contents("./users.json"), true);
    $userData['termCourse'] = null;
    foreach ($termData as $termDatum) {
        $userData['termCourse'][] = $termDatum['kch'];
    }
    sendMessage("[" . $userData['name'] . "出分监控] InitOK", "接下来帮你每隔1分钟查询期末出分情况～\n\n当前已出分" . sizeof($userData['termCourse']) . "门课程，静候佳音吧");
}

function getNew()
{
    global $userData;
    $termData = getTerm();
    echo "[INFO]已出分课程数量: " . sizeof($termData) . "\n";
    $newData = [];
    if (sizeof($userData['termCourse']) < sizeof($termData)) {
        echo "[NEWS]有课程出分了！！！！！\n";
        foreach ($termData as $termDatum) {
            $courseNum = $termDatum['kch'];
            if (!in_array($courseNum, $userData['termCourse'])) {
                $newData[] = $termDatum;
                $userData['termCourse'][] = $courseNum;
            }
        }
    }
    return $newData;
}

init();
for ($i = 0; sizeof($userData['termCourse']) <= 9; $i++) {
    echo "========loop" . $i . " " . date("m-d H:i:s", time()) . "========\n";
    $newData = getNew();
    if (sizeof($newData) > 0) {
        foreach ($newData as $newDatum) {
            $score = $newDatum['xqcj'];
            $jd = $newDatum['jd'];
            $title = "出分啦～" . $newDatum['kcmc'];
            $content = "成绩为 " . $newDatum['xqcj'];
            if ($newDatum['jd'] != '') {
                $content = $content . " ，绩点为 " . $newDatum['jd'];
            }
            $content = $content . "\n\n目前出分" . sizeof($userData['termCourse']) . "门课程，加油加油！";
            if ($score >= 90) {
                $content = $content . "\n\n考这么好，赏我点零花钱呗～，谢谢老爷！\n\n![支付二维码](https://tva1.sinaimg.cn/large/007S8ZIlly1gfz5heccq4j30l50spju3.jpg)";
            }
            sendMessage($title, $content);
        }
    }
    sleep($config['timeGap']);
}
