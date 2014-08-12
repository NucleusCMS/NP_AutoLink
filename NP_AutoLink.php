<?php
/*
	0.5.7 - refactor
	0.5.6 - refactor
	0.5.1 - support multi language
	0.5 - support for SqlTablePrefix
	0.4 - new option: links can be open in new window (default: off)
	0.3 - userdefined placeholders for [at] and [dot]
	0.2 - new option: rewrite mail addresses with [at] and [dot]
	0.1 - initial release
*/

class NP_AutoLink extends NucleusPlugin {

	function getName()           { return 'AutoLink'; }
	function getAuthor()         { return 'Kai Greve, yama'; }
	function getURL()            { return 'http://japan.nucleuscms.org/wiki/plugins:autolink'; }
	function getVersion()        { return '0.5.7'; }
	function getDescription()    { return _ATLK01;}
	function getEventList()      { return array('PreItem', 'PreComment');}
	function supportsFeature($w) { return ($w==='SqlTablePrefix') ? 1 : 0 ; }

	function init()
	{
		$lang_path = $this->getDirectory() . 'language/';
		$language = str_replace( array('\\','/'), '', getLanguageName());
		if(is_file("{$lang_path}{$language}.php")) include_once("{$lang_path}{$language}.php");
		else                                       include_once("{$lang_path}english.php");
		$this->language = $language;
	}

	function event_PreItem(&$data)
	{
		$data[item]->body = $this->treatment($data[item]->body);
		$data[item]->more = $this->treatment($data[item]->more);
	}

	function event_PreComment(&$data)
	{
		$nw = $this->newWindow();
		$data['comment']['body'] = str_replace('rel="nofollow">', $nw.' rel="nofollow">', $data['comment']['body']);
	}

	function treatment($content)
	{
		$split = explode('>', $content);
		foreach($split as $i=>$text)
		{
			if(strpos($text,'<')!==false)
			{
				list($text,$tag) = explode('<',$text,2);
				$tag = '<' . $tag;
			}
			else $tag='';
			if(strpos($text,'://')!==false || strpos($text,'www')!==false)
				$text = $this->replace($text);
			if($i!=0) $split[$i] = ">{$text}{$tag}";
		}
		$text = join('', $split);
		$text = $this->treatmentEmail($text);
		return $text;
	}
	
	function replace($text)
	{
		$nw = $this->newWindow();
		if ($this->getOption('InternetAddress') === 'yes')
		{
			$in=array(
				'`^(https?|ftp|file|rtsp|callto|mms)(://[[:alnum:]_?=&%;+-./:~#@$]+)`',
				'`([^=\">])(https?|ftp|file|rtsp|callto|mms)(://[[:alnum:]_?=&%;+-./:~#@$]+)`',
				'`(\s)(www\.[[:alnum:]_?=&%;+-./:~#@$]+)`'
				);
			$out=array(
				'<a href="$1$2"'.$nw.'>$1$2</a>',
				'$1<a href="$2$3"'.$nw.'>$2$3</a>',
				'$1<a href="http://$2"'.$nw.'>$2</a>'
				);
			$text = preg_replace($in, $out, $text);
		}
		return $text;
	}
	
	function newWindow()
	{
		switch ($this->getOption('NewWindow'))
		{
			case 'no':
				return '';
			case 'yes':
				return ' target="_blank"';
			case 'yesjs':
				return "onclick=\"javascript:window.open(this.href, '_blank'); return false;\"";
			default:
				return '';
		}
	}
	
	function treatmentEmail($text)
	{
		if(strpos($text,'@')===false) return $text;
		$at = $this->getOption('at');
		$dot = $this->getOption('dot');
		$emailPattern = '/(\s)([a-zA-Z0-9\._-]+)@([a-zA-Z0-9\._-]+)\.([a-zA-Z]{2,5})/s';
		if ($this->getOption('MailAddress') === 'yes')
		{
			if($this->getOption('RewriteMailAddress') === 'no')
				$text = preg_replace($emailPattern, '$1<a href="mailto:$2@$3.$4">$2@$3.$4</a>',$text);
			else
				$text = preg_replace($emailPattern, '$1<a href="mailto:$2'.$at.'$3'.$dot.'$4">$2'.$at.'$3'.$dot.'$4</a>',$text);
		}
		elseif ($this->getOption('RewriteMailAddress') == 'yes')
		{
			$text = preg_replace($emailPattern, '$1$2'.$at.'$3'.$dot.'$4',$text);
		}
		return $text;
	}

	function install()
	{
		$this->createOption('InternetAddress'    ,_ATLK02,'yesno','yes');
		$this->createOption('NewWindow'          ,_ATLK03,'select','no',_ATLK08);
		$this->createOption('MailAddress'        ,_ATLK04,'yesno','yes');
		$this->createOption('RewriteMailAddress' ,_ATLK05,'yesno','yes');
		$this->createOption('at'                 ,_ATLK06,'text','&#64;');
		$this->createOption('dot'                ,_ATLK07,'text','&#46;');
	}
}
