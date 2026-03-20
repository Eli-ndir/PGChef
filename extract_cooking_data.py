#!/usr/bin/env python3
"""
PGChef Data Extractor
Extracts cooking recipes and ingredients from Project Gorgon game data files
and converts them into the compact JSON format used by the PGChef WordPress plugin.

Usage:
    python3 extract_cooking_data.py

Input files (place in same directory as this script):
    items_full.json   - Full game items data
    recipes_full.json - Full game recipes data

Output files:
    ingredients.json  - Cooking ingredients for the plugin
    recipes.json      - Cooking recipes for the plugin
"""

import json
import os
import sys


def load_json(filename):
    """Load a JSON file, with a clear error message if it's missing."""
    if not os.path.exists(filename):
        print(f"ERROR: Could not find '{filename}' - please place it in the same directory as this script.")
        sys.exit(1)
    with open(filename, 'r', encoding='utf-8') as f:
        return json.load(f)


def extract_ingredients(items_data):
    """
    Extract all items tagged as CookingIngredient.
    Returns both a list for the output file and a lookup dict for recipe processing.
    """
    ingredients_list = []
    item_lookup = {}  # ItemCode (int) -> item name, for resolving recipe ingredients

    for key, item in items_data.items():
        # Build the item code lookup for ALL items (needed for recipe resolution)
        item_code = int(key.replace('item_', ''))
        item_lookup[item_code] = item.get('Name', 'Unknown')

        # Only include CookingIngredients in the output file
        keywords = item.get('Keywords', [])
        if 'CookingIngredient' not in keywords:
            continue

        ingredients_list.append({
            "Name": item.get('Name', 'Unknown'),
            "StackSize": item.get('MaxStackSize', 1),
            # VegStatus not available in game data - defaulting to Unknown
            # You can manually update specific entries if needed
            "Rarity": "Unknown",
            "VegStatus": "Unknown"
        })

    # Sort alphabetically for easier reading/editing
    ingredients_list.sort(key=lambda x: x['Name'])

    return ingredients_list, item_lookup


def has_keyword(keywords, term):
    """Check if a keyword exists, ignoring any =value suffix."""
    return any(k == term or k.startswith(term + '=') for k in keywords)

def extract_preparedfood(items_data):
    """
    Extract all items tagged as PreparedFood.
    Returns a lookup dict indexed by name with Gourmand level,
    VegStatus and MealType for use in recipe processing.
    """
    preparedfood_lookup = {}

    for key, item in items_data.items():
        keywords = item.get('Keywords', [])
        item_code = int(key.replace('item_', ''))

        if not has_keyword(keywords, 'PreparedFood'):
	        continue
        if has_keyword(keywords, 'MeatDish'):
        	veg_status = 'Meat'
        elif has_keyword(keywords, 'FishDish'):
        	veg_status = 'Fish'
        elif has_keyword(keywords, 'VegetarianDish'):
        	veg_status = 'Vegetarian'
        else:
        	veg_status = 'Unknown'


        # Determine meal type
        if has_keyword(keywords, 'Snack'):
            meal_type = 'Snack'
        elif has_keyword(keywords, 'Meal'):
            meal_type = 'Meal'
        elif has_keyword(keywords, 'InstantSnack'):
            meal_type = 'InstantSnack'
        else:
            meal_type = 'Unknown'


        # Determine gourmand level
        skill_reqs = item.get('SkillReqs', {})
        gourmand_level = skill_reqs.get('Gourmand', 0)

        name = item.get('Name', 'Unknown')
        preparedfood_lookup[name] = {
            'MealType': meal_type,
            'VegStatus': veg_status,
            'FoodName': name,
            'GourmandLevel': gourmand_level
        }

    return preparedfood_lookup


def get_keyword_value(keywords, term):
    """Get the numeric value from a keyword like 'PreparedFood=50', returns 0 if not found."""
    for k in keywords:
        if k == term:
            return 0
        if k.startswith(term + '='):
            try:
                return int(k.split('=')[1])
            except ValueError:
                return 0
    return 0



def extract_recipes(recipes_data, item_lookup, preparedfood_lookup):
    """
    Extract all Cooking skill recipes and resolve ItemCodes to ingredient names.
    """
    recipes_list = []

    for key, recipe in recipes_data.items():
        # Only cooking recipes
        if recipe.get('Skill') != 'Cooking':
            continue

        # Resolve ingredient ItemCodes to names
        ingredients = []
        for ingredient in recipe.get('Ingredients', []):
            item_code = ingredient.get('ItemCode')
            if item_code is not None:
                name = item_lookup.get(item_code, f'Unknown (code {item_code})')
                ingredients.append({
                    "Name": name,
                    "NumberNeeded": ingredient.get('StackSize', 1)
                })
        
        # Look up prepared food details by recipe name
        food_details = preparedfood_lookup.get(recipe.get('Name'), {})
        recipes_list.append({
        		"Name": recipe.get('Name', 'Unknown'),
        		"CookingLevel": recipe.get('SkillLevelReq', 0),
        		"GourmandLevel": food_details.get('GourmandLevel', 0),
        		"MealType": food_details.get('MealType', 'Unknown'),
        		"VegStatus": food_details.get('VegStatus', 'Unknown'),
        		"Ingredients": ingredients
        		})

    # Sort by cooking level, then alphabetically
    recipes_list.sort(key=lambda x: (x['CookingLevel'], x['Name']))

    return recipes_list


def main():
    print("PGChef Data Extractor")
    print("=" * 40)

    # Load input files
    print("Loading items_full.json...")
    items_data = load_json('items_full.json')

    print("Loading recipes_full.json...")
    recipes_data = load_json('recipes_full.json')

    # Extract ingredients
    print("\nExtracting cooking ingredients...")
    ingredients_list, item_lookup = extract_ingredients(items_data)
    print(f"  Found {len(ingredients_list)} cooking ingredients")

    # Extract details of prepared food
    print("\nExtracting prepared food...")
    preparedfood_lookup = extract_preparedfood(items_data)
    print(f"  Found {len(preparedfood_lookup)} prepared food items")

    # Extract recipes
    print("Extracting cooking recipes...")
    recipes_list = extract_recipes(recipes_data, item_lookup, preparedfood_lookup)
    print(f"  Found {len(recipes_list)} cooking recipes")

    # Write ingredients.json
    ingredients_output = {"Items": ingredients_list}
    with open('ingredients.json', 'w', encoding='utf-8') as f:
        json.dump(ingredients_output, f, indent=2, ensure_ascii=False)
    print("\nWritten: ingredients.json")

    # Write recipes.json
    recipes_output = {"Recipes": recipes_list}
    with open('recipes.json', 'w', encoding='utf-8') as f:
        json.dump(recipes_output, f, indent=2, ensure_ascii=False)
    print("Written: recipes.json")

    print("\nDone! Copy ingredients.json and recipes.json to your plugin directory.")
    print(f"Summary: {len(ingredients_list)} ingredients, {len(recipes_list)} recipes")


if __name__ == '__main__':
    main()
