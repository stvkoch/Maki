<?php
/**
 * install 
 *  dependencies
 *    libssh (brew install libssh) or (apt-get install libssh)
 *    openssl (brew install openssl) or (apt-get install open)
 *    pecl install ssh2 channel://pecl.php.net/ssh2-0.11.3
 * 
 * 
 * Command line usage
 *  cd ~/www/application
 *  maki
 *  Maki>help
 * 
 * 
 * 
 * 
 * 
 * //configuration SSH
 *  SSH_USERNAME, 
 *  SSH_PASSWORD, 
 *  SSH_HOST, 
 *  SSH_PORT,
 *  SSH_PUBLIC_KEY,
 *  SSH_PRIVATE_KEY,
 *  SSH_SECRET
 * 
 * //configuration PATHs
 *  LOCAL_BASE_DIR, 
 *  REMOTE_BASE_DIR
 * 
 * 
 */
require_once 'terminal.php';
include_once 'shell.php';



//Registry and stacks
class Maki{

  static $STACK_TASKS = array();

  static $STACK_ENVIRONMENTS = array();

  static $CONST = array();

  static $CURRENT_ENVIRONMENT;

}



//consegue-se abrir multiplos environment, e assim executar comandos no ambiente desejado
class Environment{

 public $environment;

 public $config = array();

 public $ssh_con = null;

 public $local_con = null;

 function __construct($environment){
  $this->environment = $environment;
  $this->config = get_config_env($this->environment) + get_config_env('master');
 }

 function ssh(){
  if($this->ssh_con==null)
    $this->ssh_con = new SSH($this->config);//
  return $this->ssh_con;
 }

 function local(){
  if($this->local_con==null)
    $this->local_con = new Shell($this->config['LOCAL_BASE_DIR']);//
  return $this->local_con;
 }

 function name(){
  return $this->environment;
 }

}

/**
 * remote shell ssh2
 * @dependency php
 *  ssh2
 */
class SSH{

  private $config = array('SSH_ADVERTISE'=>'ssh-dss', 'SSH_PORT'=>22);

  private $con;

  private $shell_stream;

  function __construct($config){

    $this->config= $config + $this->config;
    if(isset($this->config['SSH_PUBLIC_KEY']) && isset($this->config['SSH_PRIVATE_KEY'])){
      $this->con = ssh2_connect($this->config['SSH_HOST'], $this->config['SSH_PORT'], array('hostkey'=>'ssh-dss'));
      if(!$this->con)
        terminal()->WriteLine("Connect to host fail. (".$this->config['SSH_HOST'].")",'red');
      if(!ssh2_auth_pubkey_file($this->con, $this->config['SSH_USERNAME'], $this->config['SSH_PUBLIC_KEY'], $this->config['SSH_PRIVATE_KEY']))
        terminal()->WriteLine("Authenticate using a public key fail.",'red');
    }else{
      $this->con = ssh2_connect($this->config['SSH_HOST'], $this->config['SSH_PORT']);
      if(!$this->con)
        terminal()->WriteLine("Connect to host fail. (".$this->config['SSH_HOST'].")",'red');
      if(!ssh2_auth_password($this->con, $this->config['SSH_USERNAME'], $this->config['SSH_PASSWORD']))
        terminal()->WriteLine("SSH authenticated. (".$this->config['SSH_USERNAME'].")",'red');
    }
    $this->shell_stream = ssh2_shell($this->con, 'ansi', null, 120, 24, SSH2_TERM_UNIT_CHARS);
  }

  function exec($command, $send_output=true){
    $new_command = 'echo [start];'.$command.';echo [end]';
    fwrite( $this->shell_stream, $new_command. PHP_EOL);
    $output = "";
    $start = false;
    $start_time = time();
    $max_time = 10; //time in seconds
    while(((time()-$start_time) < $max_time)) {
      $line = fgets($this->shell_stream);
      if(!strstr($line,$command)) {
        if(preg_match('/\[start\]/',$line)) {
          $start = true;
        }elseif(preg_match('/\[end\]/',$line)) {
          if($send_output) echo $output;
          return $output;
        }elseif($start){
          $output[] = $line;
        }
      }
    }
  }

  function __destruct(){}

  function scp_local_remote($local_file, $remote_file, $mode=0644){
    return ssh2_scp_send($this->con, $local_file, $remote_file);
  }

  function scp_remote_local($remote_file, $local_file, $mode=0644){
    return ssh2_scp_recv($this->con, $remote_file, $local_file, $mode);
  }

}



/**
 * Set configuration on environment
 * set('SSH_USERNAME', 'username');
 * set('SSH_PASSWORD', 'passcrypts');
 * set('SSH_PUBLIC_KEY', 'publickeypath');
 * set('SSH_PRIVATE_KEY', 'publickeypath');
 * set('SSH_PASSPHRASE', 'publickeypath');
 * 
 * //only when use configurations expecific of environment
 * set('SSH_USERNAME', 'username', 'staging');
 * set('SSH_PASSWORD', 'passcrypts', 'staging');
 */
