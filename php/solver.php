<?php

// Set the return content type to JSON
header('content-type: application/json; charset=utf-8');

// Require database helper
require_once('lib/databaseHelper.php');

// Initilise the response object
$response = array(
	'combinations' => null,
	'comboCount' => 0,
);

$Grid = new Grid();

// Retrieve POST request data
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	// Retrieve the word count and word lengths
	$WordCount = $_POST['WordCount'];
	
	for($i=1; $i<=$WordCount; $i++){
		$WordLengths[$i] = $_POST['W'.$i];
	}
	
	// Retieve the grid length and letters
	$Grid->length = $_POST['GridSize'];
		
	for($row = 1; $row <= $Grid->length; $row++)
	{
		for($col = 1; $col <= $Grid->length; $col++)
		{
			$letters[$row][$col] = $_POST['L'.$row.$col];
			$ok[$row][$col] = true;
		}
	}
	$Grid->letters = $letters;
	$Grid->ok = $ok;
}
else
{
	// Error - No POST data supplied
	header('status: 405 No POST request data was supplied.', true, 405); // http Method Not Allowed
	exit(json_encode($response));
}

// For each word needed
set_time_limit (180);
try
{
	$WordCombinations = Combos::find($Grid, $WordLengths, 1);
} 
catch(Exception $e)
{
	header('status: 500'.$e->getMessage(), true, 500); // http Internal server error
	exit(json_encode($response));
}


for($j=0; $j<count($WordCombinations); $j++)
{
	ksort($WordCombinations[$j]);
}


// Update response
$response['combinations'] = $WordCombinations;
$response['comboCount'] = count($WordCombinations);

// Success - Return the response
header('status: 200 No POST request data was supplied.', true, 200); // http Method Not Allowed
echo json_encode($response);









//
// Combinations finding static class
//
class Combos{
	
	// Returns the word combinations found in the grid board
	public static function find($grid, $wordLengths, $i)
	{
		// Word combinations array
		$combos = null;
		// word count
		$findWordCount = count($wordLengths);
		
		// SQL to select words with the length and letters given
		$SQL = new SQL("SELECT Word FROM Words WHERE Length = '".$wordLengths[$i]."' AND Word REGEXP '^[".$grid->lettersString()."]*$'");
		
		// Query the database and get the words
		if(DB::query($SQL, $response))
		{
			// For each match, see if it'll fit on the board
			foreach($response as $result)
			{
				$word = $result['Word'];
				//echo str_repeat("&nbsp-", $i-1);
				//echo "$word.<br>";
				
				// Iterate through each grid square
				for($row = 1; $row <= $grid->length; $row++)
				{
					for($col = 1; $col <= $grid->length; $col++)
					{
						// Get the word path if the word can be made starting at this square
						$l_positions = $grid->tryFitWord($word, 0, $row, $col);
						
						// Check if the word path exists
						if(isset($l_positions))
						{
							// Word was made - Remove it from a temporary grid
							$grid_T = clone $grid;
							$grid_T->removeWord($l_positions);
							
							// Check to see if that was the last word needed
							if($i < $findWordCount)
							{
								// Not the last word - Move to the next words needed to see words can be found on the board
								//echo "Try find the next word..<br>";
								
								$combos_T = Combos::find($grid_T, $wordLengths, $i+1);
								
								if(isset($combos_T))
								{								
									// Combos were found
									foreach($combos_T as $combo){
										// foreach combo add the current word to the start of it
										$combo[$i] = $word;
										
										// Add the combo to the combos found
										$combos[] = $combo;
									}
									
									// Break to next word 
									break 2;
								}
							}
							else
							{
								// That was the last word to be found! A combo has been made!
								$combo[$i] = $word;
								
								//echo "<h2>Combo found!</h2>";
								
								// Add the combo to the combos found
								$combos[] = $combo;
								
								// Break to next word
								break 2;
							}
						}
						else
						{
							// Word can't be made - continue to the next square
							continue;
						}
					}
				}
				// Checked every square
			}
			// Checked every word
		}
		// Return all the combos found
		return $combos;
	} // END Find function
	
}



