// JavaScript Document
$(function() {
	"use strict";
	// Disable/enable solve button
	$("button.solve").prop("disabled", !canSolve());
	
	// On letter entry go to the next empty square
	$("#GridBoard").on("keyup", ".letter-tile", function(){
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
		
		var input = $(this);
		$('#GridBoard td').each(function() {
			input = $(this).find('input.letter-tile');
			if(input.val() === ""){
				input.focus();
				return false;
			}
		});
	});
	
	
	// Increase grid size
	$("#grid-plus").click(function(){
		var newGridSize = +$("#GridSize").val() + 1;
		
		console.log("New grid size: "+ newGridSize);
		
		// Add new column
		$("#GridBoard tr").each(function(i){
			$(this).append('<td><input class="letter-tile" name="L'+(i+1)+newGridSize+'" id="L'+(i+1)+newGridSize+'" maxlength="1" value="" title="Letter" pattern="[A-Za-z]{1}" /></td>');
		});
		
		// Add new Row
		$("#GridBoard").append("<tr></tr>");
		var lastRow = $("#GridBoard > tbody > tr:last-child");
		
		for(var j=1; j<=newGridSize; j++){
			lastRow.append('<td><input class="letter-tile" name="L'+newGridSize+j+'" id="L'+newGridSize+j+'" maxlength="1" value="" title="Letter" pattern="[A-Za-z]{1}" /></td>');
		}
		
		// Update grid size value
		$("#GridSize").val(newGridSize);
		$("#GridBoard").data("size", newGridSize);
		
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
	});
	
	
	// Decrease Grid size
	$("#grid-minus").click(function(){
		var newGridSize = +$("#GridSize").val() - 1;
		
		console.log("New grid size: "+ newGridSize);
		
		// Remove the last column
		$("#GridBoard tr").each(function(){
			$(this).children().last().remove();
		});
		
		// Remove the last Row
		$("#GridBoard > tbody > tr:last-child").remove();
		
		// Update grid size value
		$("#GridSize").val(newGridSize);
		$("#GridBoard").data("size", newGridSize);
		
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
	});
	
	
	// Add a letter to a word
	$("#words").on("click", ".letter-plus", function(){
		// Get the word length
		var wordLength = +$(this).siblings("input.WordLength").val() + 1;
		console.log("word length: "+wordLength);
		// Add a letter space to the word
		$(this).closest(".word").prepend('<div class="letter blank"></div>');
		// Update the word length value
		$(this).siblings("input.WordLength").val(wordLength);
		
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
	});
	
	
	// Remove a letter from a word
	$("#words").on("click", ".letter-minus", function(){
		// Get the word length
		var wordLength = +$(this).siblings("input.WordLength").val() - 1;
		console.log("word length: "+wordLength);
		// If word no longer exist
		if(wordLength <= 1){
			// Remove the word
			$(this).closest(".word").remove();
		} else {
			// Minus a letter space from the word
			$(this).siblings(".letter").first().remove();
			// Update the word length value
			$(this).siblings("input.WordLength").val(wordLength);
		}
		
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
	});
	
	
	// Add a word
	$("#words").on("click", ".word-plus", function(){
		// Get the word count
		var wordCount = +$("#WordCount").val() + 1;
		console.log("word count: "+wordCount);
		
		$(this).closest(".word").before('<div class="word"><div class="letter blank"></div><button type="button" class="letter blank letter-minus" title="Remove this letter"><i class="fa fa-minus"></i></button><button type="button" class="letter letter-plus" title="Add another letter"><i class="fa fa-plus"></i></button><input type="hidden" class="WordLength" name="W'+wordCount+'" id="W'+wordCount+'" value="2"></div>');
		
		$("#WordCount").val(wordCount);
		
		// Disable/enable solve button
		$("button.solve").prop("disabled", !canSolve());
	});
	
	var combinations = null;
	var currentCombo = 0;
	var combosFound = 0;
	
	
	// Solve the puzzle
	$("#setupForm").submit(function(e){
		e.preventDefault();
		
		console.log("Submitting form...");
		$(this).find("button").prop("disabled", true);
		$("html, body, button").css('cursor', 'wait');
		
		var formData = $(this).serialize();
		
		$.post("php/solver.php", formData)
		.always(function(info, status){
			console.log("Submit setup form status: "+status);
			$(this).find("button").prop("disabled", false);
			$("html, body").css('cursor', 'default');
			$("button").css('cursor', 'pointer');
		})
		.done(function(response){
			combinations = response.combinations;
			combosFound = response.comboCount;
			console.log(combosFound+" word combinations found.");
			console.log(combinations);
			
			
			// Update UI
			$(".GridSetup").hide();
			$("#actions").html('<button type="button" class="button plain" id="lastCombo" title="Last word combination" disabled><i class="fa fa-arrow-left"></i></button><button type="button" class="button plain" id="nextCombo" title="Next word combination"><i class="fa fa-arrow-right"></i></button><button type="reset" class="button highlight reset" title="Solve another game">Done!</button>');
			if(currentCombo+1 >= combosFound){
				$("#nextCombo").prop("disabled", true);
			}
			
			// Show the first combination
			if(combosFound >0){
				displayCombo(currentCombo);
			} else {
				$("h2").html("0 OF 0 COMBINATIONS FOUND");
				$("#words").html("You're the 0%...");
			}
		})
		.fail(function(info, status, message){
			console.log("Submit setup form "+status+" message: '"+message+"'");
			$("#ErrorMessage").html("Sorry, this just occured '"+message+"'");
			$(".reset").prop("disabled", false);
		});
	});
	
	
	// Reset the page
	$("#actions").on("click", ".reset", function(){
		// Grid
		$("#setupForm").trigger("reset");
		$("#GridSize").val($("#GridBoard").data("size"));
		$(".GridSetup").show();
		$("#setupForm").find("button").prop("disabled", false);
		// Words
		$("h2").html("Solved by 100% of the players ;)");
		$("#words").html('<input type="hidden" name="WordCount" id="WordCount" value="1" required><div class="word"><div class="letter blank"></div><button type="button" class="letter blank letter-minus" title="Remove this letter"><i class="fa fa-minus"></i></button><button type="button" class="letter letter-plus" title="Add another letter"><i class="fa fa-plus"></i></button><input type="hidden" class="WordLength" name="W1" id="W1" value="2" required></div><div class="word"><button type="button" class="letter word-plus" title="Add another word"><i class="fa fa-plus"></i></button></div>');
		// Actions
		$("#actions").html('<button type="reset" class="button plain reset" title="Reset"><i class="fa fa-refresh" aria-hidden="true"></i></button><button type="submit" class="button highlight solve" title="Find all the possible combinations"><i class="fa fa-check" aria-hidden="true"></i> Solve!</button>');
		$("button.solve").prop("disabled", !canSolve());
		// variables
		combinations = null;
		currentCombo = 0;
		combosFound = 0;
		return true;
	});
	
	
	// Go to the next combination
	$("#actions").on("click", "#nextCombo", function(){
		displayCombo(++currentCombo);
		if(currentCombo+1 >= combosFound){
			$(this).prop("disabled", true);
		}
		$("#lastCombo").prop("disabled", false);
	});
	
	
	// Go back to the last combination
	$("#actions").on("click", "#lastCombo", function(){
		displayCombo(--currentCombo);
		if(currentCombo-1 < 0){
			$(this).prop("disabled", true);
		}
		$("#nextCombo").prop("disabled", false);
	});
	
	// Display the word comination
	function displayCombo(i){
		var combo = combinations[i];
		
		$("h2").html((i+1)+" OF "+combosFound+" COMBINATIONS FOUND");
		
		$("#words").html("");
		$.each(combo, function(w, word){
			// Add the Word
			
			$("#words").append('<div class="word">');
			var wordDiv = $("#words .word:last-child");
			for(var l = 0; l<word.length; l++){
				wordDiv.append('<div class="letter full">'+word[l]+'</div>');
			}
			$("#words").append('</div>');
		});
	}
	
	
	// Check if the form is valid or not
	function canSolve(){
		var result = true;
		// Test all squares have a value
		var emptySquares = $(".letter-tile").filter(function () { return !this.value; }).length;
		if(emptySquares > 0){
			result = false;
		}
		
		// Test there is enough word letters
		var gridSize = +$("#GridSize").val();
		var lettersNeeded = gridSize * gridSize;
		var lettersFound = +$('.letter.blank').length;
		if(lettersNeeded !== lettersFound){
			result = false;
		}
		
		// return true
		return result;
	}
	
});