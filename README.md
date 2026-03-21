# PGChef
Description: Upload your inventory JSON and get recipe recommendations based on available ingredients or based on all ingredients available in game

## Project Concept

### Core Features
- **JSON Inventory Upload**: Players export their inventory from Project Gorgon and upload the JSON file
- **CDN-based Ingredient Parsing**: Extracts cooking materials from inventory, categorizing by meat/fish/vegetarian based on open PG cdn data
- **Recipe Matching**: Suggests recipes based on available ingredients with skill/gourmand level filtering
- **Missing Ingredient Analysis**: Shows recipes you're close to making (missing 1-2 ingredients)

### Advanced Filtering System - implemented
- **Cooking Skill Level Range**: Filter recipes by required cooking skill (1-100)
- **Gourmand Level Range**: Optional filter for gourmand XP progression
- **Meal Categories**: Meal vs. snack vs. instant-snack
  
### Advanced Filtering System - planned
- **Dietary Preferences**: Vegetarian vs. meat recipes
- **Cheese Content**: Toggle for recipes containing cheese
- **Inventory Fit**: Show only recipes possible with current ingredients vs including buyable ingredients
- **Gourmand Progression**: Show only recipes for food that have not been eaten yet, based on imported PG gourmand report

### Data Structure
- **Ingredient Classification**:
  - Meat / fish / vegetarian
  - Can be bought vs. rare/found-only
- **Recipe Categories**:
  - Level gourmand vs. level cooking skill
  - Vegetarian vs. meat/fish
  - Meal vs. snack vs. insta-snack
  - Contains dairy / fruit / eggs or is a drink (all currently classified as vegetarian, unless meat/fish)

### Possible Features 
- **Import/Export**:
  - Import gourmand report from PG (TXT)
  - Export recipe lists as JSON/TXT (shopping list)
  - Import recipe lists for event planning
    
    
## Technical Architecture

### Frontend
- HTML5 drag-and-drop file upload
- Responsive CSS
- JavaScript/jQuery
- AJAX communication with WordPress backend

### Backend (WordPress/PHP)
- Custom WordPress plugin with shortcode support
- JSON inventory parsing for Project Gorgon format
- Ingredient matching algorithms
- Recipe database management
- WordPress security (nonces, sanitization)    


### Data Sources
- Project Gorgon cdn (items & recipes)  https://cdn.projectgorgon.com/v465/data/index.html
- Python script extract_cooking_data.py to extract relevant data from cdn json-files
- Player inventory JSON exports


## Usage
1. Install as WordPress plugin
2. Add `[pg_cooking_helper]` shortcode to any page/post
3. Players upload their Project Gorgon inventory JSON
4. Set skill level preferences and dietary filters
5. View available recipes and missing ingredient lists
5. Set skill level preferences and dietary filters
6. View available recipes and missing ingredient lists
