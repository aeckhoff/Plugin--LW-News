<?php

/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 2012 Logic Works GmbH
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *  
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 * ************************************************************************* */

class lw_news extends lw_advanced_plugin 
{
	function __construct() 
	{
		parent::__construct();
		$reg = lw_registry::getInstance();
		$this->request 		= $reg->getEntry("request"); 
		$this->response		= $reg->getEntry("response"); 
	}
	
	function buildPageOutput() 
	{
        if ($this->request->getInt("rss")==1) {
            $data['title']          = utf8_encode("News");
            $data['link']           = utf8_encode("http://www.juelich.de/");
            $data['description']    = "News from Juelich";
            $data['copyright']      = "Stadt Juelich";
            $data['pubdate']        = date('YmdHis');
            $data['author']         = "Stadt Juelich";
            $data['entries']        = array();
            
            if ($this->params['cat']) {
                $this->db->setStatement("SELECT * FROM t:lw_items WHERE itemtype = :itemtype AND opt8text = :opt8text ORDER BY free_date DESC");
	            $this->db->bindParameter('itemtype',   	's',    'lwnews');
	            $this->db->bindParameter('opt8text',   	's',    $this->params['cat']);
	            $news = $this->db->pselect();	
            } else {
                $this->db->setStatement("SELECT * FROM t:lw_items WHERE itemtype = :itemtype ORDER BY free_date DESC");
	            $this->db->bindParameter('itemtype',   	's',    'lwnews');
	            $news = $this->db->pselect();	
            }            
	
		    if (is_array($news) && count($news) > 0)
		    {
		        foreach($news as $message)
		        {
			        if (date("Ymd") >= $message['free_date'] && (date("Ymd") <= $message['opt7text'] || !$message['opt7text'] ) ) {
			            $array['title']         = utf8_encode(substr($message['description'], 0, 255));
			            $array['description']   = "<![CDATA[".utf8_encode(strip_tags($message['info']))."]]>";
			            $array['link']          = utf8_encode("http://www.juelich.de/");
			            $array['id']            = $message['id'];
			            $data['entries'][]      = $array;
			        }
		        }
		    }
		    $feed = new lw_feed($data);
		    $feed->createFeed("rss20");
		    header("Content-Type: application/xml; charset=UTF-8");
		    echo $feed->getFeed();
		    exit();        
        }
        if ($this->params['teaser'] == 1) {
            $page = lw_page::getInstance($this->request->getIndex());
            if ($page->getPageValue('page_type') == 8) {
                return false;
            }
            return $this->buildTeaser();
		}
		else {
		    return $this->buildMainList();
		}
	}
	
	function loadNews()
	{
        if ($this->params['cat']) {
            $this->db->setStatement("SELECT * FROM t:lw_items WHERE itemtype = :itemtype AND opt8text = :opt8text ORDER BY free_date DESC");
	        $this->db->bindParameter('opt8text',   	's',    $this->params['cat']);
        } 
        else {
            $this->db->setStatement("SELECT * FROM t:lw_items WHERE itemtype = :itemtype ORDER BY free_date DESC");
        }
        $this->db->bindParameter('itemtype',   	's',    'lwnews');
        if (intval($this->params['amount'])>0) {
            $erg = $this->db->pselect(0, intval($this->params['amount']));
        }
        else {
            $erg = $this->db->pselect();
        }
        return $erg;
	}
	
	function buildMainList() 
	{
        $erg = $this->loadNews();

        foreach($erg as $message) {
	        if (date("Ymd") >= $message['free_date'] && (date("Ymd") <= $message['opt7text'] || $message['opt7text'] < 1) ) {
	            $erg2[] = $message;
	        }
	    }
	
        if (count($erg2)>0) {
            $view           = new lw_adminview(dirname(__FILE__).'/templates/news.tpl.phtml');
            $view->items    = $erg2;
            $view->rsslink  = $this->buildUrl(array("rss"=>1));
            return $view->render();
        }
	    return false;
	}
	
	function buildTeaser() 
	{
        $erg = $this->loadNews();

        foreach($erg as $message) {
            if (date("Ymd") >= $message['free_date'] && (date("Ymd") <= $message['opt7text'] || !$message['opt7text'] ) ) {
                $erg2[] = $message;
            }
        }	

        if (count($erg2)>0) {
            $view = new lw_adminview(dirname(__FILE__).'/templates/news_teaser.tpl.phtml');
            $view->items = $erg2;
            $view->newsid = $this->params['newspage'];
            return $view->render();
        }
	    return false;
	}
}
