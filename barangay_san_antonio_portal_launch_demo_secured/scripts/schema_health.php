<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only\n");}
require dirname(__DIR__).'/app/Core/Database.php';require dirname(__DIR__).'/app/Services/SchemaHealth.php';
try{$result=SchemaHealth::inspect(Database::connection());echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;exit(!empty($result['launch_demo_schema_detected'])?0:2);}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
