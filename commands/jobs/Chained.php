<?php
namespace app\commands\jobs;

class Chained extends \yii\base\Object implements \zhuravljov\yii\queue\Job{
  public $user;
  public $redo;
  
  public function run(){
    echo "$this->user finished after retry $this->redo times \n";
  }
}