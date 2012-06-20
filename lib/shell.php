<?php 
/*
  Shell emule persistent shell environment

  @example
    $shell1 = new Shell('/Home/child');
      $shell1->exec('cd www');
      $shell1->exec('ls'); //list /Home/child/www

    $shell2 = new Shell('/Home/parent/docs');
      $shell2->exec('ls'); //list /Home/parent/docs 
      $shell2->exec('cd extra');

    $shell1->exec('cat index.html');//show content from /Home/child/www/index.html
    $shell2->exec('cat manual.doc');//show content from /Home/parent/docs/extra/manual.doc

  @Limitation this is not use proc_open and you cannot access a stream STDIN
    You cannot 
      $shell1->exec('sudo -s');
      $shell1->exec('mySudoPassword');//wrong!
      but you can:
      $shell1->exec('sudo -s');
      $shell1->exec('cat /etc/apache/extra/superconfig.ini'); //run how sudoer

  This project is inspired and based on PHP Shell 2.0! Please visit:
  http://www.gimpster.com/wiki/PhpShell
  
  Adapted by
  Steven Koch <stvkoch@gmail.com>
*/
class Shell
{
  static $aliases = array(
    'la'   => 'ls -la',
    'll'  => 'ls -lvhF',
    'dir' => 'ls'
  );

  function __construct($local_base='')
  {
    if($local_base!=''){
      chdir($local_base);
    }
    $this->session['cwd'] = getcwd();
    $this->session['history'] = array();
    $this->session['output'] = '';
    $this->session['command'] ='';
    $this->session['sudo'] ='';
  }

  function exec($command, $send_output=false){
    $this->session['command'] = $command;
    $this->buildCommandHistory($command);
    return $this->handleCommand($command, $send_output);
  }


  function buildCommandHistory($command)
  {
    if(!empty($command))
    {
      if(get_magic_quotes_gpc())
      {
        $command = stripslashes($command);
      }
      // drop old commands from list if exists
      if (($i = array_search($command, $this->session['history'])) !== false)
      {
        unset($this->session['history'][$i]);
      }
      array_unshift($this->session['history'], $command);
    }
  }



  function handleCommand($command, $send_output=true)
  {
    $aliases = self::$aliases;
    $output = '';
    $new_dir = $this->session['cwd'];
    if (preg_match('@^[[:blank:]]*cd[[:blank:]]*$@', @$command))
    {
      $this->session['cwd'] = getcwd(); //dirname(__FILE__);
      chdir($new_dir);
    }
    elseif(preg_match('@^[[:blank:]]*cd[[:blank:]]+([^;]+)$@', @$command, $regs))
    {
      ($regs[1][0] == '/') ? $new_dir = $regs[1] : $new_dir = $this->session['cwd'] . '/' . $regs[1];
      // cosmetics 
      while (strpos($new_dir, '/./') !== false)
        $new_dir = str_replace('/./', '/', $new_dir);
      while (strpos($new_dir, '//') !== false)
        $new_dir = str_replace('//', '/', $new_dir);
      while (preg_match('|/\.\.(?!\.)|', $new_dir))
        $new_dir = preg_replace('|/?[^/]+/\.\.(?!\.)|', '', $new_dir);
      if(empty($new_dir)): $new_dir = "/"; endif;
      (@chdir($new_dir)) ? $this->session['cwd'] = $new_dir : $output .= "could not change to: $new_dir\n";
    }
    elseif($command=='sudo -s'){
      $this->session['sudo'] = 'sudo -s;';
    }
    elseif($command=='exit'){
      $this->session['sudo'] = '';
    }
    else
    {
      //if($this->session['cwd']!=$new_dir)
      chdir($this->session['cwd']);
      $output = exec($this->session['sudo'] . $command);
    }
    if($send_output)
      echo $output;
    return $output;
  }
}
