<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionOrder($user=''){
        //检查用户订单
        $order = \app\commands\jobs\Order::getUserOrder($user);
        if($order){
            if(isset($order['succeed'])){
                if($order['succeed']){
                    return '买到了！';
                }else{
                    return '差一点就买到了（晚了一点）';
                }
            }
            
            $left = \app\commands\jobs\Order::getStore();
            if($left<0){
                return '差一点就买到了（推任务时缓冲区满了）';
            }else{
                \Yii::$app->queue->push(new \app\commands\jobs\Order([
                    'user' => $user,
                ]));
                return '排队中。。。（推任务时缓冲区满，再次提交重新推任务）';
            }
            return json_encode($order);
        }


        //检查库存
        $left = \app\commands\jobs\Order::getStore();
        if($left<=0){
            return '已经无货了';
        }

        //检查排队过多
        if(\app\commands\jobs\Order::isOverQueue()){
            return '排队的人已经过多，不用排队了';
        }
    
        //排队
        \app\commands\jobs\Order::enqueue($user);
        \Yii::$app->queue->push(new \app\commands\jobs\Order([
            'user' => $user,
        ]));

        //显示
        return $user;
    }

    public function actionStore($add=''){        
        return \app\commands\jobs\Order::addStore($add,true);
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionShow(){
        return json_encode(\app\commands\jobs\Order::getAllQueueing());
    }
    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
