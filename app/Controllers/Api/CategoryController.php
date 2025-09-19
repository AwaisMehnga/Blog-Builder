<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\Category;
use App\Models\SeoMeta;
use Core\Database;

class CategoryController extends Controller
{
    /**
     * Get all categories with optional hierarchy
     */
    public function index(Request $request): Response
    {
        try {
            $hierarchical = $request->query('hierarchical', false);
            
            if ($hierarchical) {
                // Return hierarchical structure
                $categories = $this->getHierarchicalCategories();
            } else {
                // Return flat list
                $categories = Category::all();
                
                // Add blog count for each category
                foreach ($categories as &$category) {
                    $category = $this->enrichCategoryData($category);
                }
            }
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get a single category by ID
     */
    public function show(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $category = Category::find($id);
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found',
                    'data' => null
                ], 404);
            }
            
            $category = $this->enrichCategoryData($category);
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Create a new category
     */
    public function store(Request $request): Response
    {
        try {
            $data = $this->validateCategoryData($request);
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            } else {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }
            
            $category = Category::create($data);
            
            // Handle SEO meta
            if ($request->has('seo_meta')) {
                $this->saveSeoMeta($category->id, 'category', $request->input('seo_meta', []));
            }
            
            $category = $this->enrichCategoryData($category);
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Update an existing category
     */
    public function update(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $category = Category::find($id);
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found',
                    'data' => null
                ], 404);
            }
            
            $data = $this->validateCategoryData($request, $id);
            
            // Generate slug if changed
            if (isset($data['slug']) && $data['slug'] !== $category->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $id);
            } elseif (isset($data['name']) && $data['name'] !== $category->name && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }
            
            $category->update($data);
            
            // Handle SEO meta
            if ($request->has('seo_meta')) {
                $this->saveSeoMeta($category->id, 'category', $request->input('seo_meta', []));
            }
            
            $category = $this->enrichCategoryData($category);
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Delete a category
     */
    public function destroy(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $category = Category::find($id);
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found',
                    'data' => null
                ], 404);
            }
            
            // Check if category has blogs
            $blogCount = Database::getInstance()->fetchOne(
                "SELECT COUNT(*) as count FROM blogs WHERE category_id = ?",
                [$id]
            );
            
            if ($blogCount && $blogCount['count'] > 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Cannot delete category with existing blogs. Please reassign or delete the blogs first.',
                    'data' => null
                ], 400);
            }
            
            // Check if category has child categories
            $childCount = Database::getInstance()->fetchOne(
                "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?",
                [$id]
            );
            
            if ($childCount && $childCount['count'] > 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Cannot delete category with child categories. Please delete child categories first.',
                    'data' => null
                ], 400);
            }
            
            // Delete SEO meta
            Database::getInstance()->execute(
                "DELETE FROM seo_meta WHERE entity_type = 'category' AND entity_id = ?",
                [$id]
            );
            
            $category->delete();
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => null
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get category hierarchy
     */
    public function hierarchy(Request $request): Response
    {
        try {
            $categories = $this->getHierarchicalCategories();
            
            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Validate category data
     */
    private function validateCategoryData(Request $request, int $excludeId = null): array
    {
        $data = $request->only(['name', 'slug', 'description', 'parent_id']);
        
        // Required fields
        if (empty($data['name'])) {
            throw new \Exception('Name is required');
        }
        
        // Validate parent category
        if (!empty($data['parent_id'])) {
            if ($excludeId && $data['parent_id'] == $excludeId) {
                throw new \Exception('Category cannot be its own parent');
            }
            
            $parent = Category::find($data['parent_id']);
            if (!$parent) {
                throw new \Exception('Invalid parent category');
            }
            
            // Check for circular reference if updating
            if ($excludeId) {
                if ($this->wouldCreateCircularReference($excludeId, $data['parent_id'])) {
                    throw new \Exception('This would create a circular reference');
                }
            }
        }
        
        // Validate slug uniqueness
        if (!empty($data['slug'])) {
            $query = Category::where('slug', $data['slug']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->first()) {
                throw new \Exception('Slug already exists');
            }
        }
        
        return $data;
    }

    /**
     * Check for circular reference
     */
    private function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        $currentParentId = $parentId;
        
        while ($currentParentId) {
            if ($currentParentId == $categoryId) {
                return true;
            }
            
            $parent = Category::find($currentParentId);
            $currentParentId = $parent ? $parent->parent_id : null;
        }
        
        return false;
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug(string $text, int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $query = Category::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->first()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Get hierarchical categories
     */
    private function getHierarchicalCategories(): array
    {
        $allCategories = Category::all();
        $categoriesById = [];
        $rootCategories = [];
        
        // Index categories by ID
        foreach ($allCategories as $category) {
            $categoryData = $this->enrichCategoryData($category);
            $categoryData['children'] = [];
            $categoriesById[$category->id] = $categoryData;
        }
        
        // Build hierarchy
        foreach ($categoriesById as $category) {
            if ($category['parent_id']) {
                if (isset($categoriesById[$category['parent_id']])) {
                    $categoriesById[$category['parent_id']]['children'][] = &$categoriesById[$category['id']];
                }
            } else {
                $rootCategories[] = &$categoriesById[$category['id']];
            }
        }
        
        return $rootCategories;
    }

    /**
     * Enrich category data with additional information
     */
    private function enrichCategoryData($category): array
    {
        $categoryData = $category->toArray();
        
        // Get blog count
        $blogCount = Database::getInstance()->fetchOne(
            "SELECT COUNT(*) as count FROM blogs WHERE category_id = ?",
            [$category->id]
        );
        $categoryData['blog_count'] = $blogCount ? (int)$blogCount['count'] : 0;
        
        // Get parent category
        if ($category->parent_id) {
            $parent = Category::find($category->parent_id);
            $categoryData['parent'] = $parent ? $parent->toArray() : null;
        } else {
            $categoryData['parent'] = null;
        }
        
        // Get child categories count
        $childCount = Database::getInstance()->fetchOne(
            "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?",
            [$category->id]
        );
        $categoryData['child_count'] = $childCount ? (int)$childCount['count'] : 0;
        
        // Get SEO meta
        $seoMeta = Database::getInstance()->fetchOne(
            "SELECT * FROM seo_meta WHERE entity_type = 'category' AND entity_id = ?",
            [$category->id]
        );
        $categoryData['seo_meta'] = $seoMeta;
        
        return $categoryData;
    }

    /**
     * Save SEO meta data
     */
    private function saveSeoMeta(int $entityId, string $entityType, array $seoData): void
    {
        // Remove existing SEO meta
        Database::getInstance()->execute(
            "DELETE FROM seo_meta WHERE entity_type = ? AND entity_id = ?",
            [$entityType, $entityId]
        );
        
        if (!empty($seoData)) {
            $seoData['entity_type'] = $entityType;
            $seoData['entity_id'] = $entityId;
            
            SeoMeta::create($seoData);
        }
    }
}
