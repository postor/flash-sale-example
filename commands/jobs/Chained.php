<?php
namespace app\commands\jobs;

class Chained extends \yii\base\BaseObject implements \yii\queue\JobInterface{
  public $user;
  public $redo;
  
  public function execute($queue){
    echo "$this->user finished after retry $this->redo times (queue = $queue)\n";
  }
}