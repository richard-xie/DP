<?php
namespace Home\Controller;
use Think\Controller;
class AjaxdzController extends CommonController {
  private function checktype($value){
      $array = array(1,2,3);
      if(in_array($value, $array)){
        return true;
      }else{
        return false;
      }
  }

  private function checkfarmid($value){
      if($value>=1 && $value<=15){
        return true;
      }else{
        return false;
      }
  }


  private function land_max($type){
      $config=D('config');
      switch ($type) {
        case '1':
          $max_guozi=$config->getValue('huang_max');
          break;
        case '2':
          $max_guozi=$config->getValue('hong_max');
          break;
        case '3':
          $max_guozi=$config->getValue('hei_max');
          break;
        case '4':
          $max_guozi=$config->getValue('tiyan_max');
          break;
        default:
          $max_guozi=false;
          break;
      }

      return $max_guozi;
  }

	public function kaiken(){
          if(!IS_AJAX){
            return false;
          }
          $id=I('post.id',0,'intval');//
         if($id==8 || $id==36){
           $type=1;
         }elseif ($id==10) {
           $type=2;
         }elseif ($id==12) {
           $type=3;
         }else{
            ajaxReturn("error");
         }

          $config=D('config');
          $min_guozi=$config->where(array('id'=>$id))->getField('value');
          

          $db_store=M('Store');
          $userid=session('userid');

          $guoNum=$db_store->where(array("uid"=>$userid))->getField('cangku_num');

          if ($guoNum < $min_guozi) { 
          	ajaxReturn("no enough");
          }

          $des_num=$min_guozi;
          $is_land=no_land();
          if($is_land && $id!=36){
            $des_num=$des_num+30;
            if ($guoNum < $des_num) { 
              ajaxReturn("error");
            }
          }

          $db_farm=M('nzusfarm');
        	$db_farm->startTrans();

        	$where['uid']=$userid;
        	$where['farm_type']=$type;
          $where['status']=0;
          $farm=$db_farm->where($where)->order('f_id asc')->field('id,f_id')->find();
          if(empty($farm)){
              ajaxReturn('error');
              return false;
          }



        	$data['status']=1;
          $data['seeds']=$min_guozi;
          if($id==36){
            $data['farm_type']=4;
          }
        	$res=$db_farm->where(array('id'=>$farm['id']))->save($data);//
          if($res===false){
            $db_farm->rollback();
            ajaxReturn('error');
          }

        	//
          $s_data = array(
            'cangku_num' => array('exp', '`cangku_num`-'.$des_num),
            'plant_num' => array('exp', '`plant_num`+'.$min_guozi),
          );

        	$res=$db_store->where(array('uid'=>$userid))->save($s_data);
          if($res===false){
            $db_farm->rollback();
            ajaxReturn('error');
          }

          if($is_land && $id!=36){
            $pid=M('user')->where(array('userid'=>$userid))->getField('pid');
            if($pid>0){
              $db_store->where(array('uid'=>$pid))->setInc('cangku_num',20);
            }
          }

          $databz['uid']=$userid;
          $databz['farm_id']=$farm['f_id'];
          $databz['num']=$min_guozi;
          $databz['farm_type']=$type;
          $databz['sow_time']=time();
          $k_res=M('kaidi')->data($databz)->add();

          M('user_level')->where(array("uid"=>$userid))->setInc('land_num',1);
          AddUserLevel($userid);

        	if($res) {
        	   $db_farm->commit();
	           ajaxReturn('success',1);
        	}else{
        	   $db_farm->rollback();
	           ajaxReturn('error');
        	}

	}
  


