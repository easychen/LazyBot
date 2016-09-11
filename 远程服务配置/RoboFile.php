<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    // define public methods as commands

    /**
	 *  创建一个 Shadowsocks 服务	
     */
    public function build_shadowsocks_server()
    {
    	$ip = $this->ask('请填写要安装SS的远程服务器IP');
		$user = $this->askDefault('请填写用户名' , 'root');
		
		$port = $this->askDefault('请填写SS服务远程端口' , '8389');
		$password2 = $this->ask('请填写SS服务用密码' , true );

		$this->say('创建配置文件');
		$this->_remove('ss.json');
		$this->_copy( 'SSconf/ss.sample.json' , 'ss.json');
		
		$this->taskReplaceInFile('ss.json')
			->from(array( '[ip]' , '[port]' , '[password]' ))
	 		->to(array( $ip , $port , $password2 ))
	 		->run();

		
		$this->taskRsync()
			->fromPath('./ss.json')
			->toHost($ip)
			->toUser($user)
			->toPath('/root')
			->wholeFile()
			->verbose()
			->progress()
			->humanReadable()
			->stats()
			->run();

		$this->taskSshExec( $ip , $user )
    		->stopOnFail(true)
			->exec('apt-get update')
			->exec('apt-get install -y python-gevent python-pip')
			->exec('apt-get install  -y python-m2crypto')
			->exec('pip install shadowsocks')
			->exec('nohup ssserver -c /root/ss.json  > log &')
    		->run();
    }
}