function set($cons, $value, $env='master'){
  if(isset(Maki::$CONST[$env][$cons]))
    throw new InvalidArgumentException("@$cons exists", 1);
  return Maki::$CONST[$env][$cons]=$value;
}
function get($cons, $env='master'){
  return Maki::$CONST[$env][$cons];
}
function get_config_env($env='master'){
  return (isset(Maki::$CONST[$env]))?Maki::$CONST[$env]:array();
}



/**
 * Create new task. This is the heart of your tasks file.
 * @example
 * //create tasks.php on root of your project
 * <?
 * set('SSH_USERNAME', 'username1', 'production');//config environment production
 * set('SSH_PASSWORD', 'passcrypts1', 'production');//config environment production
 * set('SSH_HOST',     'production.hostname.local', 'staging');//config environment staging
 * task('test', 'app', function(){
 *  require_once 'AppTest.php';
 *  require_once 'PHPUnit.php';
 *  $suite  = new PHPUnit_TestSuite("AppTest");
 *  $result = PHPUnit::run($suite);
 *  if($result ->errorCount()>0){
 *    message('Errroor you not can tasks', 'red');
 *  }else{
 *    maki('git');//see maki
 *    maki('tasks', 'productio');
 *  }
 * });
 */
function task(){
  $args = func_get_args();
  $callback = array_pop( $args );
  _nested_array($args, $callback, Maki::$STACK_TASKS);
}



/**
 * Execute command on remote host via ssh configuration and on current environment
 */
function remote($command, $send_output=false){
  terminal()->WriteLine($command, 'cyan');
  return current_environment()->ssh()->exec($command, $send_output);
}



/**
 * Execute command on local
 */
function local($command, $send_output=false){
  terminal()->WriteLine($command);
  return current_environment()->local()->exec($command);
}



/**
 * recursive copy from remote host to local host
 */
function remote_local($path_remote, $path_local, $create_mode = 0644){
  return current_environment()->ssh()->scp_remote_local($path_remote, $path_local, $create_mode);
}


/**
 * recursive copy from local file to remote host
 */
function local_remote($path_local, $path_remote, $create_mode = '0644'){
  return current_environment()->ssh()->scp_local_remote($path_local, $path_remote, $create_mode);
}






/**
 * Run hierarchy tasks
 * 
 * @example 
 * task('git', 'add', function(){
 *      local('git add .');
 *    });
 * task('git', 'commit', function(){
 *      $message = prompt('Commit message:');
 *      local("git commit -m '".$message."'");
 *    });
 * task('git', 'push', function(){
 *      remote('git push');
 *    });
 * 
 * Maki>maki git//run tasks git hierarchy: add, after commit and push
 * or if like run single task
 * Maki>maki git add
 * Maki>maki git commit
 * 
 */
function maki(){
  if(current_environment()==null)
    open_environment('master');
  $tasks= array();
  _get_tasks(func_get_args(), $tasks, Maki::$STACK_TASKS);
  if(!count($tasks)) throw new TaskNotFountException("Task(s) not found", 1);
  $result=array();
  foreach ($tasks as $callback) {
    if(is_callable($callback)){
      $result[] = call_user_func($callback);
    }
  }
  //if only one task
  return (count($result)==1)?$result[0]:$result;
}


/**
 * use_environment
 * @example
 *    set('SSH_USERNAME', 'username1', 'production');//config environment production
 *    set('SSH_PASSWORD', 'passcrypts1', 'production');//config environment production
 *    set('SSH_HOST',     'production.hostname.local', 'staging');//config environment staging
 *    set('SSH_USERNAME', 'username2', 'staging');//config environment staging
 *    set('SSH_PASSWORD', 'passcrypts2', 'staging');//config environment staging
 *    set('SSH_HOST',     'staging.hostname.local', 'staging');//config environment staging
 *    task('tasks', function(){
 *      open_environment('staging');
 *      remote('cd staging_base_name_dir');
 *      remote('git pull');
 * 
 *      open_environment('production');
 *      remote('cd sprduction_base_name_dir');
 *      remote('git pull');
 *      close_environment('production');
 *      
 *      //now all commands below run on environment 'staging'
 *    });
 */
function open_environment($environment){
  Maki::$STACK_ENVIRONMENTS[]=new Environment($environment);
}


/**
 * Remove from stack environment
 */
function close_environment(){
  array_pop(Maki::$STACK_ENVIRONMENTS);
}