   public function bozhong(){  

         if(!IS_AJAX){
            return false;
          }
          $farmid=I('post.farmid',0,'intval'); 
          $fid=I('post.id',0,'intval'); 
          if(empty($fid) ||  empty($farmid)){
              ajaxReturn('error');
          }


          $bz_num=I('post.bz_num');
          if(!is_numeric($bz_num) || $bz_num<=0)
          {
              ajaxReturn('error');
          }


          $db_farm=M('nzusfarm');
          $db_store=M('store'); 
          $userid=session('userid');


          $cangku_num=$db_store->where(array("uid"=>$userid))->getField('cangku_num');
          if ($cangku_num < $bz_num) {
             ajaxReturn('error'); 
             return;
          }


          $fwhere['uid']=$userid;
          $fwhere['id']=$fid;
          $fwhere['status']=array('gt',0);
          $farm=$db_farm->where($fwhere)->field('seeds,fruits,farm_type')->find();
          if(empty($farm)){
              ajaxReturn('error');
              return;
          }

          
          $type=$farm['farm_type'];
          $max_guozi=$this->land_max($type);
          if(!$max_guozi){
             ajaxReturn('error');
          }


          $farmgz=$farm['seeds']+$farm['fruits']+$bz_num;
          if($farmgz>$max_guozi) {
             ajaxReturn('error'); 
             return;
          }

          
           
          $db_store->startTrans();
          $data=array();
          $data['status']=2;
          $data['fruits']=array('exp', '`fruits`+'.$bz_num);
          $res=$db_farm->where($fwhere)->save($data);
          if(!$res){
            $db_store->rollback();
            ajaxReturn('error'); 
          }
          $s_data = array(
            'cangku_num' => array('exp', '`cangku_num`-'.$bz_num),
            'plant_num' => array('exp', '`plant_num`+'.$bz_num),
          );
          $res=$db_store->where(array('uid'=>$userid))->save($s_data);
          if($res===false){
            $db_store->rollback();
            ajaxReturn('error');
          }
         
          $databz['uid']=$userid;
          $databz['farm_id']=$farmid;
          $databz['sow_num']=$bz_num;
          $databz['farm_type']=$type;
          $databz['sow_type']="add";
          $databz['sow_time']=time();
          $bzjl=M('sow')->data($databz)->add();  
          
          if ($res) {
          	 $db_store->commit();
	           ajaxReturn('success',1,$type,$farmgz); 
          }else{
          	 $db_store->rollback();
	           ajaxReturn('error'); 
	           return;
          }
   }

   public function cleartree(){
      if(!IS_AJAX)
        return ;

     $id=I('post.id',0,'intval'); 
      $db_farm=M('nzusfarm');

      $userid=session('userid');
      $fwhere['uid']=$userid;
      $fwhere['id']=$id;
      $fwhere['status']=3;
      $farm=$db_farm->where($fwhere)->field('seeds,fruits')->find();
      if(empty($farm)){
          ajaxReturn('error');
      }
      $fruits=$farm['fruits'];
      $db_store=M('store'); 
      $db_store->startTrans();
      if($fruits>0){
          $s_data = array(
            'cangku_num' => array('exp', '`cangku_num`+'.$fruits),
            'plant_num' => array('exp', '`plant_num`-'.$fruits),
          );
          $res=$db_store->where(array('uid'=>$userid))->save($s_data);
          if($res===false){
            $db_store->rollback();
            ajaxReturn('error');
          }
      }

      $data['status']=0;
      $data['seeds']=0;
      $data['fruits']=0;
      $data['income']=0;
      $res=$db_farm->where($fwhere)->save($data);
      if($res){
          $db_store->commit();
          ajaxReturn('success',1);
      }else{
        $db_store->rollback();
        ajaxReturn('error');
      }

   }



    public function shouge(){
        if(!IS_AJAX){
            return false;
        }

        $farmid=I('post.farmid',0,'intval'); 
        $fid=I('post.id',0,'intval'); 
        if(empty($fid) || empty($farmid)){
            ajaxReturn('error');
        }


          $db_farm=M('nzusfarm');
          $db_store=M('store'); 
          $userid=session('userid');

          $db_store->startTrans();
          $fwhere['uid']=$userid;
          $fwhere['id']=$fid;
          $fwhere['status']=array('IN',array(2,3));
          $frarm=$db_farm->where($fwhere)->field('fruits,seeds,status')->find();
          $fruits=$frarm['fruits'];
          if(empty($frarm) || $fruits<0){
             ajaxReturn('error！');
          }
          if($fruits<=0){
              ajaxReturn('error！');
          }
          $s_data = array(
            'cangku_num' => array('exp', '`cangku_num`+'.$fruits),
            'plant_num' => array('exp', '`plant_num`-'.$fruits),
          );
          $res=$db_store->where(array('uid'=>$userid))->save($s_data);
          if($res===false){
            $db_store->rollback();
            ajaxReturn('error');
          }

          $fruits_data['fruits']=0;
          if($frarm['status']!=3){
            $fruits_data['status']=1;
          }
          $res=$db_farm->where($fwhere)->save($fruits_data);
          if(!$res){
            $db_store->rollback();
            ajaxReturn('error');
          }
         
          $datasg['uid']=$userid;
          $datasg['farm_id']=$farmid;
          $datasg['shouge_num']=$fruits;
          $datasg['farm_type']=0;  
          $datasg['shouge_time']=time();
          M('shouge')->data($datasg)->add(); 

          if ($res) {
               $db_store->commit();
               ajaxReturn('success',1,'',$frarm['seeds']+0); 
          }else{

               $db_store->rollback();
               ajaxReturn('error'); 
               return;
          }

   }


