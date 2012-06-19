<?php

/**
 * CLICommander is a set of advanced tools for working with PHP scripts on the linux/unix command line.  
 * 
 * It offers features such as colored output, 
 * options handling, and a whole lot more.  CLICommander implements most 
 * ANSI escape sequences and offers support for basic 16-color, and xterm
 * 256-color output.
 * 
 * If you like CLICommander, please consider donating
 *  - BTC: 1K2tvdYzdDDd8w6vNHQgvbNQnhcHqLEadx
 *  - LTC: LfceD3QH2n1FqH8inqHdKxjBFV55QvuESv
 *  
 * @package CLICommander
 * 
 * @author Don Bauer <lordgnu@me.com>
 * @link https://github.com/lordgnu/CLICommander
 * @license MIT License
 * 
 * @copyright
 * Copyright (c) 2011 Don Bauer <lordgnu@me.com>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
class CLICommander {
	/**
	 * The version of CLICommander
	 * @var string
	 */
	public static $version	=	'1.0';
	
	/**
	 * Raw list of arguments passed at the time the script was invoked
	 * @var array
	 */
	private $argv;
	
	/**
	 * The number of arguments passed to the running script
	 * @var integer
	 */
	private $argc;
	
	/**
	 * List of argument that were passed.  Stores short options (-v) and long
	 * options (--verbose)
	 * @var array
	 */
	private $argumentsPassed	=	array();
	
	/**
	 * List of values associated to arguments passed to the running script
	 * @var array
	 */
	private $argumentValues		=	array();
	
	/**
	 * Flag for whether or not the script is running on a Windows based machine
	 * @var boolean
	 */
	private	$usingWindows	=	false;
	
	/**
	 * Flag for whether the terminal has support for xterm colors
	 * @var boolean
	 */
	private $xtermSupport	=	false;
	
	/**
	 * Flag for whether or not bash was detected as installed
	 * @var boolean
	 */
	private $bashSupport	=	false;
	
	/**
	 * Flag for whether or not to auto reset the terminal to default after output is written
	 * @var boolean
	 */
	private $autoReset		=	true;
	
	/**
	 * The newline character to use on the current system
	 * @var string
	 */
	private $nl	=	PHP_EOL;
	
	/**
	 * ANSI escape sequence for output coded for sprinf
	 * @var string
	 */
	private $escape 			=	"\033[%sm";
	
	/**
	 * ANSI escape sequence to ding the terminal bell
	 * @var string
	 */
	private $bell 				=	"\007";
	
	/**
	 * ANSI escape sequence to clear the terminal
	 * @var string
	 */
	private $cls 				=	"\033[2J";
	
	/**
	 * Default formats for output
	 * @var array
	 */
	private $defaults = array(
		'foreground'	=>	'default',
		'background'	=>	'default',
		'style'			=>	'default'
	);
	
	/**
	 * The stream to open a socket for reading input
	 * @var string
	 */
	private $inputStream	=	'php://stdin';
	
	/**
	 * The stream to open a socket for writing output
	 * @var string
	 */
	private $outputStream	=	'php://stdout';
	
	/**
	 * The stream to open a socket for writing errors
	 * @var string
	 */
	private $errorStream	=	'php://stderr';
	
	/**
	 * Threshhold for approximating greys when evaulating xterm colors
	 * 
	 * This is the threashold used by the IsAlmostGrey() method.  Setting this too high
	 * can make normal colors be converted to greyscale, and setting it too low can make
	 * colors that are almost grey show up a black
	 * 
	 * @var integer
	 */
	private $xtermGreyThreshold	=	24;
	
	/**
	 * The socket from which to read input
	 * @var resource
	 */
	protected $inputSocket;
	
	/**
	 * The socket in which to write output
	 * @var resource
	 */
	protected $outputSocket;
	
	/**
	 * The socket in which to write errors
	 * @var resource
	 */
	protected $errorSocket;
	
	/**
	 * List and values of ANSI foreground colors
	 * @var array
	 */
	protected $foregroundColors = array(
		'default'	=>	39,
		'black'		=>	30,
		'red'		=>	31,
		'green'		=>	32,
		'yellow'	=>	33,
		'blue'		=>	34,
		'magenta'	=>	35,
		'cyan'		=>	36,
		'white'		=>	37
	);
	
	/**
	 * List and values of ANSI background colors
	 * @var array
	 */
	protected $backgroundColors = array(
		'default'	=>	49,
		'black'		=>	40,
		'red'		=>	41,
		'green'		=>	42,
		'yellow'	=>	43,
		'blue'		=>	44,
		'magenta'	=>	45,
		'cyan'		=>	46,
		'white'		=>	47
	);
	
	/**
	 * List and values of ANSI styles
	 * @var array
	 */
	protected $styles = array(
		'default'			=>	0,
		'bold'				=>	1,
		'faint'				=>	2,
		'italic'			=>	3,
		'underline'			=>	4,
		'blink'				=>	5,	// Not widely supported
		'blinkfast'			=>	6,	// Not widely supported
		'reverse'			=>	7,
		'conceal'			=>	8,	// Not widely supported
		'doubleunderline'	=>	21,	// Not widely supported
		'subscript'			=>	48,	// Not widely supported
		'superscript'		=>	49	// Not widely supported
	);
	
	/**
	 * List and values of xterm colors
	 * @var array
	 */
	protected $xtermColors = array(
		'000000'=>16,'00005F'=>17,'000087'=>18,'0000AF'=>19,'0000D7'=>20,
		'0000FF'=>21,'005F00'=>22,'005F5F'=>23,'005F87'=>24,'005FAF'=>25,
		'005FD7'=>26,'005FFF'=>27,'008700'=>28,'00875F'=>29,'008787'=>30,
		'0087AF'=>31,'0087D7'=>32,'0087FF'=>33,'00AF00'=>34,'00AF5F'=>35,
		'00AF87'=>36,'00AFAF'=>37,'00AFD7'=>38,'00AFFF'=>39,'00D700'=>40,
		'00D75F'=>41,'00D787'=>42,'00D7AF'=>43,'00D7D7'=>44,'00D7FF'=>45,
		'00FF00'=>46,'00FF5F'=>47,'00FF87'=>48,'00FFAF'=>49,'00FFD7'=>50,
		'00FFFF'=>51,'5F0000'=>52,'5F005F'=>53,'5F0087'=>54,'5F00AF'=>55,
		'5F00D7'=>56,'5F00FF'=>57,'5F5F00'=>58,'5F5F5F'=>59,'5F5F87'=>60,
		'5F5FAF'=>61,'5F5FD7'=>62,'5F5FFF'=>63,'5F8700'=>64,'5F875F'=>65,
		'5F8787'=>66,'5F87AF'=>67,'5F87D7'=>68,'5F87FF'=>69,'5FAF00'=>70,
		'5FAF5F'=>71,'5FAF87'=>72,'5FAFAF'=>73,'5FAFD7'=>74,'5FAFFF'=>75,
		'5FD700'=>76,'5FD75F'=>77,'5FD787'=>78,'5FD7AF'=>79,'5FD7D7'=>80,
		'5FD7FF'=>81,'5FFF00'=>82,'5FFF5F'=>83,'5FFF87'=>84,'5FFFAF'=>85,
		'5FFFD7'=>86,'5FFFFF'=>87,'870000'=>88,'87005F'=>89,'870087'=>90,
		'8700AF'=>91,'8700D7'=>92,'8700FF'=>93,'875F00'=>94,'875F5F'=>95,
		'875F87'=>96,'875FAF'=>97,'875FD7'=>98,'875FFF'=>99,'878700'=>100,
		'87875F'=>101,'878787'=>102,'8787AF'=>103,'8787D7'=>104,'8787FF'=>105,
		'87AF00'=>106,'87AF5F'=>107,'87AF87'=>108,'87AFAF'=>109,'87AFD7'=>110,
		'87AFFF'=>111,'87D700'=>112,'87D75F'=>113,'87D787'=>114,'87D7AF'=>115,
		'87D7D7'=>116,'87D7FF'=>117,'87FF00'=>118,'87FF5F'=>119,'87FF87'=>120,
		'87FFAF'=>121,'87FFD7'=>122,'87FFFF'=>123,'AF0000'=>124,'AF005F'=>125,
		'AF0087'=>126,'AF00AF'=>127,'AF00D7'=>128,'AF00FF'=>129,'AF5F00'=>130,
		'AF5F5F'=>131,'AF5F87'=>132,'AF5FAF'=>133,'AF5FD7'=>134,'AF5FFF'=>135,
		'AF8700'=>136,'AF875F'=>137,'AF8787'=>138,'AF87AF'=>139,'AF87D7'=>140,
		'AF87FF'=>141,'AFAF00'=>142,'AFAF5F'=>143,'AFAF87'=>144,'AFAFAF'=>145,
		'AFAFD7'=>146,'AFAFFF'=>147,'AFD700'=>148,'AFD75F'=>149,'AFD787'=>150,
		'AFD7AF'=>151,'AFD7D7'=>152,'AFD7FF'=>153,'AFFF00'=>154,'AFFF5F'=>155,
		'AFFF87'=>156,'AFFFAF'=>157,'AFFFD7'=>158,'AFFFFF'=>159,'D70000'=>160,
		'D7005F'=>161,'D70087'=>162,'D700AF'=>163,'D700D7'=>164,'D700FF'=>165,
		'D75F00'=>166,'D75F5F'=>167,'D75F87'=>168,'D75FAF'=>169,'D75FD7'=>170,
		'D75FFF'=>171,'D78700'=>172,'D7875F'=>173,'D78787'=>174,'D787AF'=>175,
		'D787D7'=>176,'D787FF'=>177,'D7AF00'=>178,'D7AF5F'=>179,'D7AF87'=>180,
		'D7AFAF'=>181,'D7AFD7'=>182,'D7AFFF'=>183,'D7D700'=>184,'D7D75F'=>185,
		'D7D787'=>186,'D7D7AF'=>187,'D7D7D7'=>188,'D7D7FF'=>189,'D7FF00'=>190,
		'D7FF5F'=>191,'D7FF87'=>192,'D7FFAF'=>193,'D7FFD7'=>194,'D7FFFF'=>195,
		'FF0000'=>196,'FF005F'=>197,'FF0087'=>198,'FF00AF'=>199,'FF00D7'=>200,
		'FF00FF'=>201,'FF5F00'=>202,'FF5F5F'=>203,'FF5F87'=>204,'FF5FAF'=>205,
		'FF5FD7'=>206,'FF5FFF'=>207,'FF8700'=>208,'FF875F'=>209,'FF8787'=>210,
		'FF87AF'=>211,'FF87D7'=>212,'FF87FF'=>213,'FFAF00'=>214,'FFAF5F'=>215,
		'FFAF87'=>216,'FFAFAF'=>217,'FFAFD7'=>218,'FFAFFF'=>219,'FFD700'=>220,
		'FFD75F'=>221,'FFD787'=>222,'FFD7AF'=>223,'FFD7D7'=>224,'FFD7FF'=>225,
		'FFFF00'=>226,'FFFF5F'=>227,'FFFF87'=>228,'FFFFAF'=>229,'FFFFD7'=>230,
		'FFFFFF'=>231,'080808'=>232,'121212'=>233,'1C1C1C'=>234,'262626'=>235,
		'303030'=>236,'3A3A3A'=>237,'444444'=>238,'4E4E4E'=>239,'585858'=>240,
		'626262'=>241,'6C6C6C'=>242,'767676'=>243,'808080'=>244,'8A8A8A'=>245,
		'949494'=>246,'9E9E9E'=>247,'A8A8A8'=>248,'B2B2B2'=>249,'BCBCBC'=>250,
		'C6C6C6'=>251,'D0D0D0'=>252,'DADADA'=>253,'E4E4E4'=>254,'EEEEEE'=>255
	);
	
	/**
	 * CLICommander constructor.  
	 * 
	 * Returns an instanced CLICommander object
	 */
	public function CLICommander() {
		// Check to see if we are using windows
		if (substr(php_uname('s'),0,7) == 'Windows') $this->usingWindows = true;
		
		// Open our sockets
		$this->outputSocket = @fopen($this->outputStream, 'w');
		$this->inputSocket = @fopen($this->inputStream, 'r');
		$this->errorSocket = @fopen($this->errorStream, 'w');
		
		if (!$this->usingWindows) {
			// Check for xterm support
			if (strpos($_SERVER['TERM'],'xterm') !== false) $this->xtermSupport = true;
			
			// Check for bash
			$test = `which bash`;
			if (!empty($test)) $this->bashSupport = true;
		}
		
		// Assign the Argument Variables
		$this->argc = $_SERVER['argc'];
		$this->argv = $_SERVER['argv'];
		
		// Process any passed arguments
		$this->ProcessArguments();
	}
	
	/**
	 * CLICommander destructor.  
	 * 
	 * Reset the terminal and closes open sockets
	 */
	public function __destruct() {
		// Reset the terminal 
		$this->SystemWrite(sprintf($this->escape,0));
		
		// Close our sockets
		@fclose($this->outputSocket);
		@fclose($this->inputSocket);
		@fclose($this->errorSocket);
	}
	
	/**
	 * Check whether an argument was passed to the PHP script
	 * 
	 * @param string $argument The argument to check
	 * @return boolean
	 */
	public function ArgumentPassed($argument) {
		if (isset($this->argumentsPassed[$argument])) return $this->argumentsPassed[$argument];
		return false;
	}
	
	/**
	 * Ding the terminal bell
	 */
	public function Bell() {
		$this->SystemWrite($this->bell);
	}
	
	/**
	 * Clear the terminal and reset the cursor to 1,1
	 */
	public function Clear() {
		$this->SystemWrite($this->cls);
		$this->SetXY(1,1);
	}
	
	/**
	 * Disable automatic resetting the terminal to default after writing output to the terminal
	 * 
	 * This causes changes in formatting to persist until a new format is set or
	 * the reset method is called manually
	 */
	public function DisableAutoReset() {
		$this->autoReset = false;
	}
	
	/**
	 * Enable automatic resetting of the terminal to default after writing output to the terminal
	 */
	public function EnableAutoReset() {
		$this->autoReset = true;
	}
	
	/**
	 * Get an array of the arguments passed and their associated values (if there are any)
	 * 
	 * Any arguments passed to the script without a preceeding "-" or "--" will show up in
	 * this array with numbered indexes.  All arguments passed with a preceeding "-" or "--"
	 * will show up with named indexes
	 * 
	 * <code>
	 * ./myScript.php -vad --file myFile.php
	 * <?php
	 * print_r($cli->GetArguments());
	 * ?>
	 * OUTPUT:
	 * array(
	 * 	'v'	=>	1,
	 * 	'a'	=>	1,
	 * 	'd'	=>	1,
	 * 	'file'	=>	'myFile.php',
	 * 	0	=>	'myFile.php'
	 * )
	 * </code>
	 * 
	 * @return array
	 */
	public function GetArguments() {
		$args = array();
		foreach ($this->argumentsPassed as $i => $v) {
			$args[$i] = (isset($this->argumentValues[$i])) ? $this->argumentValues[$i] : $v;
		}
		
		return $args;
	}
	
	/**
	 * Get the value of an argument passed to the script
	 * 
	 * Returns the value of an argument passed to the php script or true of the 
	 * argument was passed but there was no associated value.  Returns false if
	 * the argument was not passed.
	 * 
	 * @param string $argument The argument to get the value of
	 * @return mixed
	 */
	public function GetArgumentValue($argument) {
		if ($this->ArgumentPassed($argument)) {
			if (isset($this->argumentValues[$argument])) {
				return $this->argumentValues[$argument];
			} else {
				return true;
			}
		} 
		
		return false;
	}
	
	/**
	 * Get a single character from input.  
	 * 
	 * This method is not very reliable on
	 * most PHP installations because it is a blocking call.  If bash is found
	 * on the system, it will use bash to get a single character from the input,
	 * otherwise this function will wait until the enter key has been pressed 
	 * and will return the first character the was entered prior to pressing enter
	 * 
	 * @return string
	 */
	public function GetChar() {
		if ($this->usingWindows || !$this->bashSupport) return fgetc($this->inputSocket);
		return trim( `bash -c "read -n 1 -t 10 ANS ; echo \\\$ANS"` );
	}
	
	/**
	 * Returns a line of text entered without the newline chacter
	 * 
	 * @return string
	 */
	public function GetLine() {
		return preg_replace("(\r\n|\n|\r)", '',fgets($this->inputSocket));
	}
	
	/**
	 * Determine whether the current terminal has support for xterm colors
	 * 
	 * @return boolean
	 */
	public function HasXtermSupport() {
		return $this->xtermSupport;
	}
	
	/**
	 * Request input from the user, but hide the characters as they are entered
	 * 
	 * This is very useful for passwords
	 *
	 * @return string
	 */
	public function MaskedGetLine() {
		$get = preg_replace("(\r\n|\n|\r)", '', `stty -echo; head -n1 ; stty echo`);
		$this->WriteLine();
		return $get;
	}
	
	/**
	 * Writes some text (i.e. a question) and waits for user input.  
	 * 
	 * Returns the string entered by the user.  This is a masked prompt, so text is not 
	 * echoed as the user types characters.  Very useful for passwords.
	 * 
	 * @param string $text The text to display
	 * @param string|integer $fgColor The color to use for the text displayed or a style array to use for all the formatting
	 * @param string|integer $bgColor The color to use for the background
	 * @param string $style The style to use for the text displayed
	 * 
	 * @return string
	 */
	public function MaskedPrompt($text, $fgColor = null, $bgColor = null, $style = null) {
		$this->Write($text . ' ', $fgColor, $bgColor, $style);
		return $this->MaskedGetLine();
	}
	
	/**
	 * Writes some text (i.e. a question) and waits for user input.  
	 * 
	 * Returns the string entered by the user.  Input is not masked.
	 *   
	 * @param string $text The text to display
	 * @param string|integer|array $fgColor The color to use for the text displayed or a style array to use for all the formatting
	 * @param string|integer $bgColor The color to use for the background
	 * @param string $style The style to use for the text displayed
	 * 
	 * @return string
	 */
	public function Prompt($text, $fgColor = null, $bgColor = null, $style = null) {
		$this->Write($text . ' ', $fgColor, $bgColor, $style);
		return $this->GetLine();
	}
	
	/**
	 * Resets the terminal to the current default format
	 * 
	 * If requested, this method will return the format string that would have been 
	 * written to the output socket, rather than writing it.
	 * 
	 * @param boolean $return When true, just returns the escape sequence of the reset
	 * 
	 * @return string|void
	 */
	public function Reset($return = false) {
		if ($this->usingWindows) return;
		if (!$return) {
			$this->SystemWrite(sprintf($this->escape,0));
			$this->SystemWrite($this->GetFormatString($this->defaults['foreground'], $this->defaults['background'], $this->defaults['style']));
		} else {
			return sprintf($this->escape,0).$this->GetFormatString($this->defaults['foreground'], $this->defaults['background'], $this->defaults['style']);
		}
	}
	
	/**
	 * Changes the default foreground color for the session
	 * 
	 * @param string|integer $fgColor The foreground color to set as default
	 */
	public function SetDefaultForegroundColor($fgColor) {
		$this->defaults['foreground'] = $fgColor;
	}
	
	/**
	 * Changes the default background color for the session
	 * 
	 * @param string|integer $bgColor The background color to set as default
	 */
	public function SetDefaultBackgroundColor($bgColor) {
		$this->defaults['background'] = $bgColor;
	}
	
	/**
	 * Changes the default style for the session
	 * 
	 * @param string $style The style to set as default
	 */
	public function SetDefaultStyle($style) {
		$this->defaults['style'] = $style;
	}
	
	/**
	 * Changes the terminal title for the session
	 * 
	 * @param string $title The title to set for this terminal
	 */
	public function SetTerminalTitle($title = "CLICommander Terminal") {
		if (!$this->usingWindows) $this->SystemWrite("\033]2;".$title."\007");
	}
	
	/**
	 * Change the cursor position to a new x,y coordinate
	 * 
	 * @param integer $x The x coordinate to set the cursor to
	 * @param integer $y The y coordinate to set the cursor to
	 */
	public function SetXY($x = 1, $y = 1) {
		if ($x < 1) $x = 1;
		if ($y < 1) $y = 1;
		$this->SystemWrite("\033[{$x};{$y}H");
	}
	
	/**
	 * Write a string to the terminal with defined formatting
	 * 
	 * Please note that Write() does not append newline characters to the text entered.
	 * If you need a newline character, use WriteLine()
	 * 
	 * @param string $text The text to display
	 * @param string|integer|array $fgColor The color to use for the text displayed or a style array to use for all the formatting
	 * @param string|integer $bgColor The color to use for the background
	 * @param string $style The style to use for the text displayed
	 */
	public function Write($text, $fgColor = null, $bgColor = null, $style = null) {
		if (!$this->usingWindows) {
			// Check our colors and styles
			if (is_array($fgColor)) {
				// User passed a style array
				$style = (isset($fgColor['style']) && !empty($fgColor['style'])) ? $fgColor['style'] : $this->defaults['style'];
				$bgColor = (isset($fgColor['background']) && !empty($fgColor['background'])) ? $fgColor['background'] : $this->defaults['background'];
				$fgColor = (isset($fgColor['foreground']) && !empty($fgColor['foreground'])) ? $fgColor['foreground'] : $this->defaults['foreground'];
			} else {
				// Check for individual options
				if ($fgColor == null) $fgColor = $this->defaults['foreground'];
				if ($bgColor == null) $bgColor = $this->defaults['background'];
				if ($style == null) $style = $this->defaults['style'];
			}
			
			$format = $this->GetFormatString($fgColor, $bgColor, $style);
			
			// Save text with formatting escape sequence
			$text = $format.$text;
		}
		
		// Write our text to the output socket
		$this->SystemWrite($text);
		
		if ($this->autoReset) $this->Reset();
	}
	
	/**
	 * Write output to the error socket
	 * 
	 * @param string $text The text to write to the error socket
	 */
	public function WriteError($text = 'A fatal error has occured!') {
		$this->SystemWrite($text, true);
	}
	
	/**
	 * Write a string to the terminal with defined formatting and auto append a newline character
	 * 
	 * @param string $text The text to display
	 * @param string|integer|array $fgColor The color to use for the text displayed or a style array to use for all the formatting
	 * @param string|integer $bgColor The color to use for the background
	 * @param string $style The style to use for the text displayed
	 */
	public function WriteLine($text = '', $fgColor = null, $bgColor = null, $style = null) {
		$this->Write($text, $fgColor, $bgColor, $style);
		$this->SystemWrite($this->nl);
	}
	
	/**
	 * Write output from a block of templated text.  
	 * 
	 * See the example for more information on using templates
	 * 
	 * @param string $text The text with template markup to display
	 * @example example.templateOutput.php
	 */
	public function WriteTemplate($text) {
		$output = $this->ParseTemplate($text);
		$this->SystemWrite($output);
	}
	
	/*
	 * All Private Methods Below Here
	 */
	/**
	 * Convert an RGB hex color to the closest xterm equivilent
	 * 
	 * @param string $rgbString The hex string to convert
	 * @param boolean $foreground Whether this is for a foreground or background color
	 * 
	 * @return integer
	 */
	private function ClosestXtermColor($rgbString, $foreground = true) {
		// Replace # sign if there
		$rgbString = str_replace('#','',strtoupper($rgbString));
		
		// Check the length
		if (strlen($rgbString) != 6) {
			if ($foreground) return 231; // White
			return 16; // Black
		}
		
		// Breakout the RGB colors
		$r = hexdec(substr($rgbString,0,2));
		$g = hexdec(substr($rgbString,2,2));
		$b = hexdec(substr($rgbString,4,2));
		
		// Check for Greyscale color
		if ($r == $g && $g == $b || $this->IsAlmostGrey($r, $g, $b)) {
			$g = $this->ClosestXtermGrey(max($r,$g,$b));
			$color = $g.$g.$g;
		} else {
			// Color
			$color = $this->ClosestXtermOctet($r).$this->ClosestXtermOctet($g).$this->ClosestXtermOctet($b);
		}
		
		return $this->xtermColors[$color];
	}
	
	/**
	 * Convert a hex octet to the closest grey octet used for xterm colors
	 * 
	 * @param integer $g The integer value of the hex to be converted
	 * 
	 * @return string
	 */
	private function ClosestXtermGrey($g = 0) {
		if ($g < 4) return '00';
		if ($g > 243) return 'FF';

		$m = $g % 10;

		if ($m != 8) {
			if ($m > 3 && $m < 8) {
				$g = $g + (8 - $m);
			} else {
				switch ($m) {
					case 3:
						$g--;
					case 2:
						$g--;
					case 1:
						$g--;
					case 0:
						$g--;
					case 9:
						$g--;
				}
			}
		}
		
		unset($m);
		$h = strtoupper(dechex($g));
		unset($g);
		
		if (strlen($h) == 1) {
			return '0'.$h;
		} else {
			return $h;
		}
	}
	
	/**
	 * Get the closest xterm color octet for the octet supplied
	 * 
	 * @param integer $c
	 * 
	 * @return string
	 */
	private function ClosestXtermOctet($c = 0) {
		if ($c >= 0 && $c < 47) {
			return '00';
		} elseif ($c > 46 && $c < 116) {
			return '5F';
		} elseif ($c > 115 && $c < 156) {
			return '87';
		} elseif ($c > 155 && $c < 196) {
			return 'AF';
		} elseif ($c > 195 && $c < 236) {
			return 'D7';
		} else {
			return 'FF';
		}
	}
	
	/**
	 * Generate the ANSI/XTERM format string for the format information passed
	 * 
	 * @param string|integer $fgColor The foreground color to use for the format string
	 * @param string|integer $bgColor The background color to use for the format string
	 * @param string $style The style to use for the format string
	 * 
	 * @return string
	 */
	private function GetFormatString($fgColor, $bgColor, $style) {
		// Initialize the format arrays
		$formats = array(
			'foreground'	=>	'',
			'background'	=>	'',
			'style'	=>	''
		);
		$xFormats = array(
			'foreground'	=>	'',
			'background'	=>	''
		);
		
		// Check the foreground color
		if ($fgColor !== null) {
			if (array_key_exists($fgColor, $this->foregroundColors)) {
				$formats['foreground'] = $this->foregroundColors[$fgColor];
			} else {
				if ($this->xtermSupport === true) {
					// Try this as an xterm color
					$fgColor = strtoupper($fgColor);
					
					if (array_key_exists($fgColor, $this->xtermColors)) {
						// Good xterm color
						$xFormats['foreground'] = sprintf($this->escape, '38;5;'.$this->xtermColors[$fgColor]);
					} else {
						if (strlen($fgColor) == 6 || strlen($fgColor) == 7) {
							// Convert RGB string to the closest xterm capable color
							$xFormats['foreground'] = sprintf($this->escape, '38;5;'.$this->ClosestXtermColor($fgColor, true));
						} elseif ((int)$fgColor >= 0 && (int)$fgColor <= 255) {
							// Already passed as xterm color index
							$xFormats['foreground'] = sprintf($this->escape, '38;5;'.$fgColor);
						} else {
							// No valid xterm color found
							unset($formats['foreground']);
						}
					}
				} else {
					// No valid ANSI or xterm color found
					unset($formats['foreground']);
				}
			}
		} else {
			unset($formats['foreground']);
		}
		
		// Check the background color
		if ($bgColor !== null) {
			if (array_key_exists($bgColor, $this->backgroundColors)) {
				$formats['background'] = $this->backgroundColors[$bgColor];
			} else {
				if ($this->xtermSupport === true) {
					// Try this as an xterm color
					$bgColor = strtoupper($bgColor);
					
					if (array_key_exists($bgColor, $this->xtermColors)) {
						// Good xterm color
						$xFormats['background'] = sprintf($this->escape, '48;5;'.$this->xtermColors[$bgColor]);
					} else {
						if (strlen($bgColor) == 6 || strlen($bgColor) == 7) {
							// Convert RGB string to the closest xterm capable color
							$xFormats['background'] = sprintf($this->escape, '48;5;'.$this->ClosestXtermColor($bgColor, false));
						} elseif ((int)$bgColor >= 0 && (int)$bgColor <= 255) {
							// Already passed as xterm color index
							$xFormats['background'] = sprintf($this->escape, '48;5;'.$bgColor);
						} else {
							// No valid xterm color found
							unset($formats['background']);
						}
					}
				} else {
					// No valid ANSI or xterm color found
					unset($formats['background']);
				}
			}
		} else {
			unset($formats['background']);
		}
		
		// Check the style
		if (array_key_exists($style, $this->styles) && $style != 'default') $formats['style'] = $this->styles[$style]; else unset($formats['style']);
		
		// Build the format string
		$formatString = ((count($formats)) ? sprintf($this->escape, implode(';',$formats)) : '') . $xFormats['foreground'] . $xFormats['background'];
		
		return $formatString;
	}
	
	/**
	 * Determines if the color passed is almost a grey
	 * 
	 * This function determines if the 3 octets that make up an RGB color are closes
	 * enough to each other to be rendered as a grey.  This prevents tinted greys from
	 * being rendered as black when using xterm colors
	 * 
	 * @param integer $r
	 * @param integer $g
	 * @param integer $b
	 */
	private function IsAlmostGrey($r, $g, $b) {
		// Get the smallest value of the colors passed
		$min = min($r, $g, $b);
		
		// Subtract the min value from all colors
		$r -= $min;
		$g -= $min;
		$b -= $min;
		
		// Now check the max against out threshhold
		if (max($r, $g, $b) < $this->xtermGreyThreshold) {
			return true;
		}
		return false;
	}
	
	/**
	 * Parse format code from template text and return format strings 
	 * 
	 * @param array $matches List of matched text from ParseTemplate
	 * 
	 * @return string
	 */
	private function ParseFormat($matches) {
		// Explode the format string
		$format = explode('|',strtolower($matches['format']));
		$fg = (isset($format[0]) && !empty($format[0])) ? $format[0] : null;
		
		// Check for reset
		if ($fg == 'reset') return $this->Reset(true);
		
		$bg = (isset($format[1]) && !empty($format[1])) ? $format[1] : null;
		$style = (isset($format[2]) && !empty($format[2])) ? $format[2] : null;
		
		return $this->GetFormatString($fg, $bg, $style);
	}
	
	/**
	 * Parse and return template text
	 * 
	 * @param string $data The text to parse
	 * 
	 * @return string
	 */
	private function ParseTemplate($data) {
		return str_replace(array('{{','}}'),array('{','}'),preg_replace_callback('/\{(?P<format>[^\}\{]+)\}/', array($this, 'ParseFormat'), $data)) . $this->nl;
	}
	
	/**
	 * Process and track arguments that were passed to the script.
	 */
	private function ProcessArguments() {
		if ($this->argc > 1) {
			unset($this->argv[0]);
			foreach ($this->argv as $index => $arg) {
				if (substr($arg, 0, 2) == '--') {
					$this->argumentsPassed[substr($arg,2)] = true;
					
					// Check if we have a value for this arg
					if (isset($this->argv[($index+1)])) {
						if (substr($this->argv[($index+1)], 0, 1) != '-') {
							$this->argumentValues[substr($arg,2)] = $this->argv[($index+1)]; 
						}
					}
					
				} elseif (substr($arg, 0, 1) == '-') {
					// This is a short flag and could be more than 1
					$arg = substr($arg,1);
					if (strlen($arg) == 1) {
						$this->argumentsPassed[$arg] = true;
					} else {
						for ($a = 0; $a < strlen($arg); $a++) {
							$this->argumentsPassed[substr($arg, $a, 1)] = true;
						}
					}
				} else {
					$this->argumentsPassed[] = $arg;
				}
			}
		}
	}
	
	/**
	 * Write to an output socket with no formatting
	 * 
	 * @param string $text The text to write to the socket
	 * @param boolean $useErrorSocket Whether to use the error socket or the output socket
	 */
	private function SystemWrite($text = '', $useErrorSocket = false) {
		if ($useErrorSocket) {
			@fwrite($this->errorSocket, $text);
		} else {
			@fwrite($this->outputSocket, $text);
		}
	}
}

?>