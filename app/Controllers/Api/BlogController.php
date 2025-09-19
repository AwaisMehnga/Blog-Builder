<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\SeoMeta;
use Core\Database;

class BlogController extends Controller
{
    /**
     * Get all blogs with pagination and filtering
     */
    public function index(Request $request): Response
    {
        try {
            $limit = min(50, max(1, (int) $request->query('limit', 10)));
            $status = $request->query('status');
            $categoryId = $request->query('category_id');
            $search = $request->query('search');

            $query = Blog::query();

            // Apply filters
            if ($status && in_array($status, ['draft', 'published', 'archived'])) {
                $query->where('status', $status);
            }

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            }

            // Get paginated results using QueryBuilder's paginate method
            $paginatedResult = $query->orderBy('created_at', 'DESC')
                ->paginate($limit);

            // Enrich blog data with related information
            foreach ($paginatedResult['data'] as &$blog) {
                $blog = $this->enrichBlogData($blog);
            }

            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $paginatedResult
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
     * Get a single blog by ID
     */
    public function show(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $blog = Blog::find($id);

            if (!$blog) {
                return $this->json([
                    'success' => false,
                    'error' => 'Blog not found',
                    'data' => null
                ], 404);
            }

            $blog = $this->enrichBlogData($blog);

            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $blog
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
     * Create a new blog
     */
    public function store(Request $request): Response
    {
        try {
            $data = $this->validateBlogData($request);

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            } else {
                $data['slug'] = $this->generateUniqueSlug($data['slug']);
            }

            // Handle published_at
            if ($data['status'] === 'published' && empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $blog = Blog::create($data);

            // Handle tags
            if ($request->has('tags')) {
                $this->attachTags($blog->id, $request->input('tags', []));
            }

            // Handle SEO meta
            if ($request->has('seo_meta')) {
                $this->saveSeoMeta($blog->id, 'blog', $request->input('seo_meta', []));
            }

            $blog = $this->enrichBlogData($blog);

            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $blog
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
     * Update an existing blog
     */
    public function update(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $blog = Blog::find($id);

            if (!$blog) {
                return $this->json([
                    'success' => false,
                    'error' => 'Blog not found',
                    'data' => null
                ], 404);
            }

            $data = $this->validateBlogData($request, $id);

            // Generate slug if changed
            if (isset($data['slug']) && $data['slug'] !== $blog->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $id);
            } elseif (isset($data['title']) && $data['title'] !== $blog->title && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title'], $id);
            }

            // Handle published_at
            if (isset($data['status']) && $data['status'] === 'published' && $blog->status !== 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $blog->update($data);

            // Handle tags
            if ($request->has('tags')) {
                $this->syncTags($blog->id, $request->input('tags', []));
            }

            // Handle SEO meta
            if ($request->has('seo_meta')) {
                $this->saveSeoMeta($blog->id, 'blog', $request->input('seo_meta', []));
            }

            $blog = $this->enrichBlogData($blog);

            return $this->json([
                'success' => true,
                'error' => null,
                'data' => $blog
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
     * Delete a blog
     */
    public function destroy(Request $request): Response
    {
        try {
            $id = $request->routeParam('id');
            $blog = Blog::find($id);

            if (!$blog) {
                return $this->json([
                    'success' => false,
                    'error' => 'Blog not found',
                    'data' => null
                ], 404);
            }

            // Delete related data
            Database::getInstance()->execute(
                "DELETE FROM blog_tags WHERE blog_id = ?",
                [$id]
            );

            Database::getInstance()->execute(
                "DELETE FROM seo_meta WHERE entity_type = 'blog' AND entity_id = ?",
                [$id]
            );

            $blog->delete();

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
     * Bulk operations on blogs
     */
    public function bulk(Request $request): Response
    {
        try {
            $action = $request->input('action');
            $ids = $request->input('ids', '');

            $isAll = $ids === 'all';

            if (!$isAll) {
                $ids = array_filter(array_map('intval', explode(',', $ids)));
                if (empty($ids) || !is_array($ids)) {
                    return $this->json([
                        'success' => false,
                        'error' => 'No blog IDs provided',
                        'data' => null
                    ], 400);
                }
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            } else {
                $placeholders = '';
            }

            switch ($action) {
                case 'delete':
                    if ($isAll) {
                        Database::getInstance()->execute("DELETE FROM blog_tags");
                        Database::getInstance()->execute("DELETE FROM seo_meta WHERE entity_type = 'blog'");
                        Database::getInstance()->execute("DELETE FROM blogs");
                    } else {
                        Database::getInstance()->execute(
                            "DELETE FROM blog_tags WHERE blog_id IN ($placeholders)",
                            $ids
                        );
                        Database::getInstance()->execute(
                            "DELETE FROM seo_meta WHERE entity_type = 'blog' AND entity_id IN ($placeholders)",
                            $ids
                        );
                        Database::getInstance()->execute(
                            "DELETE FROM blogs WHERE id IN ($placeholders)",
                            $ids
                        );
                    }
                    break;

                case 'publish':
                    if ($isAll) {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'published', published_at = NOW()"
                        );
                    } else {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'published', published_at = NOW() WHERE id IN ($placeholders)",
                            $ids
                        );
                    }
                    break;

                case 'draft':
                    if ($isAll) {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'draft'"
                        );
                    } else {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'draft' WHERE id IN ($placeholders)",
                            $ids
                        );
                    }
                    break;

                case 'archive':
                    if ($isAll) {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'archived'"
                        );
                    } else {
                        Database::getInstance()->execute(
                            "UPDATE blogs SET status = 'archived' WHERE id IN ($placeholders)",
                            $ids
                        );
                    }
                    break;

                default:
                    return $this->json([
                        'success' => false,
                        'error' => 'Invalid action',
                        'data' => null
                    ], 400);
            }

            return $this->json([
                'success' => true,
                'error' => null,
                'data' => [
                    'action' => $action,
                    'affected_count' => $isAll ? 'all' : count($ids)
                ]
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
     * Validate blog data
     */
    private function validateBlogData(Request $request, int $excludeId = null): array
    {
        $data = $request->only([
            'category_id',
            'title',
            'slug',
            'content',
            'excerpt',
            'status',
            'published_at'
        ]);

        // Required fields
        if (empty($data['title'])) {
            throw new \Exception('Title is required');
        }

        if (empty($data['content'])) {
            throw new \Exception('Content is required');
        }

        // Validate status
        if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'])) {
            throw new \Exception('Invalid status. Must be: draft, published, or archived');
        }

        // Validate category
        if (!empty($data['category_id'])) {
            $category = Category::find($data['category_id']);
            if (!$category) {
                throw new \Exception('Invalid category');
            }
        }

        // Validate slug uniqueness
        if (!empty($data['slug'])) {
            $query = Blog::where('slug', $data['slug']);
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
     * Generate unique slug
     */
    private function generateUniqueSlug(string $text, int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Blog::where('slug', $slug);
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
     * Enrich blog data with related information
     */
    private function enrichBlogData($blog): array
    {
        $blogData = $blog->toArray();

        // Get category
        if ($blog->category_id) {
            $category = Category::find($blog->category_id);
            $blogData['category'] = $category ? $category->toArray() : null;
        } else {
            $blogData['category'] = null;
        }

        // Get tags
        $tags = Database::getInstance()->fetchAll(
            "SELECT t.* FROM tags t 
             INNER JOIN blog_tags bt ON t.id = bt.tag_id 
             WHERE bt.blog_id = ?",
            [$blog->id]
        );
        $blogData['tags'] = $tags;

        // Get SEO meta
        $seoMeta = Database::getInstance()->fetchOne(
            "SELECT * FROM seo_meta WHERE entity_type = 'blog' AND entity_id = ?",
            [$blog->id]
        );
        $blogData['seo_meta'] = $seoMeta;

        return $blogData;
    }

    /**
     * Attach tags to blog
     */
    private function attachTags(int $blogId, array $tagData): void
    {
        foreach ($tagData as $tagInfo) {
            if (is_array($tagInfo)) {
                $tagId = $tagInfo['id'] ?? null;
                $tagName = $tagInfo['name'] ?? '';
            } else {
                $tagId = is_numeric($tagInfo) ? $tagInfo : null;
                $tagName = is_string($tagInfo) ? $tagInfo : '';
            }

            if ($tagId) {
                // Use existing tag
                $tag = Tag::find($tagId);
                if ($tag) {
                    Database::getInstance()->execute(
                        "INSERT IGNORE INTO blog_tags (blog_id, tag_id) VALUES (?, ?)",
                        [$blogId, $tagId]
                    );
                }
            } elseif ($tagName) {
                // Create new tag
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tagName)));

                // Check if tag exists
                $existingTag = Tag::where('slug', $slug)->first();
                if ($existingTag) {
                    $tagId = $existingTag->id;
                } else {
                    $tag = Tag::create(['name' => $tagName, 'slug' => $slug]);
                    $tagId = $tag->id;
                }

                Database::getInstance()->execute(
                    "INSERT IGNORE INTO blog_tags (blog_id, tag_id) VALUES (?, ?)",
                    [$blogId, $tagId]
                );
            }
        }
    }

    /**
     * Sync tags for blog (remove old, add new)
     */
    private function syncTags(int $blogId, array $tagData): void
    {
        // Remove existing tags
        Database::getInstance()->execute(
            "DELETE FROM blog_tags WHERE blog_id = ?",
            [$blogId]
        );

        // Add new tags
        $this->attachTags($blogId, $tagData);
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
