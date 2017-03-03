<?php
use QL\QueryList;
use function GuzzleHttp\json_decode;
require_once 'app.php';


$lgClass = new lg_login_class();

$positionCollection = (new MongoDB\Client)->lagou->position;
$commentCollection = (new MongoDB\Client)->lagou->comment;
$positionCount = $positionCollection->count();
$commentCount = $commentCollection->count();

var_dump($positionCount,$commentCount);exit();

for($i=1; $i<50; $i++){
    echo $i."\r\n";
    $stringBody = $lgClass->curlpositionAjaxJson('深圳','php',$i);
    $jsonArray = json_decode($stringBody,true);
    if(isset($jsonArray['content']['positionResult']['result']) && !empty($jsonArray['content']['positionResult']['result'])){
        $nowInsertAry = [];
        foreach ($jsonArray['content']['positionResult']['result'] as $key=>$val){
            
            usleep(200000);
            
            //采集详情页
            $positionHtml = $lgClass->getCurlpositionHtml($val['positionId']);
            file_put_contents(DIR.'s.html', $positionHtml);
            
            //获取页面上的动态token
            preg_match('/window.X_Anti_Forge_Token = \'(.*?)\'/',$positionHtml,$tokenmatches);
            preg_match('/window.X_Anti_Forge_Code = \'(.*?)\'/',$positionHtml,$codematches);
            
            //获取 详情页面里招聘详情和详细地址
            $containerQuery = QueryList::Query($positionHtml, [
                'container'=>['#container .job_bt div','text'],//详情
                'work_addr'=>['#container .job-address .work_addr','text'],//详细地址
            ]);
            $jobContentsArray = $containerQuery->data;
            
            $document = $positionCollection->findOne(['positionId' => $val['positionId']]);
            if(!$document){
                if(isset($jobContentsArray[0]['container'])){
                    $val['job_contents'] = str_replace(array("\r\n", "\r", "\n"," "), "",$jobContentsArray[0]['container']);
                    $val['job_address'] = str_replace(array("\r\n", "\r", "\n"," "), "",$jobContentsArray[0]['work_addr']);
                }
                
                $nowInsertAry []= $val;
            }
            
            if(isset($tokenmatches[1]) && !empty($tokenmatches[1])){
                //采集招聘评论json
                $posCommentjson = $lgClass->getPositionComment($val['positionId'],$tokenmatches[1],$codematches[1]);
                if($posCommentjson){
                    $posCommentArray = json_decode($posCommentjson,true);
                    if(!empty($posCommentArray['content']['data']['data']['result'])){
                        $insertManyResult = $commentCollection->insertMany($posCommentArray['content']['data']['data']['result']);
                        $success = $insertManyResult->getInsertedCount();
                        echo "成功写入评论{$success}条数据!!\r\n";
                    }
                }
            }
        }
        
        if(!empty($nowInsertAry))
        {
            $insertManyResult = $positionCollection->insertMany($nowInsertAry);
            $success = $insertManyResult->getInsertedCount();
            echo "成功写入{$success}条数据!!\r\n";
            $nowInsertAry = [];
        }
    }
    sleep(2);
}

