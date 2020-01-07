<?php
namespace app\commands\jobs;

class Order extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
  const KEY_STORE_LEFT = 'storeleft';  
  const KEY_STORE_TOTAL = 'storetotal';  
  const KEY_ORDER_RECORD = 'orders';
  const KEY_SUCCEED_RECORD = 'succeeds';
  public $user;
  public $redo = 0;
  
  public function execute($queue)
  {
    try{
      //已经成功的扔掉
      $userOrder = self::getUserOrder($this->user);
      if($userOrder && isset($userOrder['succeed']) && $userOrder['succeed']){
        echo 'already succeed, drop'."\n";
        return;
      }

      
      $left = self::getStore();
      
      echo 'check left - '.$left."\n";
      if($left<=0){
        $this->makeMisOrder($left);   
        return; 
      }
      $left = \Yii::$app->redis->decr(self::KEY_STORE_LEFT);

      if(intval($left)<0){
        $this->makeMisOrder($left);    
      }else{
        $this->makeOrder($left);  
      }

      \Yii::$app->queue_chained->push(new Chained([
          'user' => $this->user,
          'redo' => $this->redo,
      ]));
    }catch(\Exception $e){      
      echo $e;
      \Yii::$app->queue->push(new Order([
          'user' => $this->user,
          'redo' => $this->redo+1,
      ]));
    }
  }

  private function makeOrder($left){    
    $redis = \Yii::$app->redis;
    $redis->hset(self::KEY_ORDER_RECORD,$this->user,json_encode([
      'succeed'=>true,
      'storeLeft'=>$left,
      'queueing'=>false,
      'pid'=>getmypid(),
    ]));
    $redis->hset(self::KEY_SUCCEED_RECORD,$this->user,json_encode([
      'succeed'=>true,
      'storeLeft'=>$left,
      'queueing'=>false,
      'pid'=>getmypid(),
    ]));
    echo 'succeed!'."\n";
  }

  private function makeMisOrder($left){   
    $redis = \Yii::$app->redis;
    $redis->hset(self::KEY_ORDER_RECORD,$this->user,json_encode([
      'succeed'=>false,
      'storeLeft'=>$left,
      'queueing'=>false,
      'pid'=>getmypid(),
    ]));    
    echo 'fail, store empty!'."\n";
  }

  /** 添加一个等待中的用户  */
  public static function enqueue($user){
    if(self::getUserOrder($user)){
      return false;
    }
    $redis = \Yii::$app->redis;
    $redis->hset(self::KEY_ORDER_RECORD,$user,json_encode([
      'queueing'=>true,
    ])); 
    return true;  
  }

  /** 增加库存  */
  public static function addStore($add,$flushdb=false){
    $redis = \Yii::$app->redis;
    if($flushdb) $redis->flushdb();
    if(!$add) $add = 0;
    $left =  $redis->incrby(self::KEY_STORE_LEFT,$add);   
    $redis->incrby(self::KEY_STORE_TOTAL,$add); 
    return $left;
  }

  /** 获取库存  */
  public static function getStore(){
    $redis = \Yii::$app->redis;
    $left = $redis->get(self::KEY_STORE_LEFT);   
    return intval($left);
  }

  /** 获取用户的order  */
  public static function getUserOrder($user){
    $redis = \Yii::$app->redis;
    $userOrder = $redis->hget(self::KEY_ORDER_RECORD,$user);
    if(empty($userOrder)) return null;
    return json_decode($userOrder,true);
  }

  /** 排队的人是不是比总数多了？ */
  public static function isOverQueue(){    
    $redis = \Yii::$app->redis;
    $queueSize = $redis->hlen(self::KEY_ORDER_RECORD);
    $storeTotal = $redis->get(self::KEY_STORE_TOTAL); 
    return intval($queueSize)>intval($storeTotal);
  }

  /** 查看排队中的人（排队写入成功，任务写入失败情况） */
  public static function getAllQueueing(){    
    $redis = \Yii::$app->redis;
    $keys = $redis->hkeys(self::KEY_ORDER_RECORD);
    $queueings = [];
    foreach($keys as $key){
      if($order = self::getUserOrder($key)){ //&& isset($order['queueing']) && $order['queueing']
        if(isset($order['queueing'])&& $order['queueing']){
          $order['user'] = $key;
          $queueings[] = $order;
        }
      }
    }
    return $queueings;
  }
}