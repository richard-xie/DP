<?php
namespace Home\Controller;
use Think\Controller;
class GrowthController extends CommonController {

    public function StealDeatail(){
        if(!IS_AJAX){
            return false;
        }
        $userid=session('userid');
        $m=M('steal_detail');
        $where['uid']=$userid;

        $p = I('p','0','intval');
        $page=$p*10;
        $arr=$m->field("num s_num,username uname,type_name,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i') as tt ")->where($where)->order('id desc')->limit(
            $page,10)->select();
       if(empty($arr)){
               $arr=null; 
        }
        $this->ajaxReturn($arr);
    }

    public function Intro(){
        $time = time();
        $userid = session('userid');
		$u_ID = $userid;
		$drpath = './Uploads/Rcode';
		$imgma = 'codes' . $userid . '.png';
		$urel = '/Uploads/Rcode/' . $imgma.'?v='.time();
		if (!file_exists($drpath . '/' . $imgma)) {
            sp_dir_create($drpath);
            vendor("phpqrcode.phpqrcode");
            $phpqrcode = new \QRcode();
            $size = "7";
            $errorLevel = "L";
            $phpqrcode->png($hurl, $drpath . '/' . $imgma, $errorLevel, $size);
        }
		$this->urel = $urel;
        $this->display();
    }
    public function test(){
        $filename = $_GET['filename'];
        ob_end_clean();


        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($filename));
        header('Content-Disposition: attachment; filename=' . basename($filename));