   public function shifei(){

        $userid=session('userid');

        $reg_date=M('user')->where(array('uid'=>$userid))->getField('reg_date');
        $reg_date=date('Ymd',$reg_date);
        $date=date('Ymd');
        if($reg_date==$date){
          ajaxReturn('error',2); 
        }
        //
        $h_where['uid']=$userid;
        $h_where['datestr']=date('Ymd');
        $user_huafei=M('user_huafei');
        $count=$user_huafei->where($h_where)->count(1);
        if($count>0){
          ajaxReturn('error',2); 
        }

        $store=M('store');
        $nzusfarm=M('nzusfarm');
        $f_where['uid']=$userid;
        $f_where['status']=array('IN',array(1,2));
        $f_info=$nzusfarm->where($f_where)->field('id,uid,seeds,fruits,farm_type,income')->order('f_id')->select();

        if(!isset($f_info) || empty($f_info)){
          ajaxReturn('error'); 
        }

        $total_lv=$this->chaifen_lv($userid);

        $nzusfarm->startTrans();
        $huafei_total=0;
        $shop_num=0; //
        $config=D('Config');
       foreach ($f_info as $k => $val) {

          $total=0;
          $num=0;
          $total=$val['seeds']+$val['fruits'];
          $max_num=$config->getMaxLand($val['farm_type']);
          if($total>=$max_num){
            continue;
          }

          $num=$total*$total_lv*0.8;
          $shop_num=$shop_num+$total*$total_lv*0.2;
          if($num>0){
            $huafei_total=$huafei_total+$num;
            $data=array();
            $data['fruits']=array('exp','`fruits` + '.$num);
            $data['income']=array('exp','`income` + '.$num);
            $data['status']=2;
            if(!$nzusfarm->where(array('id'=>$val['id'],'status'=>array('gt',0)))->save($data)){
              $nzusfarm->rollback();
              ajaxReturn('error'); 
            }

            $diemax=$val['seeds']*10;
            $die_num=$val['income']+$num;
            if($die_num>=$diemax){
              $nzusfarm->where(array('id'=>$val['id'],'status'=>array('gt',0)))->setField('status',3);
              continue;
            } 
          }

       }

       if($huafei_total>0)
       {
           $where['uid']=$userid;
           $s_data['plant_num']=array('exp','`plant_num` + '.$huafei_total);
           $s_data['huafei_total']=array('exp','`huafei_total` + '.$huafei_total);
           $res=$store->where($where)->save($s_data);
           if(!$res){
              $nzusfarm->rollback();
              ajaxReturn('error'); 
           }
       }

       //
       if($shop_num>0){
          $u_where['userid']=$userid;
          $shop_user=M('users','sp_');
          $count=$shop_user->where($u_where)->count(1);
          if($count==0){
            $udata=session('user_login');
            $udata['userid']=$userid;
            $udata['c_money']=$shop_num;
            $shop_user->add($udata);
          }else{
            $shop_user->where($u_where)->setInc('c_money',$shop_num);
          }
       }

       $this->huafeidetail($userid,$huafei_total);

        $data['huafei_num']=$huafei_total;
        $data['datestr']=date('Ymd');
        $data['uid_str']='';
        $data['pid_caimi']=0;
        $data['gid_caimi']=0;
        $data['ggid_caimi']=0;
        $res=$user_huafei->where(array('uid'=>$userid))->save($data);
        if($res){
            $nzusfarm->commit();
            ajaxReturn('success！',1); 
        }else{
            $nzusfarm->rollback();
            ajaxReturn('error'); 
        }

   }


   private function chaifen_lv($userid){

      $config=D('Config')->getVl();

      $UserTool=M('user_level')->field('nongfu_num,niao_num,ji_num,mao_num')->where(array('uid'=>$userid))->find();

      $nongfu_num=($config['nongfu_lv']/100)*$UserTool['nongfu_num'];
      $niao_num=($config['niao_lv']/100)*$UserTool['niao_num'];
      $ji_num=($config['ji_lv']/100)*$UserTool['ji_num'];
      $mao_num=($config['mao_lv']/100)*$UserTool['mao_num'];
      $base_num=$config['base_lv']/100;

      $total_lv=$base_num+$nongfu_num+$niao_num+$ji_num+$mao_num;
      if($total_lv<=0){
          $total_lv=0;
      }

      return $total_lv;
   }

   private function huafeidetail($userid,$huafei_num){

        $datasf['uid']=$userid;
        $datasf['farm_id']=0;
        $datasf['shifei_num']=$huafei_num;
        $datasf['tudi_type']=0;  
        $datasf['shifei_time']=time();
        $sfjl=M('shifeijl')->data($datasf)->add();   

   }


}