/**
 * Return current environment on stack
 * é util quando queres ter mais de um ambiente de tasks
 *  You can config lot environment. And then deside who is execute command. You think is function very similar on mysql use command, on you select database name and run any command.
 *  @example
 *    set('SSH_USERNAME', 'username1', 'production');//config environment production
 *    set('SSH_PASSWORD', 'passcrypts1', 'production');//config environment production
 *    set('SSH_HOST',     'production.hostname.local', 'staging');//config environment staging
 *    set('SSH_USERNAME', 'username2', 'staging');//config environment staging
 *    set('SSH_PASSWORD', 'passcrypts2', 'staging');//config environment staging
 *    set('SSH_HOST',     'staging.hostname.local', 'staging');//config environment staging
 *    task('tasks', 'staging', function(){
 *      open_environment('staging');
 *      remote('cd staging_base_name_dir');
 *      remote('git pull');
 *    });
 */
function current_environment(){
  if(count(Maki::$STACK_ENVIRONMENTS))
    return Maki::$STACK_ENVIRONMENTS[count(Maki::$STACK_ENVIRONMENTS)-1];
  else
    return false;
}



/**
 * return terminal client object
 * 
 * 
 **/
function terminal(){
  static $cli=null;
  if($cli==null)
    $cli = new CLICommander();
  return $cli;
}


/**
 * print message on consoles
 * 
 * 
 **/
function message($message, $fgColor = null, $bgColor = null, $style = null){
  terminal()->WriteLine($message, $fgColor, $bgColor, $style);
}


/**
 * send message to console and return typed input
 * @example 
 * task('git', 'add', function(){
 *      local('git add .');
 *    });
 * task('git', 'commit', function(){
 *      $message = prompt('Commit message:');
 *      local("git commit -m '".$message."'");
 *    });
 * task('git', 'push', function(){
 *      remote('git push');
 *    });
 * 
 * Maki>maki git//run tasks git hierarchy: add, after commit and push
 * or if
 **/
function prompt($message, $fgColor = null, $bgColor = null, $style = null){
  return terminal()->Prompt($message, $fgColor, $bgColor, $style);
}



function print_tasks($stasks=false, $inc=' - '){
  if(!$stasks) print_tasks(Maki::$STACK_TASKS);
  foreach ($stasks as $key => $stack) {
    terminal()->WriteLine($inc . $key);
    if(is_array($stack)){
        print_tasks($stack, '   '.$inc);
    }
  }
}



/**
 * @private
 * 
 * 
 **/
function _get_tasks($path, &$tasks=array(), $stacks){
  $f = array_shift($path);
  if($f && isset($stacks[$f])){
    $task = $stacks[$f];
    if(is_callable($task))
      $tasks[]=$task;
    else{
      if(count($path)==0)
        $tasks = array_merge($tasks, array_values($stacks[$f]));
      else
        _get_tasks($path, $tasks, $stacks[$f]);
    }
  }
    
  return $tasks;
}



/**
 * @private
 * 
 * Nested args from tasks and sets on stacks, etc...
 * @example
 *  _nested_array(func_get_args(), function(){return 'hello';}, $nested_stack);
 */
function _nested_array($args, $callback, &$nested=array(), $i=0){
  if(count($args)-1>$i){
    $nested[$args[$i]] = _nested_array($args, $callback, $nested[$args[$i]], $i+1);
  }else{
    $nested[$args[$i]] = $callback;
  }
  return $nested;
}



/**
 * Execute arguments from command line
 */
function _handlerCommands($args){
  $cmd = strtolower($args[0]);
  if(!$cmd) 
    return terminal()->WriteLine("task name unrecognized! try `list`",'red');
  try {
    switch ($cmd) {
      case 'help':
      case 'commands':
        terminal()->WriteLine("Available commands:");
        terminal()->WriteLine(" * help or commands  Prints this help");
        terminal()->WriteLine(" * exit              Exits this shell");
        terminal()->WriteLine(" * tasks or list     List all task on stack");
        terminal()->WriteLine(" * task              Run task");
        terminal()->WriteLine(" * clear             Clear terminal");
        //terminal()->writeLine(" * file          Open file tasks");
        break;
      case 'exit':
        terminal()->WriteLine("Goodbye!");
        exit(0);
      case 'clear':
        terminal()->Clear();
        break;
      case 'list':
      case 'tasks':
        print_tasks(Maki::$STACK_TASKS);
        break;
      case 'task':
      case 'maki':
        $result = call_user_func_array('maki', $args);
        break;
      default:
        call_user_func_array('maki', $args);;
    }
  } catch (TaskNotFountException $e) {
    terminal()->WriteLine("task (".implode(', ', $args).") not found!",'red');
  }
}



function _install($command, $path){
  if($command=='install'){
    exec('cp -R '.DIR_ROOT.' '.$path);
    exec('ln -s '.$path.'/bin/maki /usr/bin/maki');
  }elseif($command=='update'){
    exec('cd '.DIR_ROOT.'; git pull;');
  }elseif($command=='uninstall'){
    message('Remove your self! (rm -R /usr/local/maki), (rm /usr/bin/maki)');
  }
  exit();
}


class TaskNotFountException extends Exception{}