        readfile($filename);
        echo "<script>alert('success')</script>";
    }

    public function Introrecords(){
        $uid = session('userid');
        $where['get_id'] = $uid;
        $where['get_type'] = 0;
        $Chan_info = M('tranmoney')->where($where)->order('id desc')->select();
        $this->assign('Chan_info',$Chan_info);
        $this->assign('uid',$uid);
        $this->display();
    }


 public function quxiao_order(){

    $id = (int)I('id','intval',0);
    $uid = session('userid');
    $mydeal = M('trans')->where(array("id"=>$id,"payin_id|payout_id"=>$uid,"pay_state"=>array("lt",2)))->find();

     if(!$mydeal)ajaxReturn('error',0);

    $type=$mydeal["trans_type"];
    M('trans_quxiao')->add($mydeal);//


    if($type==0){//

            $payout['payin_id'] =0;
            $payout['pay_state'] =0;
            $res1 = M('trans')->where(array('id'=>$id))->save($payout); 


    }elseif($type==1){//

        $res1 = M('trans')->delete($id); 


    }

        if($res1){       
        ajaxReturn('error',1);
        }else{
        ajaxReturn('error',1);
        }
}


    public function Purchase(){
        $close1=is_close_trade();
        if($close1['value']==0){
          success_alert($close1['tip'],U('Home/index/index'));
        }

         $uid = session('userid');
        $cid = trim(I('cid'));
        if(empty($cid)){
            $mapcas['user_id&is_default'] =array($uid,1,'_multi'=>true);
            $carinfo = M('ubanks')->where($mapcas)->count(1);
            if($carinfo < 1){
                $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid' )->where(array('u.user_id'=>$uid))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
            }else{
                $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid' )->where(array('u.user_id'=>$uid,'is_default'=>1))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
            }
        }else{
            $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid' )->where(array('u.id'=>$cid))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
        }

        if(IS_AJAX){
            $pwd = trim(I('pwd'));
            $sellnums = trim(I('sellnums'));
            $cardid = trim(I('cardid'));//id
            $messge = trim(I('messge'));//
            $sellAll = array(500,1000,3000,5000,10000,30000);
            if (!in_array($sellnums, $sellAll)) {
                ajaxReturn('error',0);
            }
            $id_Uid = M('ubanks')->where(array('id'=>$cardid))->getField('user_id');
            if($id_Uid != $uid){
                ajaxReturn('error',0);
            }
            $minepwd = M('user')->where(array('userid'=>$uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
            $user_object = D('Home/User');
            $user_info = $user_object->Trans($minepwd['account'], $pwd);
            $data['pay_no'] = build_order_no();
            $data['payin_id'] = $uid;
            $data['out_card'] = $cardid;
            $data['pay_nums'] = $sellnums;
            $data['trade_notes'] = $messge;
            $data['pay_time'] = time();
            $data['trans_type'] = 1;
            $res_Add = M('trans')->add($data);
            if($res_Add){
                ajaxReturn('success',1);
            }
        }
        $this->assign('morecars',$morecars);
        $this->display();

    }

    public function test1(){
        $sellnums = array(500,1000,3000,5000,10000,30000);
        $sellnums = 5000;
        $sellAll = array(500,1000,3000,'5000',10000,30000);
        if (!in_array(500, $sellAll)) {
            echo "Got Irix";
        }
    }
    /**
     *
     */
    public function Addbank(){
        $bakinfo = M('bank_name')->order('q_id asc')->select();
        $this->assign('bakinfo',$bakinfo);
        if(IS_AJAX){
            $uid = session('userid');
            $crkxm = I('crkxm');
            $khy = I('khy');
            $yhk = I('yhk');
            $khzy = I('khzy');

            if(empty($crkxm)){
                ajaxReturn('error',0);
            }
            if(empty($khy)){
               ajaxReturn('error',0);
            }
            if(empty($yhk)){
                ajaxReturn('error',0);
            }
            if(empty($khzy)){
                ajaxReturn('error',0);
            }

            $data['hold_name'] = $crkxm;
            $data['card_id'] = $khy;
            $data['card_number'] = $yhk;
            $data['open_card'] = $khzy;
            $data['add_time'] = time();
            $data['user_id'] = $uid;

            $res_addcard = M('ubanks')->add($data);
            if($res_addcard){
                $bank_uname = M('user')->where(array('userid'=>$uid))->getField('bank_uname');
                if(empty($bank_uname)){
                    M('user')->where(array('userid'=>$uid))->setField('bank_uname',$crkxm);
                }
                    ajaxReturn('error',1,'/Growth/Purchase');
            }
        }
        $this->display();
    }
    public function Nofinsh(){
        $state = trim(I('state'));
        $uid = session('userid');
        $traInfo = M('trans');
        if($state > 0){
            $where['pay_state'] =  array('between','1,2');
        }else{
            $where['pay_state'] = 0;
        }
        $where['payin_id'] = $uid;

        $p=getpage($traInfo,$where,20);
        $page=$p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        $banks = M('ubanks');
        foreach($orders as $k =>$v){
            if($v['payin_id'] != ''){
                $bankinfos = $banks ->where(array('id'=>$v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
                $uinfomsg = M('user')->where(array('userid'=>$v['payout_id']))->Field('username,mobile')->find();
                $orders[$k]['cardnum'] = $bankinfos['card_number'];
                $orders[$k]['bname'] = M('bank_name')->where(array('q_id'=>$bankinfos['card_id']))->getfield('banq_genre');
                $orders[$k]['openrds'] = $bankinfos['open_card'];
                $orders[$k]['uname'] = $uinfomsg['username'];
                $orders[$k]['umobile'] = $uinfomsg['mobile'];

            }
        }
        $this->assign('state',$state);
        $this->assign('orders',$orders);
        $this->assign('page',$page);
        $this->display();
    }
    public function Conpay(){
        $uid = session('userid');
        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payin_id'] = $uid;
        $where['pay_state'] = 1;
        $p=getpage($traInfo,$where,20);
        $page=$p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        foreach($orders as $k =>$v){
            $bankinfos = $banks ->where(array('id'=>$v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid'=>$v['payout_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id'=>$bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $uinfomsg['username'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
        }
        if(IS_AJAX){
            $uid = session('userid');
            $picname = $_FILES['uploadfile']['name'];
            $picsize = $_FILES['uploadfile']['size'];
            $trid = trim(I('trid'));

            if($trid <= 0){
                ajaxReturn('error',0);
            }
            if ($picname != "") {
                if ($picsize > 2014000) { //
                    ajaxReturn('error',0);
                }
                $type = strstr($picname, '.'); //
                if ($type != ".gif" && $type != ".jpg" && $type != ".png"  && $type != ".jpeg") {
                    ajaxReturn('error',0);
                }
                $rand = rand(100, 999);
                $pics = uniqid() . $type; //
                //
                $pic_path = "./Uploads/Payvos/". $pics;
                move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
            }
            $size = round($picsize/1024,2); //
            $pic_path = trim($pic_path,'.');
            if($size){
                $res = M('trans')->where(array('id'=>$trid))->setField(array('trans_img'=>$pic_path,'pay_state'=>2));
                if($res){
                    ajaxReturn('success',1,'/Growth/Conpay');
                }else{
                    ajaxReturn('error',0);
                }
            }
        }
        $this->assign('page',$page);
        $this->assign('orders',$orders);
        $this->display();
    }

    public function Paidimg(){
        $id = I('id');
        $imginfo = M('trans')->where(array('id'=>$id))->getField('trans_img');
        $this->assign('imginfo',$imginfo);

        $this->display();
    }

    public function Dofinsh(){
        $uid = session('userid');
        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payin_id'] = $uid;
        $where['pay_state'] = 3;
        $p=getpage($traInfo,$where,20);
        $page=$p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        foreach($orders as $k =>$v){
            $bankinfos = $banks ->where(array('id'=>$v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid'=>$v['payout_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id'=>$bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $uinfomsg['username'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
        }
        $this->assign('page',$page);
        $this->assign('orders',$orders);
        $this->display();
    }

    public function Buyrecords(){
        $traInfo = M('trans');
        $uid = session('userid');
        $where['payin_id'] = $uid;
        $p=getpage($traInfo,$where,20);
        $page=$p->show();
        $Chan_info = $traInfo->where($where)->order('id desc')->select();
        foreach ($Chan_info as $k =>$v){
            $Chan_info[$k]['username'] = M('user')->where(array('userid'=>$v['payout_id']))->getField('username');
            $Chan_info[$k]['get_timeymd'] = date('Y-m-d',$v['pay_time']);
            $Chan_info[$k]['get_timedate'] = date('H:i:s',$v['pay_time']);
        }
        if(IS_AJAX){
            if(count($Chan_info) >= 1) {
                ajaxReturn($Chan_info,1);
            }else{
                ajaxReturn('error',0);
            }
        }
        $this->assign('page',$page);
        $this->assign('Chan_info',$Chan_info);
        $this->assign('uid',$uid);
        $this->display();
    }


    public function Buycenter(){
        if(IS_AJAX){
            $pricenum = I('mvalue');
            if($pricenum == ''){
                ajaxReturn('error',0);
            }
            $order_info = M('trans as tr')->join('LEFT JOIN  ysk_user as us on tr.payout_id = us.userid')->where(array('tr.pay_state'=>0,'tr.trans_type'=>0,'tr.pay_nums'=>$pricenum))->order('id desc')->select();

            foreach($order_info as $k => $v){
                $order_info[$k]['cardinfo'] = M('bank_name')->where(array('q_id'=>$v['card_id']))->getfield('banq_genre');
                $order_info[$k]['spay'] = $v['pay_nums'] * 0.85;
            }
            if(count($order_info) <= 0){
                ajaxReturn('error',0);
            }else{
                ajaxReturn($order_info,1);
            }
        }
        $this->display();
    }

    public function Dopurs(){
        if(IS_AJAX){
            $uid = session('userid');
            $trid = I('trid',1,'intval');
            $pwd = trim(I('pwd'));
            $sellnums = M('trans')->where(array('id'=>$trid))->field('pay_nums,payout_id,pay_state')->find();

            $sellAll = array(500,1000,3000,5000,10000,30000);
            if (!in_array($sellnums['pay_nums'], $sellAll)) {
                ajaxReturn('error',0);
            }
            if($sellnums['payout_id'] == $uid){
                ajaxReturn('error~',0);
            }
            if($sellnums['pay_state'] != 0){
                ajaxReturn('error~',0);
            }
            $minepwd = M('user')->where(array('userid'=>$uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
            $user_object = D('Home/User');
            $user_info = $user_object->Trans($minepwd['account'], $pwd);
            $res_Buy = M('trans')->where(array('id'=>$trid))->setField(array('payin_id'=>$uid,'pay_state'=>1));
            if($res_Buy){

                ajaxReturn('success',1);
            }
        }
        $this->display();
    }
    public function Cardinfos(){
        $uid = session('userid');
        $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid' )->where(array('u.user_id'=>$uid))->order('u.id desc')->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre,banks.banq_img')->select();
        if(IS_AJAX){
            $cardid = I('bangid');
            $isuid = M('ubanks')->where(array('id'=>$cardid))->getField('user_id');
            if($isuid != $uid){
                ajaxReturn('error~',0);
            }
            $res = M('ubanks')->where(array('id'=>$cardid))->delete();
            if($res){
                ajaxReturn('error',1,'/User/Personal');
            }
        }
        $this->assign('morecars',$morecars);
        $this->display();
    }
}
