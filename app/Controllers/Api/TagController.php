<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\Tag;
use Core\Database;

class TagController extends Controller
{
    /**
     * Get all tags with optional blog count
     */
    public function index(Request $request): Response
    {
        try {
            $withCount = $request->query('with_count', false);
            
            $tags = Tag::all();
            
            if ($withCount) {
                foreach ($tags as &$tag) {
                    $tag = $this->enrichTagData($tag);
                }
            }
            
            return $this->json([
                'success' => true,
                'data' => $tags
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to fetch tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single tag by ID
     */
    public function show(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $tag = Tag::find($id);
            
            if (!$tag) {
                return $this->json([
                    'success' => false,
                    'message' => 'Tag not found'
                ], 404);
            }
            
            $tag = $this->enrichTagData($tag);
            
            return $this->json([
                'success' => true,
                'data' => $tag
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to fetch tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new tag
     */
    public function store(Request $request): Response
    {
        try {
            $data = $this->validateTagData($request);
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            } else {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }
            
            $tag = Tag::create($data);
            $tag = $this->enrichTagData($tag);
            
            return $this->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'data' => $tag
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to create tag',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update an existing tag
     */
    public function update(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $tag = Tag::find($id);
            
            if (!$tag) {
                return $this->json([
                    'success' => false,
                    'message' => 'Tag not found'
                ], 404);
            }
            
            $data = $this->validateTagData($request, $id);
            
            // Generate slug if changed
            if (isset($data['slug']) && $data['slug'] !== $tag->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $id);
            } elseif (isset($data['name']) && $data['name'] !== $tag->name && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $id);
            }
            
            $tag->update($data);
            $tag = $this->enrichTagData($tag);
            
            return $this->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'data' => $tag
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update tag',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete a tag
     */
    public function destroy(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $tag = Tag::find($id);
            
            if (!$tag) {
                return $this->json([
                    'success' => false,
                    'message' => 'Tag not found'
                ], 404);
            }
            
            // Remove tag associations with blogs
            Database::getInstance()->execute(
                "DELETE FROM blog_tags WHERE tag_id = ?",
                [$id]
            );
            
            $tag->delete();
            
            return $this->json([
                'success' => true,
                'message' => 'Tag deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to delete tag',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete tags
     */
    public function bulkDelete(Request $request): Response
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No tag IDs provided'
                ], 400);
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            // Remove tag associations with blogs
            Database::getInstance()->execute(
                "DELETE FROM blog_tags WHERE tag_id IN ($placeholders)",
                $ids
            );
            
            // Delete tags
            Database::getInstance()->execute(
                "DELETE FROM tags WHERE id IN ($placeholders)",
                $ids
            );
            
            return $this->json([
                'success' => true,
                'message' => 'Tags deleted successfully',
                'affected_count' => count($ids)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Bulk delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search tags by name
     */
    public function search(Request $request): Response
    {
        try {
            $query = $request->query('q', '');
            $limit = min(50, max(1, (int) $request->query('limit', 10)));
            
            if (empty($query)) {
                return $this->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $tags = Tag::where('name', 'LIKE', "%{$query}%")
                      ->limit($limit)
                      ->get();
            
            return $this->json([
                'success' => true,
                'data' => $tags
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate tag data
     */
    private function validateTagData(Request $request, int $excludeId = null): array
    {
        $data = $request->only(['name', 'slug']);
        
        // Required fields
        if (empty($data['name'])) {
            throw new \Exception('Name is required');
        }
        
        // Validate slug uniqueness
        if (!empty($data['slug'])) {
            $query = Tag::where('slug', $data['slug']);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if ($query->first()) {
                throw new \Exception('Slug already exists');
            }
        }
        
        // Validate name uniqueness
        $query = Tag::where('name', $data['name']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->first()) {
            throw new \Exception('Tag name already exists');
        }
        
        return $data;
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
            $query = Tag::where('slug', $slug);
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
     * Enrich tag data with additional information
     */
    private function enrichTagData($tag): array
    {
        $tagData = $tag->toArray();
        
        // Get blog count
        $blogCount = Database::getInstance()->fetchOne(
            "SELECT COUNT(*) as count FROM blog_tags WHERE tag_id = ?",
            [$tag->id]
        );
        $tagData['blog_count'] = $blogCount ? (int)$blogCount['count'] : 0;
        
        return $tagData;
    }
}
