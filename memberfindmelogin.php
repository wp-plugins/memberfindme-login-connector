<?php
/*
Plugin Name: MemberFindMe Login Connector
Plugin URI: http://memberfind.me
Description: Connects MemberFindMe membership system with WordPress user accounts and login
Version: 4.0
Author: SourceFound
Author URI: http://memberfind.me
License: GPL2
*/

/*  Copyright 2013  SOURCEFOUND INC.  (email : info@sourcefound.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('SF_WPL',3);

function sf_mfl_init() {
	global $current_user;
	if (defined('DOING_AJAX')&&defined('WP_ADMIN')&&!empty($_REQUEST['action'])){
		if ($_REQUEST['action']=='sf_login'){
			sf_login();
		} else if ($_REQUEST['action']=='sf_logout') {
			sf_logout();
		} else if ($_REQUEST['action']=='sf_password') {
			sf_password();
		}
	//} else if (isset($_COOKIE['SFSF'])&&$_COOKIE['SFSF']!=' '&&!is_user_logged_in()) {
	//	setcookie('SFSF',' ',time()+8640000,'/');
	} else if (is_user_logged_in()&&(empty($_COOKIE['SFSF'])||$_COOKIE['SFSF']==' ')&&($uid=get_user_meta(get_current_user_id(),'SF_ID',true))&&wp_get_current_user()->user_login==$uid) {
		wp_clear_auth_cookie();
		wp_set_current_user(0);
	}
}
add_action('plugins_loaded','sf_mfl_init');

function sf_mfl_clear_auth_cookie() {
	setcookie('SFSF',' ',time()+8640000,'/');
}
add_action('clear_auth_cookie','sf_mfl_clear_auth_cookie');

function sf_mfl_nocache_headers($headers) {
	$headers['Cache-Control']='no-cache, must-revalidate, max-age=0, no-store';
	return $headers;
}
add_filter('nocache_headers','sf_mfl_nocache_headers');

function sf_mfl_header() {
	global $post;
	if(strpos($post->post_content,'[memberonly')!==false||strpos($post->post_content,'[membersonly')!==false) {
		if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
		nocache_headers();
	}
}
add_action('get_header','sf_mfl_header');

$SF_widget_login='<div class="login-form">'
	.'<p class="login-username"><label style="display:block">'.__('Email').'</label><input type="text" name="log" class="input" size="20"></p>'
	.'<p class="login-password"><label style="display:block">'.__('Password').'</label><input type="password" name="pwd" class="input" size="20"></p>'
	.'<p class="login-submit">'
		.'<input type="submit" class="button-primary" value="'.__('Sign In').'" onclick="sf_wpl(this.parentNode.parentNode);return false;">'
		.'<a style="margin-left:10px" onclick="this.parentNode.style.display=this.parentNode.parentNode.querySelector(\'.login-password\').style.display=\'none\';this.parentNode.parentNode.querySelector(\'.login-request\').style.display=\'\';">Forgot password?</a>'
	.'</p>'
	.'<p class="login-request" style="display:none">'
		.'<input type="submit" class="button-primary" value="'.__('Email Password').'" onclick="sf_wpl(this.parentNode.parentNode,\'pwd\');return false;">'
	.'</p>'
.'</div>'
.'<div style="display:none">'
	.'<p class="login-message">-</p>'
	.'<p class="login-ack"><input type="submit" class="button-primary" value="'.__('Continue').'" onclick="this.parentNode.parentNode.style.display=\'none\';this.parentNode.parentNode.parentNode.querySelector(\'.login-form\').style.display=\'\';return false;"></p>'
.'</div>'
.'<script>function sf_wpl(n,act,uid){var a,i,log=false,pwd=false,red=false,xml,f=n.parentNode.querySelector(".login-form"),m=n.parentNode.querySelector(".login-message");'
	.'for(a=n.parentNode.querySelectorAll("input"),i=0;i<a.length;i++)if(a[i].name){if(a[i].name=="log")log=encodeURIComponent(a[i].value);else if(a[i].name=="pwd"){if(act)a[i].value="";else pwd=encodeURIComponent(a[i].value);}else if (a[i].name=="red")red=a[i].value;}'
	.'if(!log){alert("'.__('Please enter your email address').'");return false;}'
	.'if(!(act||pwd)){alert("'.__('Please enter your password').'");return false;}'
	.'f.style.display=m.parentNode.querySelector(".login-ack").style.display="none";'
	.'m.parentNode.style.display="";'
	.'m.innerHTML="Please wait...";'
	.'xml=new XMLHttpRequest();'
	.'xml.open("POST","'.str_replace(array('http://','https://'),'//',esc_url(admin_url('admin-ajax.php'))).'",true);'
	.'xml.setRequestHeader("Content-type","application/x-www-form-urlencoded");'
	.'xml.onreadystatechange=function(){if(this.readyState==4){'
		.'if(this.status==200){'
			.'if(this.responseText==="OK"){'
				.'if(act){'
					.'m.innerHTML="Your password has been emailed to you! Please check your spam folder too in case the email lands there.";'
					.'m.parentNode.querySelector(".login-ack").style.display="";'
					.'f.querySelector(".login-password").style.display=n.parentNode.querySelector(".login-submit").style.display="";'
					.'f.querySelector(".login-request").style.display="none";'
				.'}else{'
					.'if(red)location=red;else location.reload();'
				.'}'
			.'}else if(act){'
				.'m.innerHTML=this.responseText;'
				.'m.parentNode.querySelector(".login-ack").style.display="";'
			.'}else{'
				.'m.innerHTML=this.responseText;'
				.'m.parentNode.querySelector(".login-ack").style.display="";'
			.'}'
		.'}else{alert("Login system error");}'
	.'}};'
	.'i=String.fromCharCode(38);'
	.'if(act)xml.send("action=sf_password"+i+"user_login="+log+(uid?(i+"uid="+uid):""));'
	.'else xml.send("action=sf_login"+i+"log="+log+i+"pwd="+pwd);'
	.'return false;'
.'}</script>';

function sf_wpl_deactivate() {
	$set=get_option('sf_set');
	if (!empty($set)&&!empty($set['wpl']))
		update_option('sf_set',array_diff_key($set,array('wpl'=>1)));
}
register_deactivation_hook(__FILE__,'sf_wpl_deactivate');

class sf_widget_login extends WP_Widget {
	public function __construct() {
		parent::__construct('sf_widget_login','MemberFindMe Login',array('description'=>'Login/logout to WordPress and MemberFindMe'));
	}
	public function widget($args,$instance) {
		global $current_user,$SF_widget_login;
		extract($args);
		$id=str_replace('-','_',$this->id);
		$title=apply_filters('widget_title',$instance['title']);
		if (empty($title))
			echo str_replace('widget_sf_widget_login','widget_sf_widget_login widget_no_title',$before_widget);
		else
			echo $before_widget;
		if (!empty($title))
			echo $before_title.$title.$after_title;
		if (is_user_logged_in()) {
			$set=get_option('sf_set');
			$uid=get_user_meta(get_current_user_id(),'SF_ID',true);
			echo '<p style="margin-top:0">'.__('Hello').' '.$current_user->display_name.'!</p>'
				.'<input type="submit" class="button-primary" onclick="sf_wpl();return false;" value="Logout"/>'
				.'<script>function sf_wpl(){var xml=new XMLHttpRequest();'
					.'xml.open("POST","'.esc_url(str_replace(array('http://','https://'),'//',admin_url('admin-ajax.php'))).'",true);'
					.'xml.setRequestHeader("Content-type","application/x-www-form-urlencoded");'
					.'xml.onreadystatechange=function(){if(this.readyState==4){location="'.(empty($set['out'])?get_site_url():$set['out']).'";}};'
					.'xml.send("action=sf_logout");'
					.'return false;'
				.'}</script>';
		} else {
			if (!empty($instance['url']))
				echo '<input type="hidden" name="red" value="'.esc_url($instance['url']).'">';
			echo $SF_widget_login;
		}
		echo $after_widget;
	}
	public function update($new_instance,$old_instance ) {
		$instance=$old_instance;
		$instance['title']=strip_tags($new_instance['title']);
		$instance['url']=trim($new_instance['url']);
		return $instance;
	}
	public function form($instance) {
		$instance=wp_parse_args($instance,array('title'=>'','url'=>''));
		echo '<p><label for="'.$this->get_field_id('title').'">Title:</label><input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr($instance['title']).'" /></p>'
			.'<p><label for="'.$this->get_field_id('url').'">Redirect URL:</label><input class="widefat" id="'.$this->get_field_id('url').'" name="'.$this->get_field_name('url').'" type="text" value="'.esc_attr($instance['url']).'" placeholder="empty=current page" /></p>';
	}
}
function sf_widget_login_init() {
	register_widget('sf_widget_login');
}
add_action('widgets_init','sf_widget_login_init');

function sf_login() {
	$act=isset($_REQUEST['action'])?$_REQUEST['action']:'login';
	$msg=false;
	if (($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')&&isset($_POST['log'])&&isset($_POST['pwd'])) {
		$IP=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
		$eml=trim(strtolower($_POST['log']));
		$pwd=trim(strtolower($_POST['pwd']));
		$try=0;
		for($try=0;!$try||(is_wp_error($rsp)&&$try<3);$try++) {
			if ($try) usleep(100000);
			$rsp=wp_remote_post('https://api.memberfind.me/v1/usr',array('method'=>'POST','headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT'],'body'=>array('org'=>$set['org'],'eml'=>$eml,'pwd'=>$pwd)));
		}
		if (is_wp_error($rsp)||empty($rsp['response'])) {
			$msg='Network error, please try again later';
		} else if ($rsp['response']['code']!=200||empty($rsp['body'])) {
			$msg='Server error, please try again later';
		} else if (($rsp=json_decode($rsp['body'],true))&&!empty($rsp['uid'])) {
			$doc=array('nickname'=>$rsp['nam'],'user_nicename'=>$rsp['nam'],'display_name'=>$rsp['nam']);
			if (isset($rsp['url'])) $doc['user_url']=$rsp['url'];
			$id=username_exists($rsp['uid']);
			add_filter('send_email_change_email','__return_false');
			add_filter('send_password_change_email','__return_false');
			if (is_null($id)||$id===false) {
				$id=wp_create_user($rsp['uid'],$pwd,$eml);
				if (is_wp_error($id)&&$id->get_error_code()=='existing_user_email')
					$id=wp_create_user($rsp['uid'],$pwd);
				$doc['show_admin_bar_front']='false';
			} else {
				wp_update_user(array('ID'=>$id,'user_email'=>$eml)); // update email separately
				wp_set_password($pwd,$id); // update password separately
			}
			if (!is_null($id)&&$id!==false&&!is_wp_error($id)) {
				$doc['ID']=$id;
				wp_update_user($doc); // update names separately
				update_user_meta($id,'SF_ID',$rsp['uid']);
				setcookie('SFSF',rawurlencode($rsp['SF']),time()+8640000,'/');
				if ($act=='sf_login') {
					$user=wp_signon(array('user_login'=>$rsp['uid'],'user_password'=>$pwd,'remember'=>true),false);
					$msg=is_wp_error($user)?('Could not synchronize login '.$user->get_error_message()):'OK';
				} else {
					$_POST['log']=$rsp['uid'];
					$_POST['pwd']=$pwd;
				}
			} else if ($act=='sf_login') {
				$msg='Could not create WP user';
			}
		} else if (($id=email_exists($eml))&&(!get_user_meta(intval($id),'SF_ID',true))) {
			if ($act=='sf_login') {
				$user=wp_signon(array('user_login'=>sanitize_user($_POST['log']),'user_password'=>$_POST['pwd'],'remember'=>true),false);
				$msg=is_wp_error($user)?$user->get_error_message():'OK';
			}
		} else if ($act=='sf_login') {
			$msg=!empty($rsp)&&!empty($rsp['error'])?$rsp['error']:'Email not found or invalid password';
		}
	}
	if (!empty($msg)) {
		if (ob_get_contents()) ob_clean();
		echo $msg;
		die();
	}
}
add_action('login_form_login','sf_login');

function sf_logout() {
	if (($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')) {
		setcookie('SFSF',' ',time()+8640000,'/');
		if (isset($_REQUEST['action'])&&$_REQUEST['action']=='sf_logout') {
			wp_logout();
			if (ob_get_contents()) ob_clean();
			echo 'OK';
			die();
		}
	}
}
add_action('login_form_logout','sf_logout');

function sf_password() {
	if (!empty($_POST['user_login'])&&strpos($_POST['user_login'],'@')&&($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')) {
		$IP=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
		for($try=0;!$try||(is_wp_error($rsp)&&$try<3);$try++) {
			if ($try) usleep(100000);
			$rsp=wp_remote_get('https://api.memberfind.me/v1/usr?Z='.time().'&org='.$set['org'].'&pwd&eml='.urlencode($_POST['user_login']).(empty($_POST['uid'])?'':'&uid='.($_POST['uid'])),array('headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT']));
		}
		if (!is_wp_error($rsp)&&empty($rsp['body'])) {
			if ((isset($_REQUEST['action'])&&$_REQUEST['action']=='sf_password')) {
				if (ob_get_contents()) ob_clean();
				echo 'OK';
			} else
				wp_safe_redirect(empty($_REQUEST['redirect_to'])?'wp-login.php?checkemail=confirm':$_REQUEST['redirect_to']);
		} else if (($rsp=json_decode($rsp['body'],true))&&isset($rsp[0])) { // multiple options
			if ((isset($_REQUEST['action'])&&$_REQUEST['action']=='sf_password')) {
				echo '<p>Select the account you are requesting the password for:</p>';
				foreach ($rsp as $usr) {
					echo '<p><a style="cursor:pointer" onclick="sf_wpl(this.parentNode.parentNode.parentNode,\'pwd\',\''.esc_attr($usr['_id']).'\')">'.(empty($usr['ctc'])?$usr['nam']:($usr['ctc'].' ('.$usr['nam'].')')).'</a></p>';
				}
			} else {
				echo '<html><head></head><body style="background:#f1f1f1"><div style="margin:auto;padding:8% 0 0;width:320px"><p style="padding:20px 0;text-align:center;background:#fff;border:1px solid #ddd;border-left:4px solid #7AD03A">Select the account you are requesting the password for</p><div style="background:#fff;padding:10px 0;border:1p solid #ddd">';
				foreach ($rsp as $usr) {
					echo '<form action="'.esc_url(site_url('wp-login.php')).'" method="post" style="margin:0">'
						.'<input type="hidden" name="action" value="'.esc_attr($_REQUEST['action']).'">'
						.'<input type="hidden" name="redirect_to" value="'.esc_attr($_REQUEST['redirect_to']).'">'
						.'<input type="hidden" name="user_login" value="'.esc_attr($_POST['user_login']).'">'
						.'<input type="hidden" name="uid" value="'.esc_attr($usr['_id']).'">'
						.'<input type="submit" value="'.esc_attr(empty($usr['ctc'])?$usr['nam']:($usr['ctc'].' ('.$usr['nam'].')')).'" class="hvr" style="cursor:pointer;display:block;border:none;padding:10px 0;margin:0;width:100%">'
						.'</form>';
				}
				echo '</div></div><style>.hvr{background:transparent}.hvr:hover{background:#0074a2;color:#fff}</style></body></html>';
			}
		} else if ((isset($_REQUEST['action'])&&$_REQUEST['action']=='sf_password')) {
			echo isset($rsp['error'])?$rsp['error']:'Network error';
		}
		die();
	}
}
add_action('login_form_lostpassword','sf_password');
add_action('login_form_retrievepassword','sf_password');

function sf_get_avatar($avatar,$id_or_email,$size,$default,$alt) {
	if (!is_numeric($size)) $size='96';
	if (is_numeric($id_or_email))
		$uid=get_user_meta(intval($id_or_email),'SF_ID',true);
	elseif (is_object($id_or_email)&&!empty($id_or_email->user_id))
		$uid=get_user_meta(intval($id_or_email->user_id),'SF_ID',true);
	if (isset($uid)&&$uid)
		return '<img alt="'.($alt?esc_attr($alt):'').'" onerror="this.src=\'//usr-sourcefoundinc.netdna-ssl.com/n_ico.jpg\'" src="//usr-sourcefoundinc.netdna-ssl.com/'.$uid.'_ico.jpg" class="avatar avatar-'.$size.' photo" height="'.$size.'" width="'.$size.'" />';
	else
		return $avatar;
}
add_filter('get_avatar','sf_get_avatar',99,5);

function sf_memberonly($content) {
	global $SF_widget_login;
	for ($i=0;($x=strpos($content,'[memberonly',$i))!==false||($x=strpos($content,'[membersonly',$i))!==false;$i=$x+1) {
		$y=strpos($content,']',$x);
		if ((!$x||substr($content,$x-1,1)!='[')&&$y!==false) break;
	}
	if ($x!==false&&$y!==false) {
		$mat=array();
		preg_match_all('/\s([a-z\-]*)(=("|&[^;]*;)+.*?("|&[^;]*;))?/',substr($content,$x+1,$y-$x-1),$mat,PREG_PATTERN_ORDER);
		$opt=array();
		foreach ($mat[1] as $key=>$val) $opt[$val]=empty($mat[2][$key])?'':trim(preg_replace('/^=("|&[^;]*;)*|("|&[^;]*;)$/','',$mat[2][$key]));
		if (current_user_can('edit_post',get_the_ID())) {
			$tmp=array();
			foreach ($opt as $key=>$val) $tmp[]=$key.'="'.$val.'"';
			return substr_replace($content,'[administrator info: content below memberonly '.implode(' ',$tmp).']',$x,$y-$x+1);
		} else if (($set=get_option('sf_set'))&&!empty($set['org'])) {
			$msg='The following content is accessible for members only, please sign in';
			if (is_user_logged_in()&&get_user_meta(get_current_user_id(),'SF_ID',true)) {
				if (!empty($_COOKIE['SFSF'])) {
					$IP=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
					$lbl=array();
					if (!empty($opt['label'])||!empty($opt['level'])) {
						$arr=explode(',',empty($opt['label'])?$opt['level']:$opt['label']);
						foreach ($arr as $val) if (trim(urldecode($val))) $lbl[]=urlencode(trim(urldecode($val)));
					} else if (!empty($opt['folder'])||!empty($opt['folders'])) {
						$arr=explode(',',empty($opt['folder'])?$opt['folders']:$opt['folder']);
						foreach ($arr as $val) if (trim(urldecode($val))) $dek[]=urlencode(trim(urldecode($val)));
					}
					do {
						if (empty($try)) $try=0; else usleep(100000);
						$rsp=wp_remote_get("https://api.memberfind.me/v1/lbl?org=".$set['org'].'&sfsf='.$_COOKIE['SFSF'].'&lbl='.implode(',',$lbl).(empty($dek)?'':'&dek='.implode(',',$dek)),array('headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT']));
					} while (is_wp_error($rsp)&&($try++)<3);
					if (is_wp_error($rsp)||empty($rsp['response']))
						$err='Network error, please try again later';
					else if ($rsp['response']['code']==403)
						$IP=false;
					else if ($rsp['response']['code']!=200||empty($rsp['body']))
						$err='Server error, please try again later';
					else if (($rsp=json_decode($rsp['body'],true))&&!empty($rsp['lbl']))
						return substr_replace($content,'',$x,$y-$x+1);
					else if (!empty($rsp['end']))
						$msg='The following content is not accessible because your membership has expired';
					else
						$msg='The following content is not accessible for your account';
				}	
				if (empty($IP)) {
					if (!headers_sent())
						setcookie('SFSF',' ',time()+8640000,'/');
					wp_logout();
					$msg='Session expired, please sign in again';
				}
			}
			if (!empty($err)) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__($err).'</p>';
			}
			if (!empty($opt['message']))
				$msg=$opt['message'];
			if (!empty($opt['nonmember-redirect'])&&is_singular()) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__($msg).'</p>'
					.'<script>window.location="'.esc_url($opt['nonmember-redirect']).'";</script>';
			} else if (!empty($opt['nonmember'])&&is_singular()&&($set=get_option('sf_set'))) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__($msg).'</p>'
					.'<div id="SFctr" class="SF" data-sfi="1" data-ini="'.$opt['nonmember'].'"'
					.(strpos($opt['open'],'account')===0?'':(' data-hme="'.$opt['open'].'"'))
					.(empty($set['org'])?'':(' data-org="'.$set['org'].'"'))
					.(empty($set['pay'])?'':(' data-pay="'.$set['pay'].'"'))
					.(empty($set['map'])?'':(' data-map="'.$set['map'].'"'))
					.(empty($set['fbk'])?'':(' data-fbk="'.$set['fbk'].'"'))
					.(empty($set['fnd'])?'':(' data-fnd="'.$set['fnd'].'"'))
					.(isset($set['adv'])?(' data-adv="'.$set['adv'].'"'):'')
					.(empty($set['rsp'])?'':(' data-rsp="'.$set['rsp'].'"'))
					.(empty($set['ctc'])?'':(' data-ctc="1"'))
					.(empty($set['scl'])?'':(' data-scl="0"'))
					.(empty($set['out'])?'':(' data-out="'.$set['out'].'"'))
					.(empty($set['top'])?'':(' data-top="'.$set['top'].'"'))
					.' data-wpl="'.esc_url(preg_replace('/^http[s]?:\\/\\/[^\\/]*/','',site_url('wp-login.php','login_post'))).'"'
					.' data-zzz="'.esc_url(empty($opt['redirect'])?get_permalink():$opt['redirect']).'"'
					.'><div id="SFpne" style="position:relative;"><div class="SFpne">Loading...</div></div>'
					.'<div style="clear:both;"></div>'
					.'</div>'
					.'<script type="text/javascript" src="//mfm-sourcefoundinc.netdna-ssl.com/mfm.js" defer="defer"></script>';
			} else if (!empty($IP)) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__($msg).'</p>';
			} else {
				return substr($content,0,$x)
					.'<div class="memberonly"'.(isset($opt['nologin'])?'':' style="padding:40px 0;border-top:1px solid #ddd;border-bottom:1px solid #ddd">')
						.'<p>'.__($msg).'</p>'
						.(is_singular()&&!isset($opt['nologin'])?$SF_widget_login:'')
					.'</div>';
			}
		} else
			return $content;
	} else
		return $content;
}

add_filter('the_content','sf_memberonly',1);
add_filter('widget_text','sf_memberonly',1); 

?>