<?php
	if(!defined('IN_MYBB')){
		die('Direct initialization of this file is not allowed.');
		}
	/*
	Plugin: Quotelinks
	Description: Appends to each post a div containing links to other posts in the same thread that quote that post.
	Author: Copilot
	Version: 1.0
	Compatibility: 18*
	*/
	$plugins->add_hook('showthread_start','qbpp_build_quote_map');
	$plugins->add_hook('postbit','qbpp_append_quoted_by_div');
	/**
	 * Plugin info
	 */
	function quotelinks_info(){
		return array(
			'name' => 'Quotelinks',
			'description' => 'Appends to each post a div containing links to other posts in the same thread that quote that post.',
			'website' => '',
			'author' => 'YourName',
			'authorsite' => '',
			'version' => '1.0',
			'compatibility' => '18*'
			);
		}
	function quotelinks_install(){}
	function quotelinks_uninstall(){}
	/**
	 * Build a map of quoted_post_id => array of quoting posts
	 *
	 * Runs once per showthread request.
	 */
	function qbpp_build_quote_map(){
		global $posts,$thread,$qb_quoted_by;
		$qb_quoted_by=array();
		if (empty($posts)||empty($thread)){
			return;
			}
		//For each post in the thread, find any quote tags that reference other posts.
		foreach($posts as $post){
			if(empty($post['message'])||empty($post['pid'])){
				continue;
				}
			$pid_of_this_post=(int)$post['pid'];
			$message=$post['message'];
			//Find pid=123 or post=123 attributes (common MyBB quote formats)
			$matches=array();
			preg_match_all('/(?:\b(?:pid|post)\s*=\s*(\d+))/i',$message,$matches);
			if(!empty($matches[1])){
				foreach($matches[1] as $quoted_pid){
					$quoted_pid=(int)$quoted_pid;
					if ($quoted_pid<=0||$quoted_pid===$pid_of_this_post){
						continue;
						}
					$qb_quoted_by[$quoted_pid][]=$post;
					}
				}
			//Also match [quote=Username;123] style which some clients produce
			$matches2=array();
			preg_match_all('/\[quote=[^\]\;]*\;(\d+)\]/i',$message,$matches2);
			if(!empty($matches2[1])){
				foreach($matches2[1] as $quoted_pid){
					$quoted_pid=(int)$quoted_pid;
					if($quoted_pid<=0||$quoted_pid===$pid_of_this_post){
						continue;
						}
					$qb_quoted_by[$quoted_pid][]=$post;
					}
				}
			//(Optional) Add more detection patterns here if you have other quote formats to support.
			}
		}
	/**
	 * Append a "Quoted by" div to each post's message if other posts quote it.
	 *
	 * $post is passed by reference by the postbit hook.
	 */
	function qbpp_append_quoted_by_div(&$post){
		global $thread,$qb_quoted_by,$mybb;
		if(empty($post['pid'])){
			return;
			}
		$pid=(int)$post['pid'];
		if(empty($qb_quoted_by)||empty($qb_quoted_by[$pid])){
			return;
			}
		$tid=(int)$thread['tid'];
		$links=array();
		foreach($qb_quoted_by[$pid] as $qpost){
			if(empty($qpost['pid'])){
				continue;
				}
			$qpid=(int)$qpost['pid'];
			//Build link to the quoting post. Including &pid= helps MyBB jump to the correct page.
			$url=htmlspecialchars($mybb->settings['bburl'] . "/showthread.php?tid={$tid}&pid={$qpid}#pid{$qpid}", ENT_QUOTES,'UTF-8');
			if(true){//Imageboard-style--refers only to the ID of the quoting post
				$text=htmlspecialchars($qpid,ENT_QUOTES,'UTF-8');
				}
			elseif(!empty($qpost['subject'])){//Original AI slop, commented out here: Prefer subject if available, otherwise show "Post by USER".
				$text=htmlspecialchars($qpost['subject'],ENT_QUOTES,'UTF-8');
				}
			else{
				$username=!empty($qpost['username'])?$qpost['username']:'Unknown';
				$text = htmlspecialchars("Post by {$username}",ENT_QUOTES,'UTF-8');
				}
			$links[]="<a href=\"{$url}\">{$text}</a>";
		}

		if (!empty($links)) {
			// You can change the class name and markup to match your theme or a template.
			$div = '<div class="quoted-by-block" style="margin-top:8px;font-size:0.95em;color:#555;">Quoted by: ' . implode(', ', $links) . '</div>';
			// Append to the parsed message HTML. We already escaped link text & URL.
			$post['message'] .= $div;
		}
	}
	?>
