<?php 
/*
  **************************************************************
  *                      LocalShell                           *
  **************************************************************
  
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.
  
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  
  You can get a copy of the GNU General Public License from this
  address: http://www.gnu.org/copyleft/gpl.html#SEC1
  You can also write to the Free Software Foundation, Inc., 59 Temple
  Place - Suite 330, Boston, MA  02111-1307, USA.
  
  This project is inspired and based on PHP Shell 2.0! Please visit:
  http://www.gimpster.com/wiki/PhpShell
  

  Steven Koch <stvkoch@lellol.com>
*/
class Shell
{
  static $aliases = array('la'   => 'ls -la',
    'll'  => 'ls -lvhF',
    'dir' => 'ls' );

  function __construct($local_base='')
  {
    if($local_base!=''){
      chdir($local_base);
    } 

    $this->session['cwd'] = getcwd();
    $this->session['history'] = array();
    $this->session['output'] = '';
    $this->session['command'] ='';
  }

  function exec($command, $send_output=false){
    $this->session['command'] = $command;
    $this->buildCommandHistory($command);
    return $this->handleCommand($command, $send_output);
  }


  function phpCheckVersion($min_version)
  {
    $is_version=phpversion();

    list($v1,$v2,$v3,$v4) = sscanf($is_version,"%d.%d.%d%s");
    list($m1,$m2,$m3,$m4) = sscanf($min_version,"%d.%d.%d%s");

      if($v1>$m1)
        return(1);
      elseif($v1<$m1)
        return(0);
      if($v2>$m2)
        return(1);
      elseif($v2<$m2)
        return(0);
      if($v3>$m3)
        return(1);
      elseif($v3<$m3)
        return(0);

      if((!$v4)&&(!$m4))
        return(1);
      if(($v4)&&(!$m4))
      {
        $is_version=strpos($v4,"pl");
        if(is_integer($is_version))
        return(1);
        return(0);
      }
      elseif((!$v4)&&($m4))
      {
        $is_version=strpos($m4,"rc");
        if(is_integer($is_version))
          return(1);
        return(0);
      }
    return(0);
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



  function handleCommand($command, $send_output=false)
  {
    $aliases = self::$aliases;
    $output = '';
    $new_dir = $this->session['cwd'];
    if (preg_match('@^[[:blank:]]*cd[[:blank:]]*$@', @$command))
    {
      $new_dir = getcwd(); //dirname(__FILE__);
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
    else
    {
      //if($this->session['cwd']!=$new_dir)
        //chdir($this->session['cwd']);
      $output = shell_exec($command);
    }
    if($send_output)
      echo $output;
    return $output;
  }
}
