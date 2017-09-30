<?php
namespace Admin\Controller;

class UserController extends AdminController
{
	public function index($name = NULL, $field = NULL, $status = NULL)
	{
		//$this->checkUpdata();
		//$where = array();
		$where=" 1 ";
		if ($field && $name) {
			//$where[$field] = $name;
			if($field=="awardid" &&($name==7 || $name==9)){
				$where = " (`awardid`=7 or `awardid`=9) ";
			}else{
				$where = "`".$field."`='".$name."'";
			}

		}

		if ($status) {
			if($status>2){
				switch($status){
					case "3":
						$where = $where." and `awardstatus`=1 ";
						break;
					case "4":
						$where = $where." and `awardstatus`=0 ";
						break;
					case "5":
						$where = $where." and `idcardauth`=1 ";
						break;
					case "6":
						$where = $where." and `idcardauth`=0 ";
						break;
				}

			}else{

				$where = $where." and `status`=".($status-1);
			}
		}

		$count = M('User')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('User')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['invit_1'] = M('User')->where(array('id' => $v['invit_1']))->getField('username');
			$list[$k]['invit_2'] = M('User')->where(array('id' => $v['invit_2']))->getField('username');
			$list[$k]['invit_3'] = M('User')->where(array('id' => $v['invit_3']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function edit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$user = M('User')->where(array('id' => trim($id)))->find();
				$this->data = $user;
			}
			
			$imgstr = "";
			if($user['idcardimg1']){
				$img_arr = array();
				$img_arr = explode("_",$user['idcardimg1']);

				foreach($img_arr as $k=>$v){
					$imgstr = $imgstr.'<img src="/Upload/idcard/'.$v.'"  style="width:200px;height:100px;" />';
				}

				unset($img_arr);
			}
			
			$this->assign('userimg', $imgstr);

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			switch($_POST['awardid']){
				case 0:
					$_POST['awardname']="无奖品";
					break;
				case 1:
					$_POST['awardname']="苹果电脑";
					break;
				case 2:
					$_POST['awardname']="华为手机";
					break;
				case 3:
					$_POST['awardname']="1000元现金";
					break;
				case 4:
					$_POST['awardname']="小米手环";
					break;
				case 5:
					$_POST['awardname']="100元现金";
					break;
				case 6:
					$_POST['awardname']="10元现金";
					break;
				case 7:
					$_POST['awardname']="1元现金";
					break;
				case 8:
					$_POST['awardname']="无奖品";
					break;
				case 9:
					$_POST['awardname']="1元现金";
					break;
				case 10:
					$_POST['awardname']="无奖品";
					break;
				default:
					$_POST['awardid']=0;
					$_POST['awardname']="无奖品";
			}
			
			
			
			if ($_POST['password']) {
				$_POST['password'] = md5($_POST['password']);
			}
			else {
				unset($_POST['password']);
			}

			if ($_POST['paypassword']) {
				$_POST['paypassword'] = md5($_POST['paypassword']);
			}
			else {
				unset($_POST['paypassword']);
			}

			$_POST['mobletime'] = strtotime($_POST['mobletime']);

            $flag = false;
            if (isset($_POST['id'])) {
                $rs = M('User')->save($_POST);
            } else {
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables ecshecom_user write , ecshecom_user_coin write ');
                $rs[] = $mo->table('ecshecom_user')->add($_POST);
                $rs[] = $mo->table('ecshecom_user_coin')->add(array('userid' => $rs[0]));
                $flag = true;
            }

			if ($rs) {
                if ($flag) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                }
                session('reguserId', $rs);
				$this->success('编辑成功！');
			}
			else {
                if ($flag) {
                    $mo->execute('rollback');
                }
				$this->error('编辑失败！');
			}
		}
	}

	public function status($id = NULL, $type = NULL, $moble = 'User',$awardid=0)
	{
		if (APP_DEMO) {
			//$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('请选择会员！');
		}

		if (empty($type)) {
			$this->error('参数错误！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		
		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
                $_where = array(
                    'userid' => $where['id'],
                );
                M('UserCoin')->where($_where)->delete();
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;
			
		case 'idauth': 
			$data = array('idcardauth' => 1, 'addtime' => time());
			break;
			
		case 'notidauth': 
			$data = array('idcardauth' => 0);
			break;
			
		case 'award';
		
			switch($awardid){
				case 0:
					$awardname="无奖品";
					break;
				case 1:
					$awardname="苹果电脑";
					break;
				case 2:
					$awardname="华为手机";
					break;
				case 3:
					$awardname="1000元现金";
					break;
				case 4:
					$awardname="小米手环";
					break;
				case 5:
					$awardname="100元现金";
					break;
				case 6:
					$awardname="10元现金";
					break;
				case 7:
					$awardname="1元现金";
					break;
				case 8:
					$awardname="无奖品";
					break;
				case 9:
					$awardname="1元现金";
					break;
				case 10:
					$awardname="无奖品";
					break;
				default:
					$awardid=0;
					$awardname="无奖品";
			}
			$data = array('awardstatus' => 0, 'awardid' => $awardid,'awardname'=>$awardname);
			
			break;
		
		default:
			$this->error('操作失败！');
		}
		
		
		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function admin($name = NULL, $field = NULL, $status = NULL)
	{
		$DbFields = M('Admin')->getDbFields();

		if (!in_array('email', $DbFields)) {
			M()->execute('ALTER TABLE `ecshecom_admin` ADD COLUMN `email` VARCHAR(200)  NOT NULL   COMMENT \'\' AFTER `id`;');
		}

		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Admin')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Admin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function adminEdit()
	{
		if (empty($_POST)) {
			if (empty($_GET['id'])) {
				$this->data = null;
			}
			else {
				$this->data = M('Admin')->where(array('id' => trim($_GET['id'])))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$input = I('post.');

			/* //if (!check($input['username'], 'username')) {
			//	$this->error('用户名格式错误！');
			//} */

			if ($input['nickname'] && !check($input['nickname'], 'A')) {
				$this->error('昵称格式错误！');
			}

			if ($input['password'] && !check($input['password'], 'password')) {
				$this->error('登录密码格式错误！');
			}

			if ($input['moble'] && !check($input['moble'], 'moble')) {
				$this->error('手机号码格式错误！');
			}

			if ($input['email'] && !check($input['email'], 'email')) {
				$this->error('邮箱格式错误！');
			}

			if ($input['password']) {
				$input['password'] = md5($input['password']);
			}
			else {
				unset($input['password']);
			}

			if ($_POST['id']) {
				$rs = M('Admin')->save($input);
			}
			else {
				$_POST['addtime'] = time();
				$rs = M('Admin')->add($input);
			}

			if ($rs) {
				$this->success('编辑成功！');
			}
			else {
				$this->error('编辑失败！');
			}
		}
	}

	public function adminStatus($id = NULL, $type = NULL, $moble = 'Admin')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function auth()
	{
		//$list = $this->lists('AuthGroup', array('module' => 'admin'), 'id asc');
		$authGroup = M('AuthGroup');
		$condition['module'] = 'admin';
		$list = $authGroup->order('id asc')->where($condition)->select();
		$list = int_to_string($list);
		$this->assign('_list', $list);
		$this->assign('_use_tip', true);
		$this->meta_title = '权限管理';
		$this->display();
	}

	public function authEdit()
	{
		if (empty($_POST)) {
			if (empty($_GET['id'])) {
				$this->data = null;
			}
			else {
				$this->data = M('AuthGroup')->where(array(
                    'module' => 'admin',
                    'type' => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
                ))->find((int) $_GET['id']);
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if (isset($_POST['rules'])) {
				sort($_POST['rules']);
				$_POST['rules'] = implode(',', array_unique($_POST['rules']));
			}

			$_POST['module'] = 'admin';
			$_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;
			$AuthGroup = D('AuthGroup');
			$data = $AuthGroup->create();

			if ($data) {
				if (empty($data['id'])) {
					$r = $AuthGroup->add();
				}
				else {
					$r = $AuthGroup->save();
				}

				if ($r === false) {
					$this->error('操作失败' . $AuthGroup->getError());
				}
				else {
					$this->success('操作成功!');
				}
			}
			else {
				$this->error('操作失败' . $AuthGroup->getError());
			}
		}
	}

	public function authStatus($id = NULL, $type = NULL, $moble = 'AuthGroup')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function authStart()
	{
		if (M('AuthRule')->where(array('status' => 1))->delete()) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function authAccess()
	{
		$this->updateRules();
		$auth_group = M('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
			))->getfield('id,id,title,rules');
		$node_list = $this->returnNodes();
		$map = array(
            'module' => 'admin',
            'type' => 2,//Common\Model\AuthRuleModel::RULE_MAIN,
            'status' => 1
        );
		$main_rules = M('AuthRule')->where($map)->getField('name,id');
		$map = array(
            'module' => 'admin',
            'type' => 1,//Common\Model\AuthRuleModel::RULE_URL,
            'status' => 1
        );
		$child_rules = M('AuthRule')->where($map)->getField('name,id');
		$this->assign('main_rules', $main_rules);
		$this->assign('auth_rules', $child_rules);
		$this->assign('node_list', $node_list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
		$this->meta_title = '访问授权';
		$this->display();
	}

	protected function updateRules()
	{
		$nodes = $this->returnNodes(false);
		$AuthRule = M('AuthRule');
		$map = array(
			'module' => 'admin',
			'type'   => array('in', '1,2')
			);
		$rules = $AuthRule->where($map)->order('name')->select();
		$data = array();

		foreach ($nodes as $value) {
			$temp['name'] = $value['url'];
			$temp['title'] = $value['title'];
			$temp['module'] = 'admin';

			if (0 < $value['pid']) {
				$temp['type'] = 1;//Common\Model\AuthRuleModel::RULE_URL;
			}
			else {
				$temp['type'] = 2;//Common\Model\AuthRuleModel::RULE_MAIN;
			}

			$temp['status'] = 1;
			$data[strtolower($temp['name'] . $temp['module'] . $temp['type'])] = $temp;
		}

		$update = array();
		$ids = array();

		foreach ($rules as $index => $rule) {
			$key = strtolower($rule['name'] . $rule['module'] . $rule['type']);

			if (isset($data[$key])) {
				$data[$key]['id'] = $rule['id'];
				$update[] = $data[$key];
				unset($data[$key]);
				unset($rules[$index]);
				unset($rule['condition']);
				$diff[$rule['id']] = $rule;
			}
			else if ($rule['status'] == 1) {
				$ids[] = $rule['id'];
			}
		}

		if (count($update)) {
			foreach ($update as $k => $row) {
				if ($row != $diff[$row['id']]) {
					$AuthRule->where(array('id' => $row['id']))->save($row);
				}
			}
		}

		if (count($ids)) {
			$AuthRule->where(array(
				'id' => array('IN', implode(',', $ids))
				))->save(array('status' => -1));
		}

		if (count($data)) {
			$AuthRule->addAll(array_values($data));
		}

		if ($AuthRule->getDbError()) {
			trace('[' . 'Admin\\Controller\\UserController::updateRules' . ']:' . $AuthRule->getDbError());
			return false;
		}
		else {
			return true;
		}
	}

	public function authAccessUp()
	{
		if (isset($_POST['rules'])) {
			sort($_POST['rules']);
			$_POST['rules'] = implode(',', array_unique($_POST['rules']));
		}

		$_POST['module'] = 'admin';
		$_POST['type'] = 1;//Common\Model\AuthGroupModel::TYPE_ADMIN;
		$AuthGroup = D('AuthGroup');
		$data = $AuthGroup->create();

		if ($data) {
			if (empty($data['id'])) {
				$r = $AuthGroup->add();
			}
			else {
				$r = $AuthGroup->save();
			}

			if ($r === false) {
				$this->error('操作失败' . $AuthGroup->getError());
			}
			else {
				$this->success('操作成功!');
			}
		}
		else {
			$this->error('操作失败' . $AuthGroup->getError());
		}
	}

	public function authUser($group_id)
	{
		if (empty($group_id)) {
			$this->error('参数错误');
		}

		$auth_group = M('AuthGroup')->where(array(
			'status' => array('egt', '0'),
			'module' => 'admin',
			'type'   => 1,//Common\Model\AuthGroupModel::TYPE_ADMIN
			))->getfield('id,id,title,rules');
		$prefix = C('DB_PREFIX');
/* 		$l_table = $prefix . 'ucenter_member';//Common\Model\AuthGroupModel::MEMBER;
		$r_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
		$model = M()->table($l_table . ' m')->join($r_table . ' a ON m.id=a.uid');
		$_REQUEST = array();
		$list = $this->lists($model, array(
			'a.group_id' => $group_id,
			'm.status'   => array('egt', 0)
			), 'm.id asc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status'); */

			
		$l_table = $prefix . 'auth_group_access';//Common\Model\AuthGroupModel::MEMBER;
		$r_table = $prefix . 'admin';//Common\Model\AuthGroupModel::AUTH_GROUP_ACCESS;
		$model = M()->table($l_table . ' a')->join($r_table . ' m ON m.id=a.uid');
		$_REQUEST = array();
		$list = $this->lists($model, array(
			'a.group_id' => $group_id,
			//'m.status'   => array('egt', 0)
			), 'a.uid desc', null, 'm.id,m.username,m.nickname,m.last_login_time,m.last_login_ip,m.status');
			
			
        int_to_string($list);
		
		//var_dump($list);
		
		$this->assign('_list', $list);
		$this->assign('auth_group', $auth_group);
		$this->assign('this_group', $auth_group[(int) $_GET['group_id']]);
		$this->meta_title = '成员授权';
		$this->display();
	}

	public function authUserAdd()
	{
		$uid = I('uid');

		if (empty($uid)) {
			$this->error('请输入后台成员信息');
		}

		if (!check($uid, 'd')) {
			$user = M('Admin')->where(array('username' => $uid))->find();

			if (!$user) {
				$user = M('Admin')->where(array('nickname' => $uid))->find();
			}

			if (!$user) {
				$user = M('Admin')->where(array('moble' => $uid))->find();
			}

			if (!$user) {
				$this->error('用户不存在(id 用户名 昵称 手机号均可)');
			}

			$uid = $user['id'];
		}

		$gid = I('group_id');

		if ($res = M('AuthGroupAccess')->where(array('uid' => $uid))->find()) {
			if ($res['group_id'] == $gid) {
				$this->error('已经存在,请勿重复添加');
			} else {
				$res = M('AuthGroup')->where(array('id' => $gid))->find();

				if (!$res) {
					$this->error('当前组不存在');
				}

				$this->error('已经存在[' . $res['title'] . ']组,不可重复添加');
			}
		}

		$AuthGroup = D('AuthGroup');

		if (is_numeric($uid)) {
			if (is_administrator($uid)) {
				$this->error('该用户为超级管理员');
			}

			if (!M('Admin')->where(array('id' => $uid))->find()) {
				$this->error('管理员用户不存在');
			}
		}

		if ($gid && !$AuthGroup->checkGroupId($gid)) {
			$this->error($AuthGroup->error);
		}

		if ($AuthGroup->addToGroup($uid, $gid)) {
			$this->success('操作成功');
		}
		else {
			$this->error($AuthGroup->getError());
		}
	}

	public function authUserRemove()
	{
		$uid = I('uid');
		$gid = I('group_id');

		if ($uid == UID) {
			$this->error('不允许解除自身授权');
		}

		if (empty($uid) || empty($gid)) {
			$this->error('参数有误');
		}

		$AuthGroup = D('AuthGroup');

		if (!$AuthGroup->find($gid)) {
			$this->error('用户组不存在');
		}

		if ($AuthGroup->removeFromGroup($uid, $gid)) {
			$this->success('操作成功');
		}
		else {
			$this->error('操作失败');
		}
	}

	public function log($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('UserLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function logEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$this->data = M('UserLog')->where(array('id' => trim($id)))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('ecshecom_user_log')->where(array('id'=>$id))->save($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (M()->table('ecshecom_user_log')->add($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }

		}
	}

	public function logStatus($id = NULL, $type = NULL, $moble = 'UserLog')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function qianbao($name = NULL, $field = NULL, $coinname = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		if ($coinname) {
			$where['coinname'] = trim($coinname);
		}

		$count = M('UserQianbao')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserQianbao')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function qianbaoEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$this->data = M('UserQianbao')->where(array('id' => trim($id)))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('ecshecom_user_qianbao')->where(array('id' => $id))->save($_POST)) {
                    $this->success('编辑成功！');
                } else {
                    $this->error('编辑失败！');
                }
            } else {
                if (M()->table('ecshecom_user_qianbao')->add($_POST)) {
                    $this->success('添加成功！');
                } else {
                    $this->error('添加失败！');
                }
            }
		}
	}

	public function qianbaoStatus($id = NULL, $type = NULL, $moble = 'UserQianbao')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function bank($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('UserBank')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserBank')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function bankEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$this->data = M('UserBank')->where(array('id' => trim($id)))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                if (M()->table('ecshecom_user_bank')->where(array('id' => $id))->save($_POST)) {
                    $this->success('编辑成功！');
                }
                else {
                    $this->error('编辑失败！');
                }
            } else {
                if (M()->table('ecshecom_user_bank')->add($_POST)) {
                    $this->success('添加成功！');
                }
                else {
                    $this->error('添加失败！');
                }
            }
		}
	}

	public function bankStatus($id = NULL, $type = NULL, $moble = 'UserBank')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function coin($name = NULL, $field = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		$count = M('UserCoin')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserCoin')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function coinEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$this->data = M('UserCoin')->where(array('id' => trim($id)))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}
			
            if ($id) {
                if (M('UserCoin')->save($_POST)) {
                    $this->success('编辑成功！');
                }
                else {
                    $this->error('编辑失败！');
                }
            } else {
                if (M()->table('ecshecom_user_coin')->add($_POST)) {
                    $this->success('添加成功！');
                }
                else {
                    $this->error('添加失败！');
                }
            }
		}
	}

	public function coinLog($userid = NULL, $coinname = NULL)
	{
		$data['userid'] = $userid;
		$data['username'] = M('User')->where(array('id' => $userid))->getField('username');
		$data['coinname'] = $coinname;
		$data['zhengcheng'] = M('UserCoin')->where(array('userid' => $userid))->getField($coinname);
		$data['dongjie'] = M('UserCoin')->where(array('userid' => $userid))->getField($coinname . 'd');
		$data['zongji'] = $data['zhengcheng'] + $data['dongjie'];
		$data['chongzhicny'] = M('Mycz')->where(array(
			'userid' => $userid,
			'status' => array('neq', '0')
			))->sum('num');
		$data['tixiancny'] = M('Mytx')->where(array('userid' => $userid, 'status' => 1))->sum('num');
		$data['tixiancnyd'] = M('Mytx')->where(array('userid' => $userid, 'status' => 0))->sum('num');

		if ($coinname != 'cny') {
			$data['chongzhi'] = M('Myzr')->where(array(
				'userid' => $userid,
				'status' => array('neq', '0')
				))->sum('num');
			$data['tixian'] = M('Myzc')->where(array('userid' => $userid, 'status' => 1))->sum('num');
		}

		$this->assign('data', $data);
		$this->display();
	}

	public function goods($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('UserGoods')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserGoods')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function goodsEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = null;
			}
			else {
				$this->data = M('UserGoods')->where(array('id' => trim($id)))->find();
			}

			$this->display();
		}
		else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$_POST['addtime'] = strtotime($_POST['addtime']);

            if ($id) {
                unset($_POST['id']);
                if (M()->table('ecshecom_user_goods')->where(array('id'=>$id))->save($_POST)) {
                    $this->success('编辑成功！');
                }
                else {
                    $this->error('编辑失败！');
                }
            } else {
                if (M()->table('ecshecom_user_goods')->add($_POST)) {
                    $this->success('添加成功！');
                }
                else {
                    $this->error('添加失败！');
                }
            }
		}
	}

	public function goodsStatus($id = NULL, $type = NULL, $moble = 'UserGoods')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

	public function setpwd()
	{
		if (IS_POST) {
			defined('APP_DEMO') || define('APP_DEMO', 0);

			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$oldpassword = $_POST['oldpassword'];
			$newpassword = $_POST['newpassword'];
			$repassword = $_POST['repassword'];

			if (!check($oldpassword, 'password')) {
				$this->error('旧密码格式错误！');
			}

			if (md5($oldpassword) != session('admin_password')) {
				$this->error('旧密码错误！');
			}

			if (!check($newpassword, 'password')) {
				$this->error('新密码格式错误！');
			}

			if ($newpassword != $repassword) {
				$this->error('确认密码错误！');
			}

			if (D('Admin')->where(array('id' => session('admin_id')))->save(array('password' => md5($newpassword)))) {
				$this->success('登陆密码修改成功！', U('Login/loginout'));
			}
			else {
				$this->error('登陆密码修改失败！');
			}
		}

		$this->display();
	}

    public function award($name = NULL, $field = NULL, $status = NULL)
	{
/* 		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		} */

		$where="";
		if ($field && $name) {
			//$where[$field] = $name;
			if($field=="awardid" &&($name==7 || $name==9)){
				$where = " (`awardid`=7 or `awardid`=9) ";
			}else{
				$where = "`".$field."`='".$name."'";
			}
		}

		if($status){
			$where = $where." and `status`=".($status-1);
		}






		$count = M('UserAward')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('UserAward')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

    public function awardStatus($id = NULL, $type = NULL,$status=NUll, $moble = 'UserAward')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('请选择要操作的记录！');
		}

		if (empty($type)) {
			$this->error('参数错误！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'dealaward':
			if(empty($status)){
				$this->error("参数错误！");
			}
			$data=array('status' => $status,'dealtime'=>time());
			break;
		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			}
			else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		}
		else {
			$this->error('操作失败！');
		}
	}

    /**
     * 钱包地址列表
     * @param null $user_id
     * @param null $coin_name
     * @param null $address
     * @param null $is_bind
     * @param int $status
     */
    public function qianbaoAddress($user_id = NULL, $coin_name = NULL, $address = NULL, $is_bind = NULL, $status = null)
    {
        $where = array();
        if (is_numeric($status)) {
            $where['status'] = intval($status);
        } else {
            //$where['status'] = 1;
        }
        if (is_numeric($user_id)) {
            $where['user_id'] = intval($user_id);
        }
        if (!empty($coin_name)) {
            $where['coin_name'] = trim($coin_name);
        }
        if (!empty($address)) {
            $where['address'] = trim($address);
        }
        if (!is_null($is_bind)) {
            if ($is_bind == 'yes') {
                $where['bind_time'] = array('gt', 0);
            } elseif ($is_bind == 'no') {
                $where['bind_time'] = array('elt', 0);
            }
        }

        $QianBaoAddress = D('UserQianbaoAddress');
        $count = $QianBaoAddress->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $QianBaoAddress
            ->where($where)
            ->order('id asc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        $user_ids = array_unique(array_filter(array_column($list, 'user_id')));
        if (!empty($user_ids)) {
            $user_list = D('User')
                ->where(array(
                    'id' => array('in', $user_ids),
                ))
                ->getField('id,moble,truename');

            foreach ($list as $key => $value) {
                if (empty($user_list[$value['user_id']])) {
                    continue;
                }
                $value['moble'] = $user_list[$value['user_id']]['moble'];
                $value['truename'] = $user_list[$value['user_id']]['truename'];
                $list[$key] = $value;
            }
        }

        // 获取自定义币种配置
        $custom_coin_config = $this->getCustomCoinConfig();

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('custom_coin_type', $custom_coin_config['custom_coin_type']);
        $this->assign('coin_config', $custom_coin_config['coin_config']);
        $this->assign('bind_status', array(
            'yes' => '已绑定',
            'no' => '未绑定',
        ));

        $this->display();
    }

    /**
     * 添加自定义币种钱包地址
     * @param int $id
     */
    public function qianbaoAddressEdit($id = 0)
    {
        $post_data = $_POST;
        $QianbaoAddress = D('UserQianbaoAddress');
        if (empty($post_data)) {
            $this->data = null;
            if (!empty($id)) {
                $this->data = $QianbaoAddress->find(intval($id));
            }

        	// 获取自定义币种配置
            $custom_coin_config = $this->getCustomCoinConfig();

            $this->assign('custom_coin_type', $custom_coin_config['custom_coin_type']);
            $this->assign('coin_config', $custom_coin_config['coin_config']);
            $this->display();
        } else {
            if (APP_DEMO) {
                $this->error('测试站暂时不能修改！');
            }

            if (empty($post_data['address'])) {
            	$this->error('请输入地址！');
            }
            // 钱包地址只能是英文字母+数字
            if (!preg_match('/^[a-zA-Z\d]+$/', $post_data['address'])) {
                $this->error('钱包地址只能是英文字母或数字或两者的组合！');
            }

            $exist = $QianbaoAddress
                ->where(array(
                    'address' => trim($post_data['address'])
                ))
                ->find();
            if (!empty($exist)) {
                $this->error('地址已存在！');
            }

            $post_data['user_id'] = 0;
            $post_data['add_time'] = time();
            $post_data['status'] = 1;
            unset($post_data['id']);
            if ($QianbaoAddress->add($post_data)) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        }
    }

    /**
     * 修改自定义币种钱包地址状态(开放禁用，废除和软删除)
     * @param null $id
     * @param null $type
     * @param string $moble
     */
    public function qianbaoAddressStatus($id = NULL, $type = NULL, $moble = 'UserQianbaoAddress')
    {
        if (APP_DEMO) {
            $this->error('测试站暂时不能修改！');
        }

        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;
            case 'resume':
                $data = array('status' => 1);
                break;
            // 可用作解绑并废除当前数据
            case 'repeal':
                $data = array('status' => 2);
                break;
            case 'delete':
                $data = array('status' => -1);
                break;
            case 'del':
                if (M($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;
            default:
                $this->error('操作失败！');
        }

        if (D($moble)->where($where)->save($data)) {
            $this->success('操作成功！');
        }
        else {
            $this->error('操作失败！');
        }
    }

    /**
     * 解绑自定义币种钱包地址
     * @param int $id
     */
    public function unbindQianbaoAddress($id = 0)
    {
        $qianbaoAddress = D('UserQianbaoAddress')
            ->where(array(
                'id' => intval($id),
                'user_id' => array('gt', 0),
                'bind_time' => array('gt', 0),
                'status' => 1,
            ))
            ->find();
        if (empty($qianbaoAddress)) {
            $this->error('操作失败！');
        }

        $model = M();
        $model->execute('set autocommit=0');
        $model->execute('lock tables ecshecom_user_coin write,ecshecom_myzr write,ecshecom_finance write,ecshecom_invit write,ecshecom_user write,ecshecom_user_qianbao_address write');

        $result = array();
        $result[] = $model
            ->table('ecshecom_user_qianbao_address')
            ->save(array(
                'id' => intval($id),
                'status' => 2
            ));
        $result[] = $model
            ->table('ecshecom_user_coin')
            ->where(array(
                'userid' => $qianbaoAddress['user_id']
            ))
            ->save(array(
                $qianbaoAddress['coin_name'] . 'b' => ''
            ));

        if (check_arr($result)) {
            $model->execute('commit');
            $model->execute('unlock tables');
            $this->success('解绑成功');
        } else {
            $model->execute('rollback');
            $this->error('解绑失败！');
        }
    }

    /**
     * 绑定自定义币种钱包地址
     * @param $user_id
     */
    public function bindQianbaoAddress($id = 0)
    {
    	$post_data = $_POST;
		if (empty($post_data)) {
            $user_id = intval($id);
			if (empty($user_id)) {
				$this->error('请输入用户ID！');
			}

			$custom_coin_config = $this->getCustomCoinConfig();

			// 获取已绑定的币种
			$qianbaoAddress = D('UserQianbaoAddress')
                ->field('id,user_id,coin_name,address,bind_time')
				->where(array(
					'user_id' => $user_id,
					'status' => 1,
				))
				->select();

            $bind_coin_type = [];
            $bind_coin = [];
			foreach ($qianbaoAddress as $key => $value) {
                $bind_coin_type[] = $value['coin_name'];
                $bind_coin[$value['coin_name']] = $value;
			}

			// 筛选出未绑定币种
            $unbind_coin = [];
            foreach ($custom_coin_config['coin_config'] as $key => $value) {
                if (in_array($key, $bind_coin_type)) {
                    continue;
                }
                $unbind_coin[$key] = $custom_coin_config['custom_coin_type'][$value['type']] . '--' . $value['title'];
            }

            $this->data = D('User')
                ->field('id,username,moble,truename')
                ->find(intval($user_id));
            $this->assign('user_id', $user_id);
            $this->assign('bind_coin', $bind_coin);
            $this->assign('unbind_coin', $unbind_coin);
            $this->display();
		} else {
			if (APP_DEMO) {
                $this->error('测试站暂时不能修改！');
            }

			if (empty($post_data['user_id']) || empty($post_data['coin_name'])) {
				$this->error('请输入用户ID或币种！');
			}

	        $user_id = intval($post_data['user_id']);
	        $coin_name = trim($post_data['coin_name']);
            $address = trim($post_data['address']);

	        // 检查用户是否已绑定该币种钱包地址
	        $qianbaoAddress = D('UserQianbaoAddress')
	            ->where(array(
	                'user_id' => $user_id,
	                'coin_name' => $coin_name,
	                'status' => 1,
	            ))
	            ->find();
	        if (!empty($qianbaoAddress)) {
	            $this->error('操作失败！');
	        }

	        $model = M();
	        $model->execute('set autocommit=0');
	        $model->execute('lock tables ecshecom_user_coin write,ecshecom_myzr write,ecshecom_finance write,ecshecom_invit write,ecshecom_user write,ecshecom_user_qianbao_address write');

	        $result = array();
	        $result[] = $model
	            ->table('ecshecom_user_qianbao_address')
	            ->where(array(
	                'coin_name' => $coin_name,
	                'user_id' => 0,
	                'bind_time' => 0,
	                'address' => trim($address),
	                'status' => 1
	            ))
	            ->save(array(
	                'user_id' => $user_id,
	                'bind_time' => time(),
	            ));
	        $result[] = $model
	            ->table('ecshecom_user_coin')
	            ->where(array(
	                'userid' => $user_id,
                    $coin_name . 'b' => ''
	            ))
	            ->save(array(
                    $coin_name . 'b' => $address
	            ));

	        if (check_arr($result)) {
	            $model->execute('commit');
	            $model->execute('unlock tables');
	            $this->success('绑定成功');
	        } else {
	            $model->execute('rollback');
	            $this->error('绑定失败！');
	        }
	    }
    }

    /**
     * 获取自定义币种配置
     * @return array
     */
    private function getCustomCoinConfig()
    {
    	$custom_coin_type = C('CUSTOM_COIN_TYPE');
        $coin_config = array();
        if (!empty($custom_coin_type)) {
            $coin_list = D('Coin')
                ->where(array(
                    'type' => array('in', array_keys($custom_coin_type)),
                ))
                ->getField('id,name,type,title');

            if (!empty($coin_list)) {
                foreach ($coin_list as $value) {
                    $coin_config[$value['name']] = $value;
                }
            }
        }

        return compact('custom_coin_type', 'coin_config');
    }
}

?>