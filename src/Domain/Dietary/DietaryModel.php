<?php
/**
 * @author: AlexK
 * Date: 27-Nov-18
 * Time: 1:55 PM
 */

namespace Cookbook\Domain\Dietary;

use Cookbook\DB\Database;

class DietaryModel
{
    private $db;
    private $gdb;

    public function __construct()
    {
        $this->db = Database::dsn();
        $this->gdb = Database::genericDsn();
    }

    public function getIngredientRestrictions($id)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT * FROM ' . DATABASE_DEFAULT . '.dietary_restrictions t1 JOIN ' . DATABASE_DEFAULT . '.ingredient_dietary_restrictions t2 USING(dietary_restriction_id) WHERE t2.ingredient_id = ?', [$id]);
    }

    public function getRestrictions()
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->select('SELECT * FROM ' . DATABASE_DEFAULT . '.dietary_restrictions ORDER BY restriction_name');
    }

    public function getRestriction($id)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->selectRow('SELECT * FROM ' . DATABASE_DEFAULT . '.dietary_restrictions WHERE dietary_restriction_id = ? LIMIT 1', [$id]);
    }

    public function getDishRestrictions($dishId)
    {
        $restrictionsRecipes = null;
        $restrictionRecipesIngredients = null;
        $recipes = $this->getDishRecipes($dishId);

        if ($recipes) {
            foreach ($recipes as $value) {
                $recipeIds = $this->getRecipeRecipes($value['recipe_id']);
                $ingredientIds = $this->getRecipeIngredients($recipeIds);
                $rr = $this->checkIngresdientRestrictions($ingredientIds);
                $ri = $this->getSelfRecipeIngrdientsRestrictions($value['recipe_id']);

                $rr ? $restrictionsRecipes[$value['recipe_id']] = $rr : null;
                $ri
                    ? (isset($restrictionsRecipes[$value['recipe_id']])
                        ? $restrictionsRecipes[$value['recipe_id']] += $ri
                        : $restrictionsRecipes[$value['recipe_id']] = $ri)
                    : null;
            }
        }

        $restrictionIngredients = $this->getSelfDishIngrdientsRestrictions($dishId);

//        var_dump($recipeIds);
//        var_dump($ingredientIds);
//        var_dump($restrictionsRecipes);
//        var_dump($restrictionIngredients);
//        exit;

        if ($restrictionsRecipes && $restrictionIngredients) {
            return $restrictionsRecipes + $restrictionIngredients;
        } elseif ($restrictionsRecipes) {
            return $restrictionsRecipes;
        } elseif ($restrictionIngredients) {
            return $restrictionIngredients;
        } else {
            return null;
        }
    }

    public function getRecipeRestrictions($recipeId)
    {
        $recipeIds = $this->getRecipeRecipes($recipeId);
        $ingredientIds = $this->getRecipeIngredients($recipeIds);
        $restrictionsRecipes = $this->checkIngresdientRestrictions($ingredientIds);
        $restrictionIngredients = $this->getSelfRecipeIngrdientsRestrictions($recipeId);

        if ($restrictionsRecipes && $restrictionIngredients) {
            return $restrictionsRecipes + $restrictionIngredients;
        } elseif ($restrictionsRecipes) {
            return $restrictionsRecipes;
        } elseif ($restrictionIngredients) {
            return $restrictionIngredients;
        } else {
            return null;
        }
    }

    private function getRecipeRecipes($recipeId)
    {
        $this->db->exec('SET NAMES \'utf8\'');

        $r = $this->db->select('SELECT DISTINCT t1.ingredient_recipe_id AS r1, t2.ingredient_recipe_id AS r2, t3.ingredient_recipe_id AS r3, t4.ingredient_recipe_id AS r4, t5.ingredient_recipe_id AS r5 FROM recipe_ingredients t1 LEFT JOIN recipe_ingredients t2 ON t2.recipe_id = t1.ingredient_recipe_id AND t2.ingredient_recipe_id > 0 LEFT JOIN recipe_ingredients t3 ON t3.recipe_id = t2.ingredient_recipe_id AND t3.ingredient_recipe_id > 0 LEFT JOIN recipe_ingredients t4 ON t4.recipe_id = t3.ingredient_recipe_id AND t4.ingredient_recipe_id > 0 LEFT JOIN recipe_ingredients t5 ON t5.recipe_id = t4.ingredient_recipe_id AND t5.ingredient_recipe_id > 0 WHERE t1.recipe_id = ? AND t1.ingredient_recipe_id > 0', [$recipeId]);

        if ($r) {
            $recipes = [];

            foreach ($r as $item) {
                foreach (array_filter($item) as $value) {
                    $recipes[$item['r1']][] = $value;
                }
            }

            return $recipes;
        } else {
            return null;
        }
    }

    private function getRecipeIngredients($recipeIds)
    {
        if ($recipeIds) {
            $ingredients = null;

            foreach ($recipeIds as $currentRecipeId => $recipes) {
                foreach (array_unique($recipes) as $recipeId) {
                    $r = $this->db->select('SELECT ingredient_id FROM recipe_ingredients WHERE recipe_id = ? AND ingredient_id > 0', [$recipeId]);

                    if ($r) {
                        foreach ($r as $value) {
                            $ingredients[$currentRecipeId][] = $value['ingredient_id'];
                        }
                    }
                }
            }

            return $ingredients;
        } else {
            return null;
        }
    }

    private function checkIngresdientRestrictions($ingredientIds)
    {
        if ($ingredientIds) {
            $restrictions = null;

            foreach ($ingredientIds as $currentRecipeId => $ingredients) {
                foreach ($ingredients as $ingredientId) {
                    $r = $this->gdb->select('SELECT t2.* FROM ' . DATABASE_DEFAULT . '.ingredient_dietary_restrictions t1 JOIN ' . DATABASE_DEFAULT . '.dietary_restrictions t2 USING(dietary_restriction_id) WHERE t1.ingredient_id = ?', [$ingredientId]);

                    if ($r) {
                        foreach ($r as $value) {
                            $restrictions[$currentRecipeId][] = [
                                'ingredient_id' => $ingredientId,
                                'restriction_name' => $value['restriction_name'],
                                'description' => $value['description']];
                        }
                    }
                }
            }

            return $restrictions;
        } else {
            return null;
        }
    }

    private function getSelfRecipeIngrdientsRestrictions($recipeId)
    {
        $r = $this->db->select('SELECT t1.ingredient_id, t3.restriction_name, t3.description FROM recipe_ingredients t1 JOIN ' . DATABASE_DEFAULT . '.ingredient_dietary_restrictions t2 USING (ingredient_id) JOIN ' . DATABASE_DEFAULT . '.dietary_restrictions t3 USING (dietary_restriction_id) WHERE t1.recipe_id = ? AND t1.ingredient_id > 0', [$recipeId]);

        if ($r) {
            $restrictions = null;

            foreach ($r as $value) {
                $restrictions[$value['ingredient_id']][] = $value;
            }

            return $restrictions;
        } else {
            return null;
        }
    }

    private function getDishRecipes($dishId)
    {
        return $this->db->select('SELECT recipe_id FROM dish_recipes WHERE dish_id = ?', [$dishId]);
    }

    private function getSelfDishIngrdientsRestrictions($dishId)
    {
        $r = $this->db->select('SELECT t1.ingredient_id, t3.restriction_name, t3.description FROM dish_ingredients t1 JOIN ' . DATABASE_DEFAULT . '.ingredient_dietary_restrictions t2 USING (ingredient_id) JOIN ' . DATABASE_DEFAULT . '.dietary_restrictions t3 USING (dietary_restriction_id) WHERE t1.dish_id = ?', [$dishId]);

        if ($r) {
            $restrictions = null;

            foreach ($r as $value) {
                $restrictions[$value['ingredient_id']][][] = $value;
            }

            return $restrictions;
        } else {
            return null;
        }
    }

    public function createRestriction($new_restriction_name, $new_description, $new_type)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        $this->gdb->insert(
            'dietary_restrictions',
            [
                'restriction_name' => $new_restriction_name,
                'description' => $new_description,
                'type' => $new_type
            ]
        );

        return $this->gdb->getLastInsertId();
    }

    public function updateRestriction($update_restriction_id, $update_restriction_name, $update_description, $update_type)
    {
        $this->gdb->exec('SET NAMES \'utf8\'');

        return $this->gdb->update(
            'dietary_restrictions',
            [
                'restriction_name' => $update_restriction_name,
                'description' => $update_description,
                'type' => $update_type
            ],
            [
                'dietary_restriction_id' => $update_restriction_id
            ]
        );
    }

    public function deleteRestriction($id)
    {
        return $this->gdb->delete('dietary_restrictions', ['dietary_restriction_id' => $id]);
    }
}