<?php
/*
Plugin Name: MemberFindMe Login Connector
Plugin URI: http://memberfind.me
Description: Connects MemberFindMe membership system with WordPress user accounts and login
Version: 1.7
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

class sf_widget_login extends WP_Widget {
	public function __construct() {
		parent::__construct('sf_widget_login','MemberFindMe Login',array('description'=>'Login/logout to WordPress and MemberFindMe'));
	}
	public function widget($args,$instance) {
		global $current_user;
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
			$uid=get_user_meta(get_current_user_id(),'SF_ID',true);
			echo '<p style="margin-top:0">'.__('Hello').' '.$current_user->display_name.'!</p>'
				.'<form id="loginform'.$id.'" action="'.esc_url(wp_nonce_url(site_url('wp-login.php','login_post'),'log-out')).'&action=logout&redirect_to='.esc_url(get_site_url()).'" method="post">'
				.'<input type="submit" class="button-primary" value="'.__('Log Out').'" />'
				.'</form>';
		} else
			echo '<form id="loginform'.$id.'" action="'.esc_url(site_url('wp-login.php','login_post')).'" method="post">'
				.'<p class="login-username">'
					.'<label>'.__('Email').'</label>'
					.'<input type="text" name="log" class="input" size="20" />'
				.'</p>'
				.'<p class="login-password">'
					.'<label>'.__('Password').'</label>'
					.'<input type="password" name="pwd" class="input" size="20" />'
				.'</p>'
				.'<p class="login-submit">'
					.'<input type="submit" class="button-primary" value="'.__('Log In').'" />'
					.'<input type="hidden" name="redirect_to" value="'.esc_url($instance['url']?$instance['url']:$_SERVER['REQUEST_URI']).'" />'
				.'</p>'
				.'</form>';
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

function sf_login_init() {
	$act=isset($_REQUEST['action'])?$_REQUEST['action']:'login';
	if (($set=get_option('sf_set'))&&!empty($set['org'])&&defined('SF_WPL')) {
		if ($act=='logout') {
			setcookie('SFSF',' ',time()+8640000,'/');
		} else if ($act=='sf_logout') {
			setcookie('SFSF',' ',time()+8640000,'/');
			wp_logout();
			die();
		} else if ($act=='login'&&isset($_POST['log'])&&isset($_POST['pwd'])) {
			$IP=isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
			$eml=trim(strtolower($_POST['log']));
			$pwd=trim(strtolower($_POST['pwd']));
			do {
				if (empty($try)) $try=0; else usleep(100000);
				$rsp=wp_remote_post('https://www.sourcefound.com/fi/usr',array('method'=>'POST','headers'=>array('from'=>$IP),'user-agent'=>$_SERVER['HTTP_USER_AGENT'],'body'=>array('eml'=>$eml,'pwd'=>$pwd,'org'=>$set['org'])));
			} while (is_wp_error($rsp)&&($try++)<3);
			if (!is_wp_error($rsp)&&($rsp=json_decode($rsp['body'],true))&&!empty($rsp['uid'])) {
				$doc=array('nickname'=>$rsp['nam'],'user_nicename'=>$rsp['nam'],'display_name'=>$rsp['nam'],'user_pass'=>$pwd);
				if (isset($rsp['url'])) $doc['user_url']=$rsp['url'];
				$id=username_exists($rsp['uid']);
				if (is_null($id)) {
					$id=wp_create_user($rsp['uid'],$pwd,$eml);
					$doc['show_admin_bar_front']='false';
				}
				if (!is_null($id)&&!is_wp_error($id)) {
					$doc['ID']=$id;
					wp_update_user($doc);
					update_user_meta($id,'SF_ID',$rsp['uid']);
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
add_action('login_init','sf_login_init');

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
	for ($i=0;($x=strpos($content,'[memberonly',$i))!==false||($x=strpos($content,'[membersonly',$i))!==false;$i=$x+1) {
		$y=strpos($content,']',$x);
		if ((!$x||substr($content,$x-1,1)!='[')&&$y!==false) break;
	}
	if ($x!==false&&$y!==false) {
		$mat=array();
		preg_match_all('/\s([a-z]*)="([^"]*)"/',substr($content,$x+1,$y-$x-1),$mat,PREG_PATTERN_ORDER);
		$opt=array();
		foreach ($mat[1] as $key=>$val) $opt[$val]=$mat[2][$key];
		if (current_user_can('edit_post')) {
			return substr_replace($content,'[content below ',$x,1);
		} else if (!isset($_COOKIE['SFSF'])||!$_COOKIE['SFSF']||!is_user_logged_in()||!get_user_meta(get_current_user_id(),'SF_ID',true)) {
			return substr($content,0,$x)
				.'<div class="memberonly">'.__('... This content is accessible for members only. Please log in ...').'</div>'
				.(is_singular()?('<form action="'.esc_url(site_url('wp-login.php','login_post')).'" method="post" style="display:block;margin-top:20px;"><table>'
				.'<tr class="login-username"><td style="padding-right:10px;">'.__('Email').'</td><td><input type="text" name="log" class="input" size="20" style="width:200px" /></td></tr>'
				.'<tr class="login-password"><td style="padding-right:10px;">'.__('Password').'</td><td><input type="password" name="pwd" class="input" size="20" style="width:200px" /></td></tr>'
				.'<tr class="login-submit"><td></td><td><input type="submit" class="button-primary" value="'.__('Log In').'" /></td></tr>'
				.'</table>'
				.'<input type="hidden" name="redirect_to" value="'.esc_url(get_permalink()).'" />'
				.'</form>'):'');
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
					.'<div class="memberonly">'.__('... This content is not accessible for your membership level ...').'</div>';
		} else {
			return substr_replace($content,'',$x,$y-$x+1);
		}
	} else
		return $content;
}
add_filter('the_content','sf_memberonly',1);

?>