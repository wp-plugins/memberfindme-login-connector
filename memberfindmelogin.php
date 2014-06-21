<?php
/*
Plugin Name: MemberFindMe Login Connector
Plugin URI: http://memberfind.me
Description: Connects MemberFindMe membership system with WordPress user accounts and login
Version: 2.1
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

define('SF_WPL',1);

$SF_widget_login='<div class="login-choose" style="display:none"></div>'
.'<div class="login-form">'
	.'<p class="login-username"><label style="display:block">'.__('Email').'</label><input type="text" name="log" class="input" size="20"></p>'
	.'<p class="login-password"><label style="display:block">'.__('Password').'</label><input type="password" name="pwd" class="input" size="20"></p>'
	.'<p class="login-submit">'
		.'<input type="submit" class="button-primary" value="'.__('Log In').'" onclick="sf_wpl(this.parentNode.parentNode)">'
		.'<a onclick="this.parentNode.style.display=this.parentNode.parentNode.querySelector(\'.login-password\').style.display=\'none\';this.parentNode.parentNode.querySelector(\'.login-request\').style.display=\'\';">Forgot password?</a>'
	.'</p>'
	.'<p class="login-request" style="display:none">'
		.'<input type="submit" class="button-primary" value="'.__('Email Password').'" onclick="sf_wpl(this.parentNode.parentNode,\'pwd\')">'
	.'</p>'
.'</div>'
.'<div style="display:none">'
	.'<p class="login-message">-</p>'
	.'<p class="login-ack"><input type="submit" class="button-primary" value="'.__('Continue').'" onclick="this.parentNode.parentNode.style.display=\'none\';this.parentNode.parentNode.parentNode.querySelector(\'.login-form\').style.display=\'\';"></p>'
.'</div>'
.'<script>function sf_wpl(n,act,uid){var a,i,log=false,pwd=false,red=false,xml,m=n.parentNode.querySelector(".login-message");'
	.'for(a=n.parentNode.querySelectorAll("input"),i=0;i<a.length;i++)if(a[i].name){if(a[i].name=="log")log=encodeURIComponent(a[i].value);else if(a[i].name=="pwd"){if(act)a[i].value="";else pwd=encodeURIComponent(a[i].value);}else if (a[i].name=="red")red=a[i].value;}'
	.'if(!log){alert("'.__('Please enter your email address').'");return false;}'
	.'if(!(act||pwd)){alert("'.__('Please enter your password').'");return false;}'
	.'m.innerHTML="Please wait...";'
	.'n.style.display=m.parentNode.querySelector(".login-ack").style.display="none";'
	.'m.parentNode.style.display="";'
	.'xml=new XMLHttpRequest();'
	.'xml.open("POST","'.esc_url(site_url('wp-login.php')).'",true);'
	.'xml.setRequestHeader("Content-type","application/x-www-form-urlencoded");'
	.'xml.onreadystatechange=function(){if(this.readyState==4){'
		.'if(this.status==200){var x,l=String.fromCharCode(60),r=String.fromCharCode(62);'
			.'if(this.responseText.indexOf(l+"div id=\"login_error\""+r)>=0){'
				.'m.innerHTML=act?"Email not found":"Email/password incorrect or not found";'
				.'m.parentNode.querySelector(".login-ack").style.display="";'
			.'}else if(act){'
				.'if(!uid){'
					.'n.querySelector(".login-password").style.display=n.querySelector(".login-submit").style.display="";'
					.'n.querySelector(".login-request").style.display="none";'
				.'}'
				.'if(this.responseText.indexOf(l+"p class=\"message\""+r)>=0){'
					.'m.innerHTML="Your password has been emailed to you! Please check your spam folder too in case the email lands there.";'
					.'m.parentNode.querySelector(".login-ack").style.display="";'
				.'}else if(!(!act||(x=this.responseText.indexOf(l+"div id=\"wpl-choose-account\""+r))<0)){'
					.'m.parentNode.style.display="none";'
					.'n.parentNode.querySelector(".login-choose").style.display="";'
					.'x=this.responseText.substr(x+29);'
					.'n.parentNode.querySelector(".login-choose").innerHTML=x.substr(0,x.indexOf(l+"/div"+r));'
				.'}'
			.'}else{'
				.'if(red)location=red;else location.reload();'
			.'}'
		.'}else{alert("Login System Error");}'
	.'}};'
	.'i=String.fromCharCode(38);'
	.'if(!act)xml.send("action=login"+i+"log="+log+i+"pwd="+pwd+i+"redirect_to="+encodeURIComponent(location.href));'
	.'else xml.send("action=retrievepassword"+i+"user_login="+log+(uid?(i+"uid="+uid):""));'
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
				.'<form id="loginform'.$id.'" action="'.esc_url(wp_nonce_url(site_url('wp-login.php','login_post'),'log-out')).'&action=logout&redirect_to='.esc_url(empty($set['out'])?get_site_url():$set['out']).'" method="post">'
				.'<input type="submit" class="button-primary" value="'.__('Log Out').'" />'
				.'</form>';
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
	if (($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')) {
		if ($act=='logout'||$act=='sf_logout') {
			setcookie('SFSF',' ',time()+8640000,'/');
			if ($act=='sf_logout') {
				wp_logout();
				die();
			}
		} else if (($act=='login'||$act=='sf_login')&&isset($_POST['log'])&&isset($_POST['pwd'])) {
			$IP=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
			$eml=trim(strtolower($_POST['log']));
			$pwd=trim(strtolower($_POST['pwd']));
			$try=0;
			for($try=0;!$try||(is_wp_error($rsp)&&$try<3);$try++) {
				if ($try) usleep(100000);
				$rsp=wp_remote_post('https://www.sourcefound.com/api',array('method'=>'POST','headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT'],'body'=>array('fi'=>'usr','org'=>$set['org'],'eml'=>$eml,'pwd'=>$pwd)));
			}
			if (!is_wp_error($rsp)&&($rsp=json_decode($rsp['body'],true))&&!empty($rsp['uid'])) {
				$doc=array('nickname'=>$rsp['nam'],'user_nicename'=>$rsp['nam'],'display_name'=>$rsp['nam'],'user_pass'=>$pwd);
				if (isset($rsp['url'])) $doc['user_url']=$rsp['url'];
				$id=username_exists($rsp['uid']);
				if (is_null($id)) {
					$id=wp_create_user($rsp['uid'],$pwd,$eml);
					if (is_wp_error($id)&&$id->get_error_code()=='existing_user_email')
						$id=wp_create_user($rsp['uid'],$pwd);
					$doc['show_admin_bar_front']='false';
				}
				if (!is_null($id)&&!is_wp_error($id)) {
					$doc['ID']=$id;
					$doc['user_email']=$eml;
					wp_update_user($doc);
					update_user_meta($id,'SF_ID',$rsp['uid']);
					if ($act=='login')
						setcookie('SFSF',$rsp['SF'],time()+8640000,'/');
					$_POST['log']=$rsp['uid'];
					$_POST['pwd']=$pwd;
				}
			} else if ($id=email_exists($eml)) {
				delete_user_meta($id,'SF_ID');
			}
		}
	}
}
add_action('login_form_login','sf_login');
add_action('login_form_logout','sf_login');

function sf_password() {
	if (!empty($_POST['user_login'])&&strpos($_POST['user_login'],'@')&&($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')) {
		for($try=0;!$try||(is_wp_error($rsp)&&$try<3);$try++) {
			if ($try) usleep(100000);
			$rsp=wp_remote_get('https://www.sourcefound.com/api?fi=usr&org='.$set['org'].'&pwd&eml='.urlencode($_POST['user_login']).(empty($_POST['uid'])?'':'&uid='.($_POST['uid'])),array('headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT']));
		}
		if (!is_wp_error($rsp)&&empty($rsp['body'])) {
			wp_safe_redirect(empty($_REQUEST['redirect_to'])?'wp-login.php?checkemail=confirm':$_REQUEST['redirect_to']);
			die();
		} else if (($rsp=json_decode($rsp['body'],true))&&isset($rsp[0])) { // multiple options
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
			echo '</div></div><div style="display:none"><div id="wpl-choose-account"><p>Select the account you are requesting the password for:</p>';
			foreach ($rsp as $usr) {
				echo '<p><a style="cursor:pointer" onclick="sf_wpl(this.parentNode.parentNode,\'pwd\',\''.esc_attr($usr['_id']).'\')">'.(empty($usr['ctc'])?$usr['nam']:($usr['ctc'].' ('.$usr['nam'].')')).'</a></p>';
			}
			echo '</div></div><style>.hvr{background:transparent}.hvr:hover{background:#0074a2;color:#fff}</style></body></html>';
			die();
		}
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
		preg_match_all('/\s([a-z\-]*)="([^"]*)"/',substr($content,$x+1,$y-$x-1),$mat,PREG_PATTERN_ORDER);
		$opt=array();
		foreach ($mat[1] as $key=>$val) $opt[$val]=$mat[2][$key];
		if (current_user_can('edit_post')) {
			return substr_replace($content,'[administrator info: content below ',$x,1);
		} else if (!is_user_logged_in()||!get_user_meta(get_current_user_id(),'SF_ID',true)) {
			if (!empty($opt['nonmember-redirect'])&&is_singular()) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__(empty($opt['message'])?('... This content is accessible for'.(!empty($opt['label'])||!empty($opt['level'])?' certain':'').' members only ...'):$opt['message']).'</p>'
					.'<script>window.location="'.esc_url($opt['nonmember-redirect']).'";</script>';
			} else if (!empty($opt['nonmember'])&&is_singular()&&($set=get_option('sf_set'))) {
				return substr($content,0,$x)
					.'<p class="memberonly">'.__(empty($opt['message'])?('... This content is accessible for'.(!empty($opt['label'])||!empty($opt['level'])?' certain':'').' members only ...'):$opt['message']).'</p>'
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
					.' data-wpl="'.esc_url(preg_replace('/^http[s]?:\\/\\/[^\\/]*/','',site_url('wp-login.php','login_post'))).'"'
					.' data-zzz="'.esc_url(empty($opt['redirect'])?get_permalink():$opt['redirect']).'"'
					.'><div id="SFpne" style="position:relative;"><div class="SFpne">Loading...</div></div>'
					.'<div style="clear:both;"></div>'
					.'</div>'
					.'<script type="text/javascript" src="//mfm-sourcefoundinc.netdna-ssl.com/mfm.js" defer="defer"></script>';
			} else {
				return substr($content,0,$x)
					.'<div class="memberonly" style="padding:20px;border:1px solid #ddd">'
						.'<p>'.__(empty($opt['message'])?('... This content is accessible for'.(!empty($opt['label'])||!empty($opt['level'])?' certain':'').' members only. Please log in ...'):$opt['message']).'</p>'
						.(is_singular()?$SF_widget_login:'')
					.'</div>';
			}
			setcookie('SFSF',' ',time()+8640000,'/');
		} else if ((!empty($opt['label'])||!empty($opt['level']))&&($set=get_option('sf_set'))&&!empty($set['org'])) {
			$arr=split(',',empty($opt['label'])?$opt['level']:$opt['label']);
			$lbl=array();
			foreach ($arr as $val) if (trim(urldecode($val))) $lbl[]=urlencode(trim(urldecode($val)));
			do {
				if (empty($try)) $try=0; else usleep(100000);
				$rsp=wp_remote_get('https://www.sourcefound.com/fi/usr?org='.$set['org'].'&sfsf='.$_COOKIE['SFSF'].'&lbl='.implode(',',$lbl),array('headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT']));
			} while (is_wp_error($rsp)&&($try++)<3);
			if (!is_wp_error($rsp)&&($rsp=json_decode($rsp['body'],true))&&count($rsp))
				return substr_replace($content,'',$x,$y-$x+1);
			else 
				return substr($content,0,$x)
					.'<p class="memberonly">'.__('... This content is not accessible for your membership level ...').'</p>';
		} else {
			return substr_replace($content,'',$x,$y-$x+1);
		}
	} else
		return $content;
}
add_filter('the_content','sf_memberonly',1);

?>