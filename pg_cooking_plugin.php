<?php
/**
* Plugin Name: PGChef - Project Gorgon Cooking Helper
* Version: 1.1
* Description: Upload your inventory JSON and get recipe recommendations based on available ingredients or based on all ingredients available in game
* Author: Elindir & Claude AI
* Update 3/21/26: Showing Meal type (Meal/Snack/Instantsnack) and VegStatus (Meat/Fish/Vegetarian)
* Update 3/22/26: Mealtypes (Meal/Snack/Instant-Snack) can now be selected in your search
*/





// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

class PGCookingHelper {
    
	public function __construct() {
		add_action('init', array($this, 'init'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_shortcode('pg_cooking_helper', array($this, 'render_frontend_ui'));
		add_action('wp_ajax_parse_inventory', array($this, 'handle_inventory_upload'));
		add_action('wp_ajax_nopriv_parse_inventory', array($this, 'handle_inventory_upload'));
		add_action('wp_ajax_get_recipes', array($this, 'get_recipe_suggestions'));
		add_action('wp_ajax_nopriv_get_recipes', array($this, 'get_recipe_suggestions'));
	}
    
	public function init() {
		// Plugin initialization
	}
    
	public function enqueue_scripts() {
		// wp_enqueue_script('pg-cooking-js', plugin_dir_url(__FILE__) . 'pg-cooking.js', array('jquery'), '1.0', true);
		// wp_enqueue_style('pg-cooking-css', plugin_dir_url(__FILE__) . 'pg-cooking.css', array(), '1.0');
        
		// wp_localize_script('jquery', 'pg_ajax', array(
		//     'ajax_url' => admin_url('admin-ajax.php'),
		//     'nonce' => wp_create_nonce('pg_cooking_nonce')
		// ));
	}
    
	public function render_frontend_ui() {
			

			wp_enqueue_script('jquery');
			wp_localize_script('jquery', 'pg_ajax', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('pg_cooking_nonce')
			));        
        
        
		ob_start();
		?>
		<div id="pg-cooking-helper">
				<div class="pg-upload-section">
					<strong> 🍳 Project: Gorgon PG Chef Cooking Helper</strong>
					<p>Upload your inventory JSON (or let PG provide all ingredients - no VIP needed) to get recipe recommendations!</p>
                
					<div id="uploadOptions" class="pg-upload-area" id="uploadArea">
						<div class="upload-content">
								<div class="upload-icon">📁</div>
								<p>Drop your inventory JSON here or click to browse - or pick the full PG ingredients data</p>
								<input type="file" id="inventoryFile" accept=".json" style="display: none;">
								<button class="pg-btn" onclick="document.getElementById('inventoryFile').click()">Use Your Own Storage File</button>
								<button class="pg-btn" id="useAllIngredientsBtn">Use All PG Ingredients</button>
						</div>
					</div>
					<div id="fileInfo" class="file-info" style="display: none;"></div>	
				</div>
            
				<div id="skillSettings" class="pg-skill-section" style="display: none;">
									
					<strong>Skill Level Preferences</strong>
					<div class="skill-controls">
						<label>
								Cooking Skill Level Range:
								<input type="number" id="minCookingLevel" placeholder="Min level" min="1" max="100" value="1">
								<input type="number" id="maxCookingLevel" placeholder="Max level" min="1" max="100" value="50">
						</label>
                    
						<label>
								<br />Gourmand Level Range (optional):
								<input type="number" id="minGourmandLevel" placeholder="Min level" min="1" max="100" value="">
								<input type="number" id="maxGourmandLevel" placeholder="Max level" min="1" max="100" value="">
						</label>
					
					<label>
						 <br />   					
    					<input type="checkbox" id="useAllIngredients">
   					 Show all possible recipes (ignore my inventory)
					</label>
		<!---			<label>
						<br />					
						<button class="pg-btn" id="selectMealTypeBtn">Include Snacks or Meals or InstantSnacks (default includes all)</button> 
					</label>         	--->			
					
<!--					<label>
						<br />    					
    					<input type="checkbox" id="showOnlyCanMakeList">
   					 Show only recipes I can make with my inventory (no partial recipes)
					</label>              -->
            </div>
         		<div id="mealtypeSettings" class="pg-mealtype-section" style="display: none;">	
					<strong>Included Meal Types (select any, default: all)</strong>
					<div class="mealtype-controls">		
					<label>
						 <br />   					
    					<input type="checkbox" id="selectMeals">
   					 Meals
    					<input type="checkbox" id="selectSnacks">
   					 Snacks
     					<input type="checkbox" id="selectInstantSnacks">
   					 Instant-Snacks
					</label>
					</div> 	     
              </div>
              
                
					<button class="pg-btn pg-btn-primary" id="findRecipes">Find Recipes!</button>
				</div>
            
				<div id="results" class="pg-results" style="display: none;">
					<div id="availableRecipes" class="recipe-section">
						<strong>🟢 Recipes You Can Make</strong>
						<div id="canMakeList"></div>
					</div>
                
					<div id="partialRecipes" class="recipe-section">
						<strong>🟡 Recipes You're Close To</strong>
						<div id="almostCanMakeList"></div>
					</div>
				</div>
            
				<div id="loading" class="loading" style="display: none;">
					<div class="spinner"></div>
					<p>Analyzing your inventory...</p>
				</div>
		</div>
        
		<style>
		#pg-cooking-helper {
				max-width: 800px;
				margin: 20px auto;
				padding: 20px;
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		}
        
		.pg-upload-area {
				border: 2px dashed #4CAF50;
				border-radius: 10px;
				padding: 30px;
				text-align: center;
				background: #f9f9f9;
				transition: all 0.3s ease;
				cursor: pointer;
		}
        
		.pg-upload-area:hover, .pg-upload-area.dragover {
				border-color: #45a049;
				background: #f0f8f0;
		}
        
		.upload-icon {
				font-size: 3em;
				margin-bottom: 10px;
		}
        
		.pg-btn {
				background: #4CAF50;
				color: white;
				border: none;
				padding: 12px 24px;
				border-radius: 6px;
				cursor: pointer;
				font-size: 12px;
				margin: 10px 5px;
		}
        
		.pg-btn:hover {
				background: #45a049;
		}
        
		.pg-btn-primary {
				background: #2196F3;
		}
        
		.pg-btn-primary:hover {
				background: #1976D2;
		}
        
		.pg-skill-section, .pg-results {
				background: white;
				border: 1px solid #ddd;
				border-radius: 8px;
				padding: 16px;
				margin: 20px 0;
		}
		
		.pg-mealtype-section {
				background: white;
				border: 1px solid #ddd;
				border-radius: 8px;
				padding: 16px;
				margin: 20px 0;
		}
        
		.quality-controls {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
				align-items: center;
		}
        
		.quality-controls label {
				display: flex;
				flex-direction: column;
				gap: 5px;
		}
        
		.quality-controls select, .quality-controls input {
				padding: 8px;
				border: 1px solid #ccc;
				border-radius: 4px;
		}
        
		.recipe-section {
				margin: 15px 0;
		}
        
		.recipe-item {
				background: #f8f9fa;
				border: 1px solid #e9ecef;
				border-radius: 6px;
				padding: 10px;
				margin: 10px 0;
		}
        
		.recipe-name {
/*				font-weight: bold;*/
				color: #2c3e50;
				margin-bottom: 5px;
		}
        
		.recipe-level {
				color: #6c757d;
				font-size: 0.9em;
		}
        
		.ingredients-needed {
				margin-top: 10px;
		}
        
		.ingredient-have {
				color: #28a745;
		}
        
		.ingredient-missing {
				color: #dc3545;
				font-weight: bold;
		}
        
		.loading {
				text-align: center;
				padding: 40px;
		}
        
		.spinner {
				border: 4px solid #f3f3f3;
				border-top: 4px solid #3498db;
				border-radius: 50%;
				width: 40px;
				height: 40px;
				animation: spin 1s linear infinite;
				margin: 0 auto 20px;
		}
        
		@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
		}
        
