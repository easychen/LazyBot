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
	 * 往 Mac 的一个目录放入最新版的 LazyPHP
	 */
	public function download_lazyphp()
	{
		$path = $this->askDefault( '请输入要放入的目录：' , '/Users/Easy/Downloads/LP' );
		
		if( !file_exists( $path ) )
		{
			$this->say('目录不存在');
			return false;
		}

		$this->taskExecStack()
    		->stopOnFail(true)
    		->exec('curl -k -o ~/lp4lastest.zip  https://codeload.github.com/easychen/LazyPHP4/zip/master' )
    		->exec( 'unzip -o ~/lp4lastest.zip -d ~/LP4 ' )
    		->exec( 'cp -Rf ~/LP4/LazyPHP4-master/* ' . $path )
    		->dir( $path )
    		->exec( 'cp -f sample.htaccess .htaccess ' )
    		->exec( 'composer install --no-dev --no-plugins --no-scripts' )
    		->run();
    	
    	$this->say( '完成' );	
	}

	/**
	 * 往服务器上安装Docker 环境
     */
	public function installDocker()
	{
		$ip = $this->ask('请填写要安装Docker的远程服务器IP');
		$user = $this->askDefault('请填写用户名' , 'ubuntu');
		//$password = $this->ask('请填写服务器密码' , true );

		$this->taskSshExec( $ip , $user )
    		//->identityFile($ifile)
    		->stopOnFail(true)
    		->exec( 'wget -qO- https://get.docker.com/ | sh' )
    		->exec( 'sudo usermod -aG docker $(whoami)' )
    		->run();	
	}


	/**
	 * 从 Mac 向 Ubuntu 部署一个通过 Git 更新的 Web 目录
	 * 参考文章：http://get.ftqq.com/8504.get 
     */
	public function deploy_git_web_publish()
	{
		$ip = $this->ask( '请输入服务器的域名：' );
		$user = $this->askDefault('请填写用户名' , 'root');
		$ifile = $this->askDefault('请输入认证文件地址' , '/Users/Easy/Dropbox/roboScript/ftqq.id_rsa');

		$localgit = $this->askDefault( '请输入 Mac 电脑上的Git 目录地址：' , '~/Code/gitcode/'.$ip );
		$remotegit = $this->askDefault( '请输入服务器的Git Repo地址：' , '/data1/repo/'.$ip );
		$remoteweb = $this->askDefault( '请输入要部署在服务器上的 Web 目录（绝对Path）：' , '/data1/www/'.$ip );

		// 在服务器上创建 Git 仓库
		$this->taskSshExec( $ip , $user )
    		//->identityFile($ifile)
    		->stopOnFail(true)
    		->exec( 'apt-get install git ' )
    		->exec( 'mkdir -p '.$remotegit )
    		->exec( 'cd '.$remotegit )
    		->exec( 'git --bare init' )
    		->run();

    	// 系统默认的 git 命令不支持认证文件，所以用 git.sh 这个包装过的
    	$gitsh = dirname(__FILE__) . '/../git.sh';

    	// 然后本地连接并进行第一次推送
    	$this->taskExecStack()
    		->exec( 'mkdir -p '.$localgit )	
    		->exec( 'cd ' .$localgit )	
    		->exec( 'echo "LazyBot init" > REDEME' )	
    		->exec( 'git init' )	
    		->exec( 'git add REDEME' )	
    		->exec( 'git commit -m "init"' )	
    		->exec( 'chmod +x '. $gitsh)	
    		->exec( $gitsh . ' -i ' .  $ifile . '  remote add origin ' . $user . '@' . $ip . ':' . $remotegit )
    		->exec( $gitsh . ' -i ' .  $ifile . ' git push origin master' )	
    		->run();

    	$vfile = 'post-receive';
    	$this->say('创建Git Hook文件');
		$this->_copy( 'gitHook/sample.sh' , $vfile);

		$this->taskReplaceInFile( $vfile )
			->from(array( '[path]' ))
	 		->to(array( $remoteweb ))
	 		->run();
    	
    	// 通过 Rsync 传 Hook 文件
	 	$this->taskRsync()
			->fromPath($vfile)
			->toHost($ip)
			->toUser($user)
			->remoteShell('ssh -i '.$ifile )
			->toPath($remotegit . '/hooks')
			->wholeFile()
			->verbose()
			->progress()
			->humanReadable()
			->stats()
			->run();	

		// 修改文件属性	
		$this->taskSshExec( $ip , $user )
    		->identityFile($ifile)
    		->stopOnFail(true)
    		->exec( 'chmod +x ' . $remotegit . '/hooks/post-receive' )
    		->run();	

    		
		$this->_remove( $vfile);

		$this->say('配置完成，注意本地的 Git 目录要通过 git.sh -i <identity file> command 的方式提交，否则会提示输入密码。最简单的方式是使用 Root 的密码，可以用过 passwd 命令创建');

		$this->say('参考命令 ssh root@'.$ip .' -i ' . $ifile );	
		$this->say('passwd ' );	

	}
	


	/**
	 *  向 Ubuntu 的一个空目录或者不存在的目录部署 LazyPHP 的最新版
     */
	public function deploy_lazyphp()
	{
		$ip = $this->ask( '请输入服务器的域名：' );
		$user = $this->askDefault('请填写用户名' , 'root');
		$path = $this->askDefault( '请输入要部署在服务器上的目标地址（绝对Path）：' , '/data1/www/'.$ip );
		$ifile = $this->askDefault('请输入认证文件地址' , '/Users/Easy/Dropbox/roboScript/ftqq.id_rsa');

		$this->taskSshExec( $ip , $user )
    		->identityFile($ifile)
    		->stopOnFail(true)
    		->exec('git --git-dir=/dev/null clone --depth=1 https://github.com/easychen/LazyPHP4.git ' . $path )
    		->exec( 'cd ' . $path )
    		->exec('cp sample.htaccess .htaccess ' )
    		->exec('curl -sS https://getcomposer.org/installer | php ' )
    		->exec('php composer.phar install --no-dev --no-plugins --no-scripts' )
    		->exec('chown -R www-data:www-data .' )
    		->exec('a2enmod rewrite' )
    		->exec('service apache2 restart' )
    		->run();

	}


	/**
	 *  向 Ubuntu 添加一个 apache2 的 vhost 配置
     */
    public function add_domain_to_apache()
	{
		$domain = $this->ask( '请输入要添加的域名：' );
		$path = $this->askDefault( '请输入该域名在服务器上的绝对Path：' , '/data1/www/'.$domain );
		$email = $this->askDefault( '请输入管理员邮箱：' , 'easychen@qq.com' );

		$vfile = $domain.'.conf';

		$this->say('创建vhost文件');
		$this->_copy( 'vhostConf/sample.conf' , $vfile);

		$this->taskReplaceInFile( $vfile )
			->from(array( '[email]' , '[domain]' , '[path]' ))
	 		->to(array( $email , $domain , $path ))
	 		->run();

	 	if( strtolower( $this->askDefault( '是否要发布到 Ubuntu 服务器的 apache 目录？' , 'yes' ) ) == 'yes' )
	 	{
	 		$ip = $this->askDefault('请填写远程服务器IP或域名：' , $domain );
			$user = $this->askDefault('请填写用户名' , 'root');
			$ifile = $this->askDefault('请输入认证文件地址' , '/Users/Easy/Dropbox/roboScript/ftqq.id_rsa');

			// 通过 Rsync 发送文件
			$this->taskRsync()
			->fromPath($vfile)
			->toHost($ip)
			->toUser($user)
			->remoteShell('ssh -i '.$ifile )
			->toPath('/etc/apache2/sites-enabled/')
			->wholeFile()
			->verbose()
			->progress()
			->humanReadable()
			->stats()
			->run();

			// 如果 Path 还不存在，创建目录
			// 并重启 apache
			$this->taskSshExec( $ip , $user )
    		->identityFile($ifile)
    		->stopOnFail(true)
    		->exec('sudo mkdir -p '.$path )
    		//->exec('echo "Hello Kitty " > '.$path . '/index.php' )
    		->exec('sudo chown www-data:www-data -R '.$path )
			->exec('sudo service apache2 graceful')
    		->run();

    		$this->_remove( $vfile);

	 	}	

	}

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
			->exec('export LC_ALL=C')
			->exec('apt-get update')
			->exec('apt-get install -y python-pip')
			->exec('apt-get install  -y libsodium-dev')
			->exec('pip install https://github.com/shadowsocks/shadowsocks/archive/master.zip -U')
			->exec('nohup ssserver -c /root/ss.json  > log &')
    		->run();
	}
	

}