<?
/**
 * try:
 *  maki list
 *  maki git diff #view diff
 *  maki git #run all order tasks git (diff, add, commit and push)
 *  maki deploy
 */ 
set('SSH_USERNAME', 'usernamessh');
set('SSH_PASSWORD', 'passwordssh');
set('SSH_HOST', 'hostssh');

set('LOCAL_BASE_DIR', __DIR__);
set('REMOTE_BASE_DIR', '/home/remoteuser/application');


//maki git (run add, commit and push)
task('git', 'diff', function(){
  local('cd '. get('LOCAL_BASE_DIR'));
  $message = local('git diff');
  message($message);
  //if you like, send e-mail with diff
});
task('git', 'add', function(){
  local('cd '. get('LOCAL_BASE_DIR'));
  local('git add .', true);
});
task('git', 'commit', function(){
  local('cd '. get('LOCAL_BASE_DIR'));
  $messageCommit = prompt('Message Commit:', 'red', 'white');
  local("git commit -m '".$messageCommit."'");
});
task('git', 'push', function(){
  local('cd '. get('LOCAL_BASE_DIR'));
  local('git push');
});


//deploy
task( 'deploy', function(){
  //git
  if(strtolower(prompt('Do you like run git task first?(y|N)'))=='y'){
    maki('git');
  }
  //test
  $firstResultTest=true;
  if(strtolower(prompt('Do you like run test task first?(y|N)'))=='y'){
    $firstResultTest = maki('test');
  }
  //deploy
  if($firstResultTest){
    if(strtolower(prompt('You are sure deploy app?(y|N)'))=='y'){
      remote('cd '. get('REMOTE_BASE_DIR'));
      remote('git pull');
    }
  }else{
    message('You cannot deploy appp. Test fail!', 'red');
  }
});


task('test', function(){
  require_once 'tests/maki.php';
  require_once 'PHPUnit.php';
  $suite  = new PHPUnit_TestSuite("Maki");
  $result = PHPUnit::run($suite);
  //include and run any test
  message($result->toString(), 'white');//show result test
  return ($result->failureCount()>0);
});


task('restart', 'production', function(){
  remote('sudo service restart apache');
});
task('restart', 'stating', function(){
  open_environment('staging');
  remote('sudo service restart apache');
  close_environment('staging');
});
task('restart', 'local', function(){
  local('sudo service restart apache');
});


task('copy', 'db', function(){
   message(get('LOCAL_BASE_DIR').'/examples.txt  ->  '.get('REMOTE_BASE_DIR').'/exxaample.txt', 'black', 'white');
  local_remote(get('LOCAL_BASE_DIR').'/examples.txt', '/tmp/exxaample.txt');
});
