<?php
declare(strict_types=1);
final class RateLimiter
{
    public static function allow(string $bucket, string $identity, int $limit, int $windowSeconds): bool
    {
        $dir=dirname(__DIR__,2).'/storage/rate-limits';
        if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))return false;
        $file=$dir.'/'.preg_replace('/[^a-z0-9_-]/i','',$bucket).'.json';
        $fp=fopen($file,'c+');if(!$fp)return false;
        try{flock($fp,LOCK_EX);$raw=stream_get_contents($fp);$rows=json_decode($raw?:'[]',true);if(!is_array($rows))$rows=[];$now=time();$key=hash('sha256',$identity);$rows=array_values(array_filter($rows,static fn($r)=>is_array($r)&&(int)($r['at']??0)>$now-$windowSeconds));$count=0;foreach($rows as $r)if(($r['key']??'')===$key)$count++;if($count >= $limit)return false;$rows[]=['key'=>$key,'at'=>$now];ftruncate($fp,0);rewind($fp);fwrite($fp,json_encode($rows));fflush($fp);return true;}finally{flock($fp,LOCK_UN);fclose($fp);}
    }
}
