<?php

namespace App\Controllers\Api;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use Core\Database;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request): Response
    {
        try {
            $db = Database::getInstance();
            
            // Get basic counts
            $blogStats = $db->fetchOne("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
                FROM blogs");
            
            $categoryCount = $db->fetchOne("SELECT COUNT(*) as count FROM categories");
            $tagCount = $db->fetchOne("SELECT COUNT(*) as count FROM tags");
            
            // Recent blogs
            $recentBlogs = $db->fetchAll("
                SELECT id, title, status, created_at 
                FROM blogs 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            
            // Popular categories (by blog count)
            $popularCategories = $db->fetchAll("
                SELECT c.id, c.name, COUNT(b.id) as blog_count
                FROM categories c
                LEFT JOIN blogs b ON c.id = b.category_id
                GROUP BY c.id, c.name
                ORDER BY blog_count DESC
                LIMIT 5
            ");
            
            // Blog status distribution for chart
            $statusDistribution = [
                'published' => (int)$blogStats['published'],
                'draft' => (int)$blogStats['draft'],
                'archived' => (int)$blogStats['archived']
            ];
            
            // Monthly blog creation stats (last 12 months)
            $monthlyStats = $db->fetchAll("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM blogs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            
            return $this->json([
                'success' => true,
                'data' => [
                    'counts' => [
                        'blogs' => [
                            'total' => (int)$blogStats['total'],
                            'published' => (int)$blogStats['published'],
                            'draft' => (int)$blogStats['draft'],
                            'archived' => (int)$blogStats['archived']
                        ],
                        'categories' => (int)$categoryCount['count'],
                        'tags' => (int)$tagCount['count']
                    ],
                    'recent_blogs' => $recentBlogs,
                    'popular_categories' => $popularCategories,
                    'status_distribution' => $statusDistribution,
                    'monthly_stats' => $monthlyStats
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content analytics
     */
    public function analytics(Request $request): Response
    {
        try {
            $db = Database::getInstance();
            $days = min(90, max(7, (int) $request->query('days', 30)));
            
            // Content creation over time
            $contentCreation = $db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as blogs_created
                FROM blogs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ", [$days]);
            
            // Content by status over time
            $statusOverTime = $db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    status,
                    COUNT(*) as count
                FROM blogs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), status
                ORDER BY date ASC, status
            ", [$days]);
            
            // Category distribution
            $categoryDistribution = $db->fetchAll("
                SELECT 
                    c.name as category,
                    COUNT(b.id) as blog_count,
                    ROUND((COUNT(b.id) / (SELECT COUNT(*) FROM blogs)) * 100, 2) as percentage
                FROM categories c
                LEFT JOIN blogs b ON c.id = b.category_id
                GROUP BY c.id, c.name
                HAVING blog_count > 0
                ORDER BY blog_count DESC
            ");
            
            // Top tags
            $topTags = $db->fetchAll("
                SELECT 
                    t.name,
                    COUNT(bt.blog_id) as usage_count
                FROM tags t
                INNER JOIN blog_tags bt ON t.id = bt.tag_id
                GROUP BY t.id, t.name
                ORDER BY usage_count DESC
                LIMIT 10
            ");
            
            // Content length analysis
            $contentLength = $db->fetchOne("
                SELECT 
                    AVG(CHAR_LENGTH(content)) as avg_length,
                    MIN(CHAR_LENGTH(content)) as min_length,
                    MAX(CHAR_LENGTH(content)) as max_length
                FROM blogs 
                WHERE status = 'published'
            ");
            
            return $this->json([
                'success' => true,
                'data' => [
                    'content_creation' => $contentCreation,
                    'status_over_time' => $statusOverTime,
                    'category_distribution' => $categoryDistribution,
                    'top_tags' => $topTags,
                    'content_metrics' => [
                        'avg_length' => round((float)$contentLength['avg_length']),
                        'min_length' => (int)$contentLength['min_length'],
                        'max_length' => (int)$contentLength['max_length']
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity
     */
    public function activity(Request $request): Response
    {
        try {
            $db = Database::getInstance();
            $limit = min(50, max(5, (int) $request->query('limit', 20)));
            
            // Recent blog activities
            $activities = $db->fetchAll("
                SELECT 
                    'blog' as type,
                    id,
                    title as name,
                    status,
                    created_at,
                    updated_at,
                    CASE 
                        WHEN created_at = updated_at THEN 'created'
                        ELSE 'updated'
                    END as action
                FROM blogs 
                ORDER BY updated_at DESC 
                LIMIT ?
            ", [$limit]);
            
            return $this->json([
                'success' => true,
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to fetch recent activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search across all content
     */
    public function search(Request $request): Response
    {
        try {
            $query = $request->query('q', '');
            $type = $request->query('type', 'all'); // all, blogs, categories, tags
            $limit = min(50, max(5, (int) $request->query('limit', 10)));
            
            if (empty($query)) {
                return $this->json([
                    'success' => true,
                    'data' => [
                        'blogs' => [],
                        'categories' => [],
                        'tags' => []
                    ]
                ]);
            }
            
            $db = Database::getInstance();
            $results = [];
            
            if ($type === 'all' || $type === 'blogs') {
                $results['blogs'] = $db->fetchAll("
                    SELECT id, title, slug, status, created_at
                    FROM blogs 
                    WHERE title LIKE ? OR content LIKE ?
                    ORDER BY 
                        CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                        created_at DESC
                    LIMIT ?
                ", ["%{$query}%", "%{$query}%", "%{$query}%", $limit]);
            }
            
            if ($type === 'all' || $type === 'categories') {
                $results['categories'] = $db->fetchAll("
                    SELECT id, name, slug, description
                    FROM categories 
                    WHERE name LIKE ? OR description LIKE ?
                    ORDER BY 
                        CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                        name ASC
                    LIMIT ?
                ", ["%{$query}%", "%{$query}%", "%{$query}%", $limit]);
            }
            
            if ($type === 'all' || $type === 'tags') {
                $results['tags'] = $db->fetchAll("
                    SELECT id, name, slug
                    FROM tags 
                    WHERE name LIKE ?
                    ORDER BY name ASC
                    LIMIT ?
                ", ["%{$query}%", $limit]);
            }
            
            return $this->json([
                'success' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