		.file-info {
				background: #e8f5e8;
				border: 1px solid #4CAF50;
				border-radius: 4px;
				padding: 10px;
				margin: 10px 0;
		}
		</style>
        
		<script>

		jQuery(document).ready(function($) {
				let inventoryData = null;
            
				// File upload handling
				const uploadArea = $('#uploadArea');
				const fileInput = $('#inventoryFile');
				const fileDatabase = $('#inventoryDatabase');
				let useingredients_db_handle = false;
				//let selectMealType_handle = false;
            
				// Drag and drop
				uploadArea.on('dragover', function(e) {
					e.preventDefault();
					$(this).addClass('dragover');
				});
            
				uploadArea.on('dragleave', function() {
					$(this).removeClass('dragover');
				});
            
				uploadArea.on('drop', function(e) {
					e.preventDefault();
					$(this).removeClass('dragover');
					const files = e.originalEvent.dataTransfer.files;
					if (files.length > 0) {
						handleFile(files[0]);
					}
				});
            
				fileInput.on('change', function() {
					if (this.files.length > 0) {
						handleFile(this.files[0]);
					}
					$('#uploadOptions').hide();
					$('#fileInfo').html('<italic>Mode:</italic> Using your stored ingredients').show();
				});
				      
				$('#useAllIngredientsBtn').on('click', function() {
				    useingredients_db_handle = true;
				    inventoryData = [];
				    $('#uploadOptions').hide();
				    $('#skillSettings').show();
				    $('#fileInfo').html('<italic>Mode:</italic> Using all PG ingredients').show();
				});
				
			  // $('#selectMealTypeBtn').on('click', function() {
       				//selectMealType_handle = true; 
       				// use 3 check boxes to include Meal/Snack/InstantSnack
       				$('#mealtypeSettings').show(); 
       				//$('#mealtypeInfo').html('<italic>Mode:</italic> Please select a meal type').show();
           //});
            
				function handleFile(file) {
					if (!file.name.toLowerCase().endsWith('.json')) {
						alert('Please select a JSON file');
						return;
					}
                
					$('#loading').show();
					const reader = new FileReader();
                
					reader.onload = function(e) {
						try {
								inventoryData = JSON.parse(e.target.result);
                          
								console.log('Parsed inventory: ', inventoryData);
								console.log('Parsed type: ', typeof inventoryData);  
                          
								processInventory(inventoryData, file.name);
							} catch (error) {
                     	                 
								alert('Error parsing JSON file: ' + error.message);
								$('#loading').hide();
							}
					};
                
					reader.readAsText(file);
				}
            
				function processInventory(data, filename) {
					console.log('========= DEBUG info ========= data:', data);
					console.log('Data type:', typeof data);
					console.log('Stringified:', JSON.stringify(data));
					console.log('==================');					
		
								                
					$.ajax({
						url: pg_ajax.ajax_url,
						type: 'POST',
						data: {
								action: 'parse_inventory',
								nonce: pg_ajax.nonce,
								inventory_data: JSON.stringify(data)
						},              
						success: function(response) {
								console.log('success:',typeof data);							                
								showInventoryInfo(response.data, filename);
									$('#skillSettings').show(); 	
									$('#loading').hide();
								},
						error: function() {
								alert('Error uploading file');
								alert('Error processing inventory: ' + response.data.message);
								$('#loading').hide(); 
						}
					});
				}
            
				function showInventoryInfo(data, filename) {
					console.log('showInventoryInfo received:', data);					
					
				}
            
				$('#findRecipes').on('click', function() {
					if (!useingredients_db_handle){					
						if (!inventoryData) {
							alert('Please upload an inventory file first');
							return;
						}
					}
					else{
						inventoryData=[];
					}
					
						
                
					$('#loading').show();
					$('#results').hide();
                
					const settings = {
						min_cooking_level: $('#minCookingLevel').val(),
						max_cooking_level: $('#maxCookingLevel').val(),
						min_gourmand_level: $('#minGourmandLevel').val(),
						max_gourmand_level: $('#maxGourmandLevel').val(),
						use_all_ingredients: $('#useAllIngredients').prop('checked'),
						select_meal_type_Meal: $('#selectMeals').prop('checked'), 
						select_meal_type_Snack: $('#selectSnacks').prop('checked'), 
						select_meal_type_InstantSnack: $('#selectInstantSnacks').prop('checked'), 
						useingredients_db: useingredients_db_handle
					};    
                
					$.ajax({
						url: pg_ajax.ajax_url,
						type: 'POST',
						data: {
								action: 'get_recipes',
								nonce: pg_ajax.nonce,
								inventory_data: JSON.stringify(inventoryData),
								settings: JSON.stringify(settings)
						},
						success: function(response) {
								if (response.success) {
									displayRecipes(response.data);
								} else {								
									 console.log('Full response:', response);
								    console.log('Response data:', response.data);
								}
								$('#loading').hide();
						},
						error: function() {
								alert('Error processing recipes');
								$('#loading').hide();
						}
					});
				});
            
				function displayRecipes(data) {
					 console.log('displayRecipes received:', data);
				    if (!data) {
				        console.log('No data received');
				        return;
				    }
				    
					const canMake = data.can_make || [];
					const almostCanMake = data.almost_can_make || [];
                
					let canMakeHtml = '';
					if (canMake.length > 0) {
						canMake.forEach(recipe => {
								canMakeHtml += `
									<div class="recipe-item">
										<div class="recipe-name"><strong>${recipe.Name} </strong><italic>• ${recipe.MealType} • ${recipe.VegStatus}</italic></div>
										<div class="recipe-level">Cooking Level ${recipe.CookingLevel} • Gourmand Level ${recipe.GourmandLevel}</div>
										<div class="ingredients-needed">
												<italic>Ingredients:</italic> ${recipe.Ingredients.map(ing => 
													`<span class="ingredient-have">${ing}</span>`
												).join(', ')}
										</div>
									</div>
								`;
						});
					} else {
						canMakeHtml = '<p>No recipes available with current ingredients.</p>';
						console.log('showInventoryInfo received:', data);
					}
                
					let almostCanMakeHtml = '';
					if (almostCanMake.length > 0) {
						almostCanMake.forEach(recipe => {
								almostCanMakeHtml += `
									<div class="recipe-item">
										<div class="recipe-name"><strong>${recipe.Name} </strong><italic>• ${recipe.MealType} • ${recipe.VegStatus}</italic></div>
										<div class="recipe-level">Cooking Level ${recipe.CookingLevel} • Gourmand Level ${recipe.GourmandLevel}</div>
										<div class="ingredients-needed">
												<italic>Have:</italic> ${recipe.have_ingredients.map(ing => 
													`<span class="ingredient-have">${ing}</span>`
												).join(', ')}<br>
												<italic>Need:</italic> ${recipe.missing_ingredients.map(ing => 
													`<span class="ingredient-missing">${ing}</span>`
												).join(', ')}
										</div>
									</div>
								`;
						});
					} else {
						almostCanMakeHtml = '<p>No partial matches found.</p>';
					}
                
					$('#canMakeList').html(canMakeHtml);
					$('#almostCanMakeList').html(almostCanMakeHtml);
					$('#results').show();
				}
		});
		
  
		</script>
		<?php
		return ob_get_clean();
	}
        
        
 
	public function handle_inventory_upload() {
	
		if (!wp_verify_nonce($_POST['nonce'], 'pg_cooking_nonce')) {
			wp_send_json_error(array('message' => 'Invalid nonce'));
		}
    
		$raw = $_POST['inventory_data'];
		$raw = stripslashes($raw);    
// Replace tab characters inside the content
		$raw = preg_replace('/\t/', ' ', $raw);
		$inventory = json_decode($raw, true);		    
    
		if (strlen($raw) > 5242880) { // 5MB limit
		    wp_send_json_error(array('message' => 'File too large'));
		}      
    
		if (!$inventory){
			wp_send_json_error(array(
				'message' => 'Invalid JSON data - check console'
			));
		}   
    
		// Parse inventory to find cooking ingredients
		$ingredients_db = $this->load_ingredients_database();
		
		$ingredients = $this->parse_cooking_ingredients($inventory, $ingredients_db);
		wp_send_json_success(array(
		    'ingredient_count' => count($ingredients),
		    'total_items' => is_array($inventory['Items']) ? count($inventory['Items']) : count($inventory),
		    'Ingredients' => $ingredients
		));
	}
    
	public function get_recipe_suggestions() {
		// Verify nonce
		if (!wp_verify_nonce($_POST['nonce'], 'pg_cooking_nonce')) {
				wp_send_json_error(array('message' => 'Invalid nonce'));
		}        
        
		$raw = $_POST['inventory_data'];
		$raw = stripslashes($raw);    
// Replace tab characters inside the content
		$raw = preg_replace('/\t/', ' ', $raw);
		$inventory = json_decode($raw, true);	
		
		$settings_json = $_POST['settings'];		
		$settings = json_decode(stripslashes($settings_json), true);		
		
		$useingredients_db = $settings['useingredients_db'];

		$ingredients_db = $this->load_ingredients_database();
		$recipes_debug = $this->get_recipes_database();	
		
		if (!$useingredients_db) {
		$ingredients = $this->parse_cooking_ingredients($inventory, $ingredients_db);
		}
		else{
			$ingredients = $ingredients_db;
		}
		
		$recipes = $recipes_debug['recipes']; 
 
		$can_make = array();
		$almost_can_make = array();
		
        
		foreach ($recipes as $recipe) {
			// Filter by cooking level range
			if ($recipe['CookingLevel'] < intval($settings['min_cooking_level']) || 
				$recipe['CookingLevel'] > intval($settings['max_cooking_level'])) {
				continue;
			}
         
			// Filter by gourmand level range if specified
			if (!empty($settings['min_gourmand_level']) && 
				$recipe['GourmandLevel'] < intval($settings['min_gourmand_level'])) {
				continue;
			}
			if (!empty($settings['max_gourmand_level']) && 
				$recipe['GourmandLevel'] > intval($settings['max_gourmand_level'])) {
				continue;
			}
			
			$meal_filter_active = $settings['select_meal_type_Meal'] || 
                      $settings['select_meal_type_Snack'] || 
                      $settings['select_meal_type_InstantSnack'];
         
			$missing_ingredients = array();
			$have_ingredients = array();
            
	      $ingredient_pool = $settings['use_all_ingredients'] 
 		   ? $ingredients_db 
  			: $ingredients;
            
				foreach ($recipe['Ingredients'] as $required_ingredient) {
					$found = false;
			
					//if MealType is selected and the recipe belongs to that category, continue		
					if (!$meal_filter_active || ($settings['select_meal_type_Meal'] && $recipe['MealType'] == 'Meal') ||
				    ($settings['select_meal_type_Snack'] && $recipe['MealType'] == 'Snack') ||
				    ($settings['select_meal_type_InstantSnack'] && $recipe['MealType'] == 'InstantSnack')) {

						foreach ($ingredient_pool as $have_ingredient)	{						
							if (stripos($have_ingredient['Name'], $required_ingredient) !== false ||
									stripos($required_ingredient, $have_ingredient['Name']) !== false) {
									$have_ingredients[] = $required_ingredient;
									$found = true;
									break;
							}
						}
					}
                
					if (!$found) {
						$missing_ingredients[] = $required_ingredient;
					}
				}
            
				if (empty($missing_ingredients)) {
					// Can make this recipe
					$can_make[] = array(
						'Name' => $recipe['Name'],
						'CookingLevel' => $recipe['CookingLevel'],
						'GourmandLevel' => $recipe['GourmandLevel'],
						'MealType' => $recipe['MealType'],
						'VegStatus' => $recipe['VegStatus'],
						'Ingredients' => $have_ingredients
					);
				} elseif (count($missing_ingredients) <= 2 && count($have_ingredients) > 0) {
					// Almost can make (missing 1-2 ingredients)
					$almost_can_make[] = array(
						'Name' => $recipe['Name'],
						'CookingLevel' => $recipe['CookingLevel'],
						'GourmandLevel' => $recipe['GourmandLevel'],
						'MealType' => $recipe['MealType'],
						'VegStatus' => $recipe['VegStatus'],
						'have_ingredients' => $have_ingredients,
						'missing_ingredients' => $missing_ingredients
					);
				}
		}
      wp_send_json_success(array(  
	     'can_make' => $can_make,
	     'almost_can_make' => $almost_can_make,
	     'have_ingredient' => $have_ingredient,
	     'required_ingredient' => $required_ingredient,
	     'Ingredients' => $ingredient_names,
		  'debug_recipes' => $recipes
	   ));
	}

	private function load_ingredients_database() {
		$json_file = plugin_dir_path(__FILE__) . 'ingredients.json';        
    
		if (!file_exists($json_file)) {
				return array();
		}
	    
		$json_content = file_get_contents($json_file);
		// Replace tab characters inside the content
		$json_content = preg_replace('/\t/', ' ', $json_content);
		$data = json_decode($json_content, true);		
		
		if (json_last_error() !== JSON_ERROR_NONE) {
			return array();
		}
    
		// Create a lookup array indexed by lowercase ingredient name
		$ingredients_lookup = array();
	    
		if (isset($data['Items']) && is_array($data['Items'])) {
			foreach ($data['Items'] as $item) {
				$name_key = strtolower($this->clean_ingredient_name($item['Name']));
				$ingredients_lookup[$name_key] = array(
					'Name' => $this->clean_ingredient_name($item['Name']),
					'stack_size' => intval($item['StackSize']),
					'rarity' => $item['Rarity'],
					'veg_status' => $item['VegStatus'],
					'is_buyable' => ($item['Rarity'] === 'Buyable'),
					'is_meat' => in_array($item['VegStatus'], array('Meat', 'Fish'))
				);
			}
		}
	    
		return $ingredients_lookup;
	}

	private function clean_ingredient_name($Name) {
	    // Remove tabs, newlines, extra whitespace, non-breaking spaces
	    $Name = preg_replace('/[\t\n\r\x0B\xA0\x00]+/', '', $Name);
	    // Collapse multiple spaces into one
	    $Name = preg_replace('/\s+/', ' ', $Name);
	    // Trim leading/trailing whitespace
	    $Name = trim($Name);
	    return $Name;
	}

	private function parse_cooking_ingredients($inventory, $ingredients_db) {

		$ingredients = array();
    
		// Parse inventory items - handle the actual JSON structure from PG
		$items_array = $inventory['Items'] ?? $inventory;
    
		if (!is_array($items_array)) {
			return $ingredients;
		}
    
		foreach ($items_array as $item) {
			// Skip if item doesn't have a name
			if (!isset($item['Name'])) {
				continue;
			}
        
			$item_name = strtolower($this->clean_ingredient_name($item['Name']));
        
			// Check if this item is in our ingredients database
			if (isset($ingredients_db[$item_name])) {
				$ing_data = $ingredients_db[$item_name];
            
				$ingredients[] = array(
					'Name' => $item_name,
					'display_name' => $ing_data['Name'], // Original capitalization from database
					'quantity' => isset($item['StackSize']) ? intval($item['StackSize']) : 1,
					'is_meat' => $ing_data['is_meat'],
					'can_buy' => $ing_data['is_buyable'],
					'veg_status' => $ing_data['veg_status'],
					'rarity' => $ing_data['rarity'],
					'storage_location' => isset($item['StorageVault']) ? $item['StorageVault'] : 'Unknown'
				);
			} else {
			}
          
       
		}
        
	return $ingredients;
	}