//
// Grid board class
//
class Grid{
	public $length;
	public $letters;
	public $ok;
	
	// Remove a word from the grid
	public function removeWord($l_positions){
		
		// Sort by rows
		ksort($l_positions);
		
		// Remove each letter
		foreach($l_positions as $pos){
			$this->removeLetter($pos[0], $pos[1]);					
		}
		
		// Drop down remaining letters
		foreach($l_positions as $pos){
			$this->dropLetters($pos[0], $pos[1]);					
		}
	}
	
	// Remove a letter from the grid
	private function removeLetter($row, $col){
		// Move squares down 
		$this->letters[$row][$col] = null;
		$this->ok[$row][$col] = false;
	}
	
	// Remove a letter from the grid
	private function dropLetters($row, $col){
		// Move squares down 
		$c = $col;
		for($r=$row; $r>0; $r--)
		{
			// Move letters above down
			$this->letters[$r][$c] = $this->letters[$r-1][$c];
			$this->ok[$r][$c] = $this->ok[$r-1][$c];
		}
		
		// Ensure unsused spaces are not used
		$this->ok[1][$c] = false;
	}
	
	
	// Try fit a word in the grid
	public function tryFitWord($word, $i, $row, $col)
	{
		// Check to see if the letter matches the letter needed in the word
		if($this->ok[$row][$col] && $this->letters[$row][$col] == $word[$i])
		{
			// The ith letter does match!
			
			// Mark the letter as used
			$this->ok[$row][$col] = false;
			
			// Check to see if it's the end of the word
			if($i+1 < strlen($word))
			{
				// Not end of word - Check the rest of the word fits
				// Check surrounding squares for the next letter
				for($r = $row-1; $r <= $row+1; $r++)
				{
					for($c = $col-1; $c <= $col+1; $c++)
					{
						// If square doesn't contain a letter or it's the current square, continue to the next square
						if(!($this->ok[$r][$c]) || ($r==$row && $c==$col)) continue;
						
						// Try to fit the rest of the word from this square
						$l_positions = $this->tryFitWord($word, $i+1, $r, $c);
						
						// If the word fits
						if(isset($l_positions) > 0)
						{
							// Add this square position
							$l_positions[$row.$col] = array($row,$col);
							$this->ok[$row][$col] = true;
							return $l_positions;
						}
					}
				}
				
				// All surrounding squares were checked with no match found
				$this->ok[$row][$col] = true;
				return null;
			}
			else
			{
				//echo $word." match found!<br>";
				// End of word - return the grid position for the letter
				$l_positions[$row.$col] = array($row,$col);
				$this->ok[$row][$col] = true;
				return $l_positions;
			}
		}
		else
		{
			// Leter doesn't match
			return null;
		}
	} // END tryFitWord
	
	
	public function lettersString(){
		$lettersStr = "";
		for($row = 1; $row <= $this->length; $row++)
		{
			for($col = 1; $col <= $this->length; $col++)
			{
				$lettersStr .= $this->letters[$row][$col];
			}	
		}
		$lettersStr = preg_replace("/(.)\\1+/", "$1", $lettersStr);
		return $lettersStr;
	} // END lettersString
	
	/*
	public function echoLetters(){
		for($row = 1; $row <= $this->length; $row++)
		{
			echo $row."| ";
			for($col = 1; $col <= $this->length; $col++)
			{
				echo "<div style='display:inline-block; width:20px;'>".$this->letters[$row][$col]."</div>";
			}
			echo "<br>";
		}
		
	}*/
}

/*
function lpos($l_positions){
	$str="";
	foreach($l_positions as $pos){
			$str .= "(".$pos[0].", ".$pos[1].")";					
		}
	return $str;
}*/

?>