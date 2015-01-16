<?php 
/*
 * 作者：Giuem
 * 博客地址：http://www.giuem.com/
 * 转载请保留版权！
 */
header("Content-type: text/html; charset=utf-8"); 
$qq = '';//QQ号
$sid = '';//填写sid的值
$con = '';//自定义内容，留空则使用simsimi
$api = '';//如果填写，则使用图灵
$qzone = new qzone($qq,$sid);
class qzone{
	private $sid ='';
	public function __construct($qq,$sid){
		$this->sid = $sid;
		$url = "http://ish.z.qq.com/infocenter_v2.jsp?B_UID={$qq}&sid={$sid}&g_ut=2";
		$re = $this->fetch($url);
		$this->getsaying($re);
	}
	private function fetch($url,$postdata=null){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 10.0; Windows Phone 8.0; Trident/6.0; IEMobile/10.0; ARM; Touch; NOKIA; Lumia 820)");
        if($postdata!=null) curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $re = curl_exec($ch);
        curl_close($ch);
        return $re;
	}
	private function getsaying($html){
		preg_match_all('/<\/a>:(.*?)评论\(\d\)<\/a>/',$html,$match);
		foreach($match[0] as $k){
			if(strstr($k,'评论(0)')){
                $k = str_replace(PHP_EOL, '', $k);
                $k = str_replace('&#10;', '', $k);
                $k = html_entity_decode($k);
				preg_match('/<\/a>:(.*?)<span class="txt-fade">/',$k,$content);
				preg_match('/myfeed_mood.jsp\?sid=.*&B_(.*?)&t1_source/',$k,$data);
				$content = preg_replace('/<img[^>]+>/', '', $content[1]);
                echo '找到一条说说：'.$content.' 机器人的回复是：';
				$data = 'B_'.$data[1];
				$content = talk($content);
                echo $content.'<br />';
                $this->postcomment($content,$data);
				sleep(3);
			}
		}
		
	}
	private function postcomment($content,$data){
        $postdata = "content={$content}&{$data}&t1_source=1&feedcenter_pn=1&flag=1&type=all&channel=0&back=false&offset=0&ic=false&dl=null&to_tweet=0&submit=%E8%AF%84%E8%AE%BA";
        $this->fetch("http://blog30.z.qq.com/mood/mood_reply.jsp?sid={$this->sid}&g_ut=2",$postdata);	
	}
 
}
 
function talk($content){
    global $con,$api;
    if($con) return $con;
	$content = str_replace(' ', '', $content);
	if($api) return tuling($content,$api);
    $ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,'http://www.simsimi.com/talk.htm');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER,1);
	curl_setopt($ch, CURLOPT_NOBODY, false);
	$rs = curl_exec($ch);
	preg_match_all('/Set-Cookie: (.+)=(.+)$/m', $rs, $regs);
	foreach($regs[1] as $i=>$k);
	$cc=str_replace(' Path','' ,$k);
	$cc='simsimi_uid=507454034223;'.$cc;
	$re = HTTPClient('http://www.simsimi.com/func/reqN?lc=ch&ft=1.0&req='.$content.'&fl=http%3A%2F%2Fwww.simsimi.com%2Ftalk.htm',$cc);
	$re = json_decode($re,true);
	return $re['sentence_resp'];
}
function HTTPClient($url,$cookie){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_COOKIE,$cookie);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$re = curl_exec($ch);
	curl_close($ch);
	return $re;
}
function tuling($content,$key){
	$re = json_decode(file_get_contents('http://www.tuling123.com/openapi/api?key='.$key.'&info='.$content),true);
	$code = $re['code'];
	switch ($code){
		case 100000:
			$content = $re['text'];
			break;
		case 200000:
			$content = $re['text'].$re['url'];
			break;
		case 302000:
			$list = $re['list'];
			$i = rand(0,count($list)-1);
			$list = $list[$i];
			$content = $re['text'].'：'.$list['article'];
			break;
		case 305000:
			$list = $re['list'];
			$i = rand(0,count($list)-1);
			$list = $list[$i];
			$content = '起始站：'.$list['start'].'，到达站：'.$list['terminal'].'，开车时间：'.$list['starttime'].'，到达时间：'.$list['endtime'].'。亲，更多信息请上网查询哦！';
			break;
		case 306000:
			$list = $re['list'];
			$i = rand(0,count($list)-1);
			$list = $list[$i];
			$content = '航班：'.$list['flight'].'，航班路线：'.$list['route'].'，起飞时间：'.$list['starttime'].'，到达时间：'.$list['endtime'].'。亲，更多信息请上网查询哦！';
			break;
		case 310000:
			$list = $re['list'];
			$i = rand(0,count($list)-1);
			$list = $list[$i];
			$content = $list['info'].'中奖号码：'.$list['number'].'。亲，小赌怡情，大赌伤身哦！';
			break;
		case 40004:
			$content = '今天累了，明天再聊吧';
			break;
		default:
			//$content = xiaoji($content);
			$content = $re['text'];
	}
	return $content;
}
?>