/*	private function load_gourmand_report_from_txt() {

    return $result;	    
	} */
	            
	private function load_recipes_from_json() {
	    $result = array(
        'success' => false,
        'recipes' => array(),
        'debug_path' => plugin_dir_path(__FILE__) . 'recipes.json',
        'debug_exists' => false,
        'debug_json_error' => '',
        'debug_raw' => '',
        'debug_recipe_count' => 0
	    );
    
    	// Path to the recipes JSON file (same directory as the plugin)
		$json_file = plugin_dir_path(__FILE__) . 'recipes.json';
	    
		// Check if file exists
		if (!file_exists($json_file)) {
			return array(); // Return empty array if file doesn't exist
		}
	    
    $result['debug_exists'] = true;
    $json_content = file_get_contents($json_file);
    $result['debug_raw'] = substr($json_content, 0, 200);
    $json_content = preg_replace('/\t/', ' ', $json_content);
    $data = json_decode($json_content, true);
    $result['debug_json_error'] = json_last_error_msg();
    
    if (json_last_error() === JSON_ERROR_NONE && isset($data['Recipes'])) {
        $result['success'] = true;
        $result['recipes'] = $data['Recipes'];
        $result['debug_recipe_count'] = count($data['Recipes']);
    }
    
    return $result;	    
	}    
	private function get_recipes_database() {
		$loaded = $this->load_recipes_from_json();		
		
    // Always send debug info up the chain
    if (!$loaded['success']) {
        // use baked potato fallback but carry debug info
        return array(
            'success' => false,
            'debug' => $loaded,
				"recipes" => array(
                array(
				      'Name' => "Baked Potato",
				      'CookingLevel' => 0,
				      'GourmandLevel' => 0,
				      'VegStatus' => "Vegetarian",
				      'Ingredients' => array('potato', 'salt')
				      )
			      )
        );
    }	
		

    
		// Transform the JSON structure to match code expectations (assume messiness)
		$formatted_recipes = array();
    
	foreach ($loaded['recipes'] as $recipe) {
		// Extract just the ingredient names (lowercase for matching)
		$ingredient_names = array();
		
		if (isset($recipe['Ingredients']) && is_array($recipe['Ingredients'])) {
			foreach ($recipe['Ingredients'] as $ingredient) {
				if (isset($ingredient['Name'])) {
					$ingredient_names[] = strtolower($this->clean_ingredient_name($ingredient['Name']));
				}
			}
		}
        
		$formatted_recipes[] = array(
			'Name' => $this->clean_ingredient_name($recipe['Name']),
			'CookingLevel' => intval($recipe['CookingLevel']),
			'GourmandLevel' => intval($recipe['GourmandLevel']),
			'MealType' => $this->clean_ingredient_name($recipe['MealType']),
			'VegStatus' => $this->clean_ingredient_name($recipe['VegStatus']),
			'Ingredients' => $ingredient_names,
			'Ingredients_detailed' => $recipe['Ingredients'] // Store full ingredient data for future use
		);
		
	}
    
    return array(
        'success' => true,
        'debug' => $loaded,
        'recipes' => $formatted_recipes
    );
	}
}

// Initialize the plugin
new PGCookingHelper();
?